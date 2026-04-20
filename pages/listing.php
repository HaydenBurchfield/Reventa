<?php
require_once '../php/objects/User.php';
require_once '../php/objects/Listing.php';
require_once '../php/objects/Chat.php';
session_start();

$id = !empty($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: explore.php"); exit; }

$listingObj = new Listing();
$listingObj->id = $id;
$item = $listingObj->getListingById($id);

if (!$item) { header("Location: explore.php"); exit; }

// Handle "Message Seller" action
$chatUrl = null;
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $item['seller_id']) {
    $chatObj = new Chat();
    $existingChatId = $chatObj->getOrCreateChat($id, $_SESSION['user_id'], $item['seller_id']);
    if ($existingChatId) {
        $chatUrl = "messages.php?chat_id=" . $existingChatId;
    }
}

// Check if current user has liked this
$isLiked = false;
if (isset($_SESSION['user_id'])) {
    require_once '../php/Utils/DatabaseConnection.php';
    $db   = new DatabaseConnection();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT 1 FROM listing_like WHERE user_id=? AND listing_id=?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $id);
    $stmt->execute();
    $isLiked = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= htmlspecialchars($item['name']) ?> — ReVènta</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/pages.css">
</head>
<body class="product-body">

<nav>
  <div class="nav-left">
    <a href="mens.php">Men</a>
    <a href="womens.php">Women</a>
    <a href="kids.php">Kids</a>
    <a href="sell.php" class="nav-sell">Sell+</a>
  </div>
  <a href="../index.php" class="nav-logo">Re<span id="theV">V</span>è<span>nta</span></a>
  <div class="nav-right">
    <a href="explore.php">Explore</a>
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="profile.php">Profile</a>
      <a href="messages.php">Messages</a>
    <?php else: ?>
      <a href="login.php">Login</a>
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
  <?php if (isset($_SESSION['user_id'])): ?>
    <a href="profile.php">Profile</a>
    <a href="messages.php">Messages</a>
    <a href="likes.php">My Likes</a>
    <a href="../php/Utils/Logout.php">Logout</a>
  <?php else: ?>
    <a href="login.php">Login</a>
    <a href="signup.php">Sign Up</a>
  <?php endif; ?>
</div>

