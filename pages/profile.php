<?php
require_once '../php/objects/User.php';
require_once '../php/objects/Listing.php';
require_once '../php/Utils/DatabaseConnection.php';
session_start();

$db   = new DatabaseConnection();
$conn = $db->getConnection();

// ── Determine whose profile we're viewing ──────────────────────────────────
$viewingUsername = trim($_GET['user'] ?? '');
$loggedIn        = isset($_SESSION['user_id']);

// Not logged in and no ?user= → redirect to login
if (!$loggedIn && $viewingUsername === '') {
    header("Location: login.php");
    exit;
}

$userObj = new User();

if ($viewingUsername !== '') {
    // Public profile: load by username
    $userRow = $userObj->getUserByUsername($viewingUsername);
    if (!$userRow) {
        http_response_code(404);
        die('User not found.');
    }
    // Populate object properties from the returned row
    $userObj->id              = $userRow['id'];
    $userObj->username        = $userRow['username'];
    $userObj->email           = $userRow['email'];
    $userObj->full_name       = $userRow['full_name']       ?? '';
    $userObj->phone_number    = $userRow['phone_number']    ?? '';
    $userObj->adress          = $userRow['address']         ?? '';
    $userObj->gender          = $userRow['gender']          ?? '';
    $userObj->birthday        = $userRow['birthday']        ?? '';
    $userObj->profile_picture = $userRow['profile_picture'] ?? null;
    $userObj->bio             = $userRow['bio']             ?? '';

    // If the requested username is actually the logged-in user, show own profile
    if ($loggedIn && strtolower($viewingUsername) === strtolower($_SESSION['username'] ?? '')) {
        header("Location: profile.php");
        exit;
    }
    $isOwn = false;
} else {
    // No ?user= → own profile
    $userObj->populate($_SESSION['user_id']);
    $isOwn = true;
}

$profileUserId = (int)$userObj->id;

// ── Ratings helpers ──────────────────────────────────────────────────────────

