<?php
require_once '../php/objects/User.php';
require_once '../php/Utils/DatabaseConnection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$db     = new DatabaseConnection();
$conn   = $db->getConnection();

$sql = "
    SELECT l.id, l.name, l.price, l.is_sold,
           c.name AS condition_name,
           cat.name AS category_name,
           u.username AS seller_username,
           (SELECT lp.photo_url FROM listing_photo lp
            WHERE lp.listing_id = l.id ORDER BY lp.sort_order ASC LIMIT 1) AS cover_photo,
           ll.created_at AS liked_at
    FROM listing_like ll
    JOIN listing l         ON l.id   = ll.listing_id
    JOIN `condition` c     ON c.id   = l.condition_id
    LEFT JOIN category cat ON cat.id = l.category_id
    JOIN user u            ON u.id   = l.seller_id
    WHERE ll.user_id = ?
    ORDER BY ll.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$liked  = [];
while ($row = $result->fetch_assoc()) $liked[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Liked Items — ReVènta</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/pages.css">
<style>
.likes-header {
  padding: 40px 48px 24px;
  display: flex; align-items: baseline; gap: 12px;
}
.likes-header h2 {
  font-family: var(--serif);
  font-size: 36px; font-weight: 300;
}
.likes-count { font-size: 12px; letter-spacing: .1em; color: var(--mid); }

.likes-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  padding: 0 48px 80px;
}

.like-card { cursor: pointer; position: relative; }

.like-card-image {
  aspect-ratio: 3/4;
  background: var(--off-white);
  overflow: hidden;
  position: relative;
  border: 1px solid rgba(0,0,0,0.06);
}

.like-card-inner {
  width: 100%; height: 100%;
  transition: transform 0.7s cubic-bezier(0.16,1,0.3,1);
}
.like-card:hover .like-card-inner { transform: scale(1.04); }

.like-card-inner img { width:100%;height:100%;object-fit:cover;display:block; }

.like-remove-btn {
  position: absolute; top: 8px; right: 8px;
  width: 28px; height: 28px;
  background: rgba(255,255,255,0.92); border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 1px 4px rgba(0,0,0,.15);
  transition: background .15s;
  opacity: 0;
}
.like-card:hover .like-remove-btn { opacity: 1; }
.like-remove-btn svg { width: 14px; height: 14px; }

@media (hover: none) { .like-remove-btn { opacity: 1; } }

.like-card-sold { position: absolute; top: 8px; left: 8px; font-size: 8px; font-weight: 400; letter-spacing: .16em; text-transform: uppercase; background: var(--mid); color: var(--white); padding: 3px 8px; }