<div class="product-layout">

  <!-- LEFT: CAROUSEL -->
  <div class="product-carousel">
    <div class="product-carousel-track">
      <?php $photos = $item['photos'] ?? []; ?>
      <?php if (!empty($photos)): ?>
        <?php foreach ($photos as $i => $photo): ?>
          <div class="carousel-slide <?= $i===0?'active':'' ?>"
               style="background: url('<?= strpos($photo['photo_url'], '/') === 0 ? htmlspecialchars($photo['photo_url']) : '/' . htmlspecialchars($photo['photo_url']) ?>') center/cover no-repeat;">
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="carousel-slide active" style="background: linear-gradient(145deg,#e0ddd6 0%,#c8c5be 100%);"></div>
      <?php endif; ?>
    </div>

    <?php if (count($photos) > 1): ?>
      <button class="carousel-arrow prev" onclick="moveCarousel(-1)">
        <svg viewBox="0 0 10 16" fill="none"><polyline points="9,1 1,8 9,15" stroke="#0a0a0a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <button class="carousel-arrow next" onclick="moveCarousel(1)">
        <svg viewBox="0 0 10 16" fill="none"><polyline points="1,1 9,8 1,15" stroke="#0a0a0a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <div class="carousel-dots">
        <?php foreach ($photos as $i => $photo): ?>
          <button class="carousel-dot <?= $i===0?'active':'' ?>" onclick="goToSlide(<?= $i ?>)"></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- RIGHT: DETAILS -->
  <div class="product-details">
    <p class="product-brand"><?= htmlspecialchars($item['category_name'] ?? '') ?></p>
    <h1 class="product-name"><?= htmlspecialchars($item['name']) ?></h1>

    <p class="product-detail-price">
      $<?= number_format((float)$item['price'], 2) ?>
    </p>
    <span class="product-condition-tag"><?= htmlspecialchars($item['condition_name'] ?? '') ?></span>

    <?php if ($item['is_sold']): ?>
      <div style="display:inline-block;font-size:9px;font-weight:400;letter-spacing:.18em;text-transform:uppercase;background:var(--mid);color:var(--white);padding:5px 12px;margin-bottom:24px;">
        Sold
      </div>
    <?php endif; ?>

    <?php if (!$item['is_sold'] && isset($_SESSION['user_id']) && $_SESSION['user_id'] != $item['seller_id']): ?>
      <?php if ($chatUrl): ?>
        <a href="<?= htmlspecialchars($chatUrl) ?>" class="btn-buy" style="display:block;text-align:center;text-decoration:none;margin-bottom:12px;">
          Message Seller
        </a>
      <?php endif; ?>
    <?php elseif (!isset($_SESSION['user_id'])): ?>
      <a href="login.php" class="btn-buy" style="display:block;text-align:center;text-decoration:none;margin-bottom:12px;">
        Login to Contact Seller
      </a>
    <?php endif; ?>

    <!-- Like button -->
    <?php if (isset($_SESSION['user_id'])): ?>
      <button class="btn-wish <?= $isLiked ? 'liked' : '' ?>" id="likeBtn"
              onclick="toggleLike()"
              style="<?= $isLiked ? 'background:var(--black);color:var(--white);' : '' ?>">
        <?= $isLiked ? '♥ Liked' : '♡ Save to Likes' ?>
      </button>
    <?php endif; ?>

    <!-- Seller -->
    <div class="seller-card">
      <div class="seller-avatar">
        <?php if (!empty($item['seller_avatar'])): ?>
          <img src="../<?= htmlspecialchars($item['seller_avatar']) ?>" alt="">
        <?php else: ?>
          <svg viewBox="0 0 26 26" fill="none">
            <circle cx="13" cy="9" r="5" fill="#0a0a0a"/>
            <ellipse cx="13" cy="22" rx="9" ry="5" fill="#0a0a0a"/>
          </svg>
        <?php endif; ?>
      </div>
      <div class="seller-info">
        <a style="text-decoration: none; color: var(--black);" href="profile.php?user=<?= htmlspecialchars($item['seller_username']) ?>">
          <h5>@<?= htmlspecialchars($item['seller_username']) ?></h5>
        </a>
        <div style="font-size:11px;color:var(--mid);">
          <?= number_format($item['view_count'] ?? 0) ?> view<?= ($item['view_count']??0) !== 1 ? 's' : '' ?> ·
          Listed <?= date('M j, Y', strtotime($item['created_at'])) ?>
        </div>
      </div>
    </div>

    <!-- Description -->
    <?php if (!empty($item['description'])): ?>
      <div class="product-desc-block">
        <button class="product-desc-toggle open" onclick="toggleDesc(this)">
          <span>Description</span>
          <span class="chevron">›</span>
        </button>
        <div class="product-desc-text open">
          <?= nl2br(htmlspecialchars($item['description'])) ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Owner actions -->
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $item['seller_id']): ?>
      <div style="margin-top:32px;border-top:1px solid var(--light);padding-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
        <?php if (!$item['is_sold']): ?>
          <form method="POST" action="../php/api/mark_sold.php" style="margin:0;">
            <input type="hidden" name="listing_id" value="<?= $id ?>">
            <button type="submit" style="font-family:var(--sans);font-size:10px;font-weight:400;letter-spacing:.18em;text-transform:uppercase;color:var(--white);background:var(--mid);border:none;padding:12px 24px;cursor:pointer;">Mark as Sold</button>
          </form>
        <?php endif; ?>
        <form method="POST" action="../php/api/delete_listing.php" onsubmit="return confirm('Delete this listing?');" style="margin:0;">
          <input type="hidden" name="listing_id" value="<?= $id ?>">
          <button type="submit" style="font-family:var(--sans);font-size:10px;font-weight:400;letter-spacing:.18em;text-transform:uppercase;color:#c0392b;background:var(--white);border:1px solid #c0392b;padding:12px 24px;cursor:pointer;">Delete Listing</button>
        </form>
      </div>
    <?php endif; ?>

  </div>
</div>

<script>
var current = 0;
var slides  = document.querySelectorAll('.carousel-slide');
var dots    = document.querySelectorAll('.carousel-dot');

function goToSlide(n) {
  if (!slides.length) return;
  slides[current].classList.remove('active');
  if (dots[current]) dots[current].classList.remove('active');
  current = (n + slides.length) % slides.length;
  slides[current].classList.add('active');
  if (dots[current]) dots[current].classList.add('active');
}

function moveCarousel(dir) { goToSlide(current + dir); }

function toggleDesc(btn) {
  btn.classList.toggle('open');
  btn.nextElementSibling.classList.toggle('open');
}

async function toggleLike() {
  const btn = document.getElementById('likeBtn');
  try {
    const res  = await fetch('../php/api/like.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=toggle&listing_id=<?= $id ?>'
    });
    const data = await res.json();
    if (data.liked) {
      btn.textContent = '♥ Liked';
      btn.style.background = 'var(--black)';
      btn.style.color = 'var(--white)';
    } else {
      btn.textContent = '♡ Save to Likes';
      btn.style.background = '';
      btn.style.color = '';
    }
  } catch(e) { console.error(e); }
}

const ham = document.getElementById('navHamburger');
const menu = document.getElementById('navMobileMenu');
ham.addEventListener('click', () => { ham.classList.toggle('open'); menu.classList.toggle('open'); });
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>