function getRatingData(int $sellerId, mysqli $conn): array {
    $stmt = $conn->prepare(
        "SELECT ROUND(AVG(stars), 1) AS avg_stars, COUNT(*) AS total
         FROM seller_ratings WHERE seller_id = ?"
    );
    $stmt->bind_param("i", $sellerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: ['avg_stars' => null, 'total' => 0];
}

function getUserRating(int $sellerId, int $raterId, mysqli $conn): ?int {
    $stmt = $conn->prepare(
        "SELECT stars FROM seller_ratings WHERE seller_id = ? AND rater_id = ?"
    );
    $stmt->bind_param("ii", $sellerId, $raterId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['stars'] : null;
}

$ratingData = ['avg_stars' => null, 'total' => 0];
$myRating   = null;
$canRate    = false;
$ratingsOk  = false;

try {
    $ratingData = getRatingData($profileUserId, $conn);
    if ($loggedIn && !$isOwn) {
        $myRating = getUserRating($profileUserId, (int)$_SESSION['user_id'], $conn);
        $canRate  = true;
    }
    $ratingsOk = true;
} catch (Throwable $e) {
    // Ratings table not yet created — degrade gracefully
}

// ── Listings ─────────────────────────────────────────────────────────────────
$listingObj = new Listing();
$listings   = $listingObj->getListingsBySeller($profileUserId);
$totalSold  = count(array_filter($listings, fn($l) => $l['is_sold']));
$active     = array_filter($listings, fn($l) => !$l['is_sold']);

$pageTitle = $isOwn
    ? 'Profile — ReVènta'
    : htmlspecialchars($userObj->username) . ' — ReVènta';

// Helper: resolve cover photo URL
function coverPhotoSrc(string $url): string {
    $url = ltrim($url, '/');
    return '../' . $url;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= $pageTitle ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/pages.css">
<style>
/* ── Rating widget ───────────────────────────────────────────── */
.rating-section {
  margin-top:32px; padding-top:28px; border-top:1px solid var(--light);
}
.rating-summary {
  display:flex; align-items:center; gap:14px; margin-bottom:18px;
}
.rating-avg {
  font-family:var(--serif); font-size:38px; font-weight:300; line-height:1;
  color:var(--black);
}
.rating-avg-empty {
  font-family:var(--serif); font-size:22px; font-weight:300;
  color:var(--mid); line-height:1;
}
.rating-stars-display { display:flex; gap:3px; }
.rating-star-icon { width:15px; height:15px; }
.rating-count {
  font-size:10px; letter-spacing:.12em; text-transform:uppercase;
  color:var(--mid); margin-top:3px;
}

/* Interactive star picker */
.star-picker { display:flex; gap:5px; margin-bottom:12px; cursor:pointer; }
.star-picker svg {
  width:26px; height:26px; transition:transform .15s;
  fill: none; stroke: #c8a96e; stroke-width:1.5;
}
.star-picker svg.filled  { fill:#c8a96e; }
.star-picker svg:hover   { transform:scale(1.15); }
.rating-prompt {
  font-size:10px; letter-spacing:.14em; text-transform:uppercase;
  color:var(--mid); margin-bottom:10px;
}
.rating-feedback {
  font-size:11px; letter-spacing:.06em; color:var(--mid);
  min-height:16px; transition:color .2s;
}
.rating-feedback.ok  { color:#1a7a40; }
.rating-feedback.err { color:#c0392b; }

.logout-link {
  font-size:10px; font-weight:400; letter-spacing:.18em; text-transform:uppercase;
  color:var(--mid); text-decoration:none; border-bottom:1px solid transparent;
  transition:color .2s,border-color .2s;
}
.logout-link:hover { color:var(--black); border-color:var(--black); }
</style>
</head>
<body class="profile-body">

<nav>
  <div class="nav-left">
    <a href="mens.php">Men</a>
    <a href="womens.php">Women</a>
    <a href="kids.php">Kids</a>
    <a href="sell.php" class="nav-sell">Sell+</a>
  </div>
  <a href="../index.php" class="nav-logo">Re<span id="theV">V</span>è<span>nta</span></a>
  <div class="nav-right">
    <a href="../index.php">Home</a>
    <a href="explore.php">Explore</a>
    <a href="profile.php" class="<?= $isOwn ? 'active' : '' ?>">Profile</a>
    <?php if ($loggedIn): ?>
      <a href="messages.php">Messages</a>
    <?php endif; ?>
  </div>
  <button class="nav-hamburger" id="navHamburger"><span></span><span></span><span></span></button>
</nav>
<div class="nav-mobile-menu" id="navMobileMenu">
  <a href="../index.php">Home</a>
  <a href="explore.php">Explore</a>
  <a href="mens.php">Men</a>
  <a href="womens.php">Women</a>
  <a href="sell.php">Sell+</a>
  <a href="profile.php">Profile</a>
  <?php if ($loggedIn): ?>
    <a href="messages.php">Messages</a>
    <a href="likes.php">My Likes</a>
    <a href="../php/Utils/Logout.php">Logout</a>
  <?php else: ?>
    <a href="login.php">Login</a>
    <a href="signup.php">Sign Up</a>
  <?php endif; ?>
</div>

<div class="profile-wrap">

  <!-- ── Avatar + username ─────────────────────────────────────── -->
  <div class="profile-avatar-wrap">

    <?php if ($isOwn): ?>
      <!-- Own profile: avatar is a clickable upload form -->
      <form method="POST" action="profile.php" enctype="multipart/form-data" id="avatarForm">
        <div class="profile-avatar-circle" title="Click to change photo">
          <?php if (!empty($userObj->profile_picture)): ?>
            <img src="../<?= htmlspecialchars(ltrim($userObj->profile_picture, '/')) ?>" alt="Avatar">
          <?php else: ?>
            <svg viewBox="0 0 70 70" fill="none">
              <circle cx="35" cy="22" r="14" fill="white"/>
              <ellipse cx="35" cy="56" rx="24" ry="14" fill="white"/>
            </svg>
          <?php endif; ?>
          <input type="file" name="avatar" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
        </div>
        <input type="hidden" name="full_name"    value="<?= htmlspecialchars($userObj->full_name ?? '') ?>">
        <input type="hidden" name="bio"          value="<?= htmlspecialchars($userObj->bio ?? '') ?>">
        <input type="hidden" name="phone_number" value="<?= htmlspecialchars($userObj->phone_number ?? '') ?>">
        <input type="hidden" name="address"      value="<?= htmlspecialchars($userObj->adress ?? '') ?>">
      </form>
    <?php else: ?>
      <!-- Public profile: static avatar -->
      <div class="profile-avatar-circle">
        <?php if (!empty($userObj->profile_picture)): ?>
          <img src="../<?= htmlspecialchars(ltrim($userObj->profile_picture, '/')) ?>" alt="Avatar">
        <?php else: ?>
          <svg viewBox="0 0 70 70" fill="none">
            <circle cx="35" cy="22" r="14" fill="white"/>
            <ellipse cx="35" cy="56" rx="24" ry="14" fill="white"/>
          </svg>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="profile-username-row">
      <span style="font-family:var(--sans);font-size:14px;font-weight:500;letter-spacing:.08em;">
        @<?= htmlspecialchars($userObj->username) ?>
      </span>
      <?php if ($isOwn): ?>
        <a href="settings.php" class="nav-settings" aria-label="Settings">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="1.5"
               stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06
                     a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09
                     A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83
                     l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09
                     A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83
                     l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09
                     a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83
                     l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09
                     a1.65 1.65 0 0 0-1.51 1z"/>
          </svg>
        </a>
      <?php endif; ?>
    </div>

    <?php if (!empty($userObj->full_name)): ?>
      <div style="font-size:12px;letter-spacing:.06em;color:var(--mid);margin-top:4px;">
        <?= htmlspecialchars($userObj->full_name) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($userObj->bio)): ?>
      <div style="font-size:12px;font-weight:300;color:var(--black);margin-top:8px;
                  max-width:340px;line-height:1.6;letter-spacing:.03em;">
        <?= nl2br(htmlspecialchars($userObj->bio)) ?>
      </div>
    <?php endif; ?>

    <?php if ($isOwn): ?>
      <div style="margin-top:8px;">
        <a href="likes.php"
           style="font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--mid);
                  text-decoration:none;border-bottom:1px solid transparent;
                  transition:color .2s,border-color .2s;"
           onmouseover="this.style.color='#0a0a0a';this.style.borderColor='#0a0a0a'"
           onmouseout="this.style.color='';this.style.borderColor=''">My Likes</a>
        &nbsp;·&nbsp;
        <a href="../php/Utils/Logout.php" class="logout-link">Logout</a>
      </div>
    <?php else: ?>
      <?php if ($loggedIn): ?>
        <div style="margin-top:10px;">
          <a href="messages.php"
             style="font-size:10px;letter-spacing:.18em;text-transform:uppercase;
                    color:var(--white);background:var(--black);text-decoration:none;
                    padding:10px 24px;display:inline-block;transition:opacity .2s;"
             onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity=''">
            Message
          </a>
        </div>
      <?php else: ?>
        <div style="margin-top:10px;">
          <a href="login.php"
             style="font-size:10px;letter-spacing:.14em;text-transform:uppercase;
                    color:var(--mid);text-decoration:none;">
            Log in to message
          </a>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- ── Stats ─────────────────────────────────────────────────── -->
  <div class="profile-stats">
    <div>
      <p class="profile-stat-label">Listings</p>
      <div class="profile-stat-value-row"><h1><?= count($listings) ?></h1></div>
    </div>
    <div>
      <p class="profile-stat-label">Active</p>
      <div class="profile-stat-value-row"><h1><?= count($active) ?></h1></div>
    </div>
    <div>
      <p class="profile-stat-label">Sold</p>
      <div class="profile-stat-value-row"><h1><?= $totalSold ?></h1></div>
    </div>
    <div>
      <p class="profile-stat-label">Member Since</p>
      <div class="profile-stat-value-row"
           style="font-family:var(--serif);font-size:14px;font-weight:300;">
        <?php
          echo !empty($userObj->birthday) ? date('Y', strtotime($userObj->birthday)) : '—';
        ?>
      </div>
    </div>
  </div>

  <!-- ── Rating section ────────────────────────────────────────── -->
  <?php if ($ratingsOk):
    $hasRatings = $ratingData['total'] > 0;
    $avgVal     = $hasRatings ? (float)$ratingData['avg_stars'] : 0;
  ?>
  <div class="rating-section">

    <!-- Summary row -->
    <div class="rating-summary" id="ratingSummary"
         style="<?= $hasRatings ? '' : 'display:none' ?>">
      <div class="rating-avg" id="ratingAvgNum"><?= $hasRatings ? $ratingData['avg_stars'] : '' ?></div>
      <div>
        <div class="rating-stars-display" id="avgStarsDisplay">
          <?php for ($i = 1; $i <= 5; $i++):
            $fill = ($hasRatings && $i <= round($avgVal)) ? '#c8a96e' : 'none'; ?>
            <svg class="rating-star-icon" viewBox="0 0 24 24"
                 fill="<?= $fill ?>" stroke="#c8a96e" stroke-width="1.5">
              <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02
                               12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/>
            </svg>
          <?php endfor; ?>
        </div>
        <div class="rating-count" id="ratingCount">
          <?= $ratingData['total'] ?> review<?= $ratingData['total'] !== 1 ? 's' : '' ?>
        </div>
      </div>
    </div>

    <!-- "No ratings yet" -->
    <div class="rating-avg-empty" id="ratingEmpty"
         style="<?= $hasRatings ? 'display:none' : 'margin-bottom:12px;' ?>">No ratings yet</div>

    <!-- Interactive picker — only for other logged-in users -->
    <?php if ($canRate): ?>
      <div class="rating-prompt" id="ratingPrompt">
        <?= $myRating ? 'Your rating — click to update' : 'Rate this seller' ?>
      </div>
      <div class="star-picker" id="starPicker"
           data-seller="<?= $profileUserId ?>"
           data-current="<?= $myRating ?? 0 ?>">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <svg viewBox="0 0 24 24" data-val="<?= $i ?>"
               class="<?= ($myRating && $i <= $myRating) ? 'filled' : '' ?>">
            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02
                             12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/>
          </svg>
        <?php endfor; ?>
      </div>
      <div class="rating-feedback" id="ratingFeedback"></div>
    <?php elseif ($isOwn && $hasRatings): ?>
      <div style="font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--mid);">
        Your seller score
      </div>
    <?php elseif (!$loggedIn): ?>
      <div style="font-size:11px;letter-spacing:.06em;color:var(--mid);margin-top:8px;">
        <a href="login.php" style="color:var(--black);">Log in</a> to rate this seller.
      </div>
    <?php endif; ?>

  </div>
  <?php endif; ?>

  <!-- ── Listings grid ─────────────────────────────────────────── -->
  <?php if (!empty($listings)): ?>
    <div class="profile-tabs" style="margin-top:40px;">
      <span class="profile-tab active">
        <?= $isOwn ? 'My Listings' : 'Listings' ?>
      </span>
    </div>
    <div class="profile-items-grid">
      <?php foreach ($listings as $item): ?>
        <div class="profile-item-card"
             onclick="window.location='listing.php?id=<?= $item['id'] ?>'">
          <div class="profile-item-image">
            <?php if (!empty($item['cover_photo'])): ?>
              <img style="width:100%;height:100%;object-fit:cover;display:block;"
                   src="../<?= htmlspecialchars(ltrim($item['cover_photo'], '/')) ?>"
                   alt="<?= htmlspecialchars($item['name']) ?>"
                   loading="lazy">
            <?php else: ?>
              <div class="profile-item-image-inner <?= 's'.((($item['id']-1)%8)+1) ?>"></div>
            <?php endif; ?>
          </div>
          <div class="profile-item-info">
            <div class="profile-item-name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="profile-item-price">
              $<?= number_format((float)$item['price'], 2) ?>
              <?php if ($item['is_sold']): ?>
                <span class="profile-sold-badge">Sold</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php elseif (!$isOwn): ?>
    <div style="text-align:center;padding:60px 24px;color:var(--mid);">
      <div style="font-family:var(--serif);font-size:22px;font-weight:300;margin-bottom:8px;">No listings yet</div>
      <div style="font-size:11px;letter-spacing:.1em;text-transform:uppercase;">This seller hasn't posted anything</div>
    </div>
  <?php endif; ?>

</div><!-- /.profile-wrap -->

<script>
/* ── Mobile nav ──────────────────────────────────────────────── */
const ham  = document.getElementById('navHamburger');
const menu = document.getElementById('navMobileMenu');
ham.addEventListener('click', () => {
  ham.classList.toggle('open');
  menu.classList.toggle('open');
});

/* ── Star rating picker ──────────────────────────────────────── */
(function () {
  const picker   = document.getElementById('starPicker');
  if (!picker) return;

  const stars    = picker.querySelectorAll('svg');
  const feedback = document.getElementById('ratingFeedback');
  const prompt   = document.getElementById('ratingPrompt');
  const sellerId = parseInt(picker.dataset.seller, 10);
  let current    = parseInt(picker.dataset.current, 10) || 0;
  let busy       = false;

  function paint(n, permanent) {
    stars.forEach((s, i) => {
      const on = i < n;
      s.style.fill = on ? '#c8a96e' : 'none';
      if (permanent) s.classList.toggle('filled', on);
    });
  }

  paint(current, false);

  stars.forEach(s => {
    s.addEventListener('mouseenter', () => paint(parseInt(s.dataset.val, 10), false));
    s.addEventListener('mouseleave', () => paint(current, false));
  });

  stars.forEach(s => {
    s.addEventListener('click', async () => {
      if (busy) return;
      const val = parseInt(s.dataset.val, 10);
      busy = true;
      feedback.textContent = 'Saving…';
      feedback.className   = 'rating-feedback';

      try {
        const res  = await fetch('rate_seller.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body:    `seller_id=${sellerId}&stars=${val}`
        });
        const json = await res.json();

        if (json.ok) {
          current = val;
          paint(current, true);
          feedback.textContent = '✓ ' + (json.message || 'Rating saved!');
          feedback.className   = 'rating-feedback ok';
          if (prompt) prompt.textContent = 'Your rating — click to update';

          const summary  = document.getElementById('ratingSummary');
          const emptyEl  = document.getElementById('ratingEmpty');
          const avgNumEl = document.getElementById('ratingAvgNum');
          const countEl  = document.getElementById('ratingCount');

          if (summary)  summary.style.display  = '';
          if (emptyEl)  emptyEl.style.display   = 'none';
          if (avgNumEl) avgNumEl.textContent    = json.avg;
          if (countEl)  countEl.textContent     =
            json.total + ' review' + (json.total !== 1 ? 's' : '');

          document.querySelectorAll('#avgStarsDisplay svg').forEach((sv, i) => {
            sv.setAttribute('fill', i < Math.round(json.avg) ? '#c8a96e' : 'none');
          });

        } else {
          feedback.textContent = '✕ ' + (json.message || 'Could not save rating.');
          feedback.className   = 'rating-feedback err';
        }
      } catch (e) {
        feedback.textContent = '✕ Network error — please try again.';
        feedback.className   = 'rating-feedback err';
      }

      busy = false;
      setTimeout(() => {
        if (feedback.classList.contains('ok')) {
          feedback.textContent = '';
          feedback.className   = 'rating-feedback';
        }
      }, 3000);
    });
  });
})();
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>