.like-card-info { padding: 12px 0 0; }
.like-card-name { font-family: var(--serif); font-size: 16px; font-weight: 400; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.like-card-price { font-size: 13px; font-weight: 300; }
.like-card-meta { font-size: 10px; color: var(--mid); margin-top: 3px; letter-spacing: .04em; }

.empty-state { text-align: center; padding: 80px 24px; color: var(--mid); }
.empty-icon  { font-family: var(--serif); font-size: 48px; margin-bottom: 16px; }
.empty-title { font-family: var(--serif); font-size: 24px; font-weight: 300; margin-bottom: 8px; color: var(--black); }
.empty-sub   { font-size: 11px; letter-spacing: .1em; text-transform: uppercase; }
.empty-cta   { display: inline-block; margin-top: 24px; font-size: 10px; font-weight: 400; letter-spacing: .22em; text-transform: uppercase; color: var(--white); background: var(--black); border: none; padding: 14px 32px; cursor: pointer; text-decoration: none; transition: opacity .2s; }
.empty-cta:hover { opacity: .75; }

.toast { position: fixed; bottom: 32px; left: 50%; transform: translateX(-50%) translateY(10px); background: var(--black); color: var(--white); padding: 10px 22px; font-size: 11px; letter-spacing: .1em; text-transform: uppercase; opacity: 0; transition: opacity .25s, transform .25s; pointer-events: none; z-index: 999; white-space: nowrap; }
.toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

@media (max-width: 1024px) {
  .likes-header { padding: 32px 32px 20px; }
  .likes-grid   { grid-template-columns: repeat(3, 1fr); padding: 0 32px 64px; }
}
@media (max-width: 767px) {
  .likes-header { padding: 24px 16px 16px; }
  .likes-header h2 { font-size: 26px; }
  .likes-grid   { grid-template-columns: repeat(2, 1fr); gap: 12px; padding: 0 16px 56px; }
  .like-remove-btn { opacity: 1; }
}
@media (max-width: 400px) {
  .likes-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body class="shop-body">

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
    <a href="profile.php">Profile</a>
    <a href="likes.php" class="active">My Likes</a>
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
  <a href="likes.php">My Likes</a>
  <a href="../php/Utils/Logout.php">Logout</a>
</div>

<div class="likes-header">
  <h2>Liked Items</h2>
  <span class="likes-count" id="likesCount">
    <?= count($liked) ?> item<?= count($liked) !== 1 ? 's' : '' ?>
  </span>
</div>

<?php if (empty($liked)): ?>
  <div class="empty-state">
    <div class="empty-icon">♡</div>
    <div class="empty-title">Nothing saved yet</div>
    <div class="empty-sub">Tap the heart on any listing to save it here</div>
    <a href="explore.php" class="empty-cta">Browse Listings</a>
  </div>
<?php else: ?>
  <div class="likes-grid" id="likesGrid">
    <?php foreach ($liked as $item): ?>
      <div class="like-card" data-id="<?= $item['id'] ?>">
        <div class="like-card-image" onclick="window.location='listing.php?id=<?= $item['id'] ?>'">
          <div class="like-card-inner">
            <?php if (!empty($item['cover_photo'])): ?>
              <img src="../<?= htmlspecialchars($item['cover_photo']) ?>"
                   alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy">
            <?php else: ?>
              <div style="width:100%;height:100%;background:linear-gradient(145deg,#e0ddd6 0%,#c8c5be 100%);"></div>
            <?php endif; ?>
          </div>

          <?php if ($item['is_sold']): ?>
            <span class="like-card-sold">Sold</span>
          <?php endif; ?>

          <button class="like-remove-btn" title="Remove"
                  onclick="event.stopPropagation(); removeLike(this, <?= $item['id'] ?>)">
            <svg viewBox="0 0 24 24" fill="#e53935" stroke="#e53935" stroke-width="2">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
          </button>
        </div>

        <div class="like-card-info" onclick="window.location='listing.php?id=<?= $item['id'] ?>'">
          <div class="like-card-name"><?= htmlspecialchars($item['name']) ?></div>
          <div class="like-card-price">$<?= number_format((float)$item['price'], 2) ?></div>
          <div class="like-card-meta">
            <?= htmlspecialchars($item['condition_name'] ?? '') ?>
            <?php if (!empty($item['seller_username'])): ?> · @<?= htmlspecialchars($item['seller_username']) ?><?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="toast" id="toast"></div>

<script>
async function removeLike(btn, listingId) {
  try {
    const res  = await fetch('../php/api/like.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=toggle&listing_id=${listingId}`
    });
    const data = await res.json();
    if (!data.liked) {
      const card = btn.closest('.like-card');
      card.style.transition = 'opacity .3s, transform .3s';
      card.style.opacity = '0';
      card.style.transform = 'scale(.92)';
      setTimeout(() => {
        card.remove();
        updateCount();
        checkEmpty();
      }, 300);
      showToast('Removed from likes');
    }
  } catch(e) { console.error(e); }
}

function updateCount() {
  const n = document.querySelectorAll('#likesGrid .like-card').length;
  document.getElementById('likesCount').textContent = n + ' item' + (n !== 1 ? 's' : '');
}

function checkEmpty() {
  const grid = document.getElementById('likesGrid');
  if (grid && !grid.querySelector('.like-card')) {
    grid.outerHTML = `<div class="empty-state">
      <div class="empty-icon">♡</div>
      <div class="empty-title">Nothing saved yet</div>
      <div class="empty-sub">Tap the heart on any listing to save it here</div>
      <a href="explore.php" class="empty-cta">Browse Listings</a>
    </div>`;
  }
}

let toastTimer;
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), 2200);
}

const ham = document.getElementById('navHamburger');
const menu = document.getElementById('navMobileMenu');
ham.addEventListener('click', () => { ham.classList.toggle('open'); menu.classList.toggle('open'); });
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>
