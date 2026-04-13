<?php
require_once 'php/objects/Listing.php';
session_start();

// Grab 4 newest listings for the featured section
$listingObj = new Listing();
$featured   = $listingObj->getListings(['sort' => 'newest', 'limit' => 4]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>ReVènta — Curated Secondhand Fashion</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ── NAV ── -->
<nav>
  <div class="nav-left">
    <a href="pages/mens.php">Men</a>
    <a href="pages/womens.php">Women</a>
    <a href="pages/kids.php">Kids</a>
    <a href="pages/sell.php" class="nav-sell">Sell+</a>
  </div>
  <a href="index.php" class="nav-logo">Re<span id="theV">V</span>è<span>nta</span></a>
  <div class="nav-right">
    <a href="pages/explore.php">Explore</a>
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="pages/profile.php">Profile</a>
      <a href="pages/messages.php">Messages</a>
      <a href="pages/likes.php">Likes</a>
    <?php else: ?>
      <a href="pages/login.php">Login</a>
      <a href="pages/signup.php">Sign Up</a>
    <?php endif; ?>
  </div>
  <button class="nav-hamburger" id="navHamburger" aria-label="Open menu">
    <span></span><span></span><span></span>
  </button>
</nav>
<div class="nav-mobile-menu" id="navMobileMenu">
  <a href="pages/explore.php">Explore</a>
  <a href="pages/mens.php">Men</a>
  <a href="pages/womens.php">Women</a>
  <a href="pages/kids.php">Kids</a>
  <a href="pages/sell.php">Sell+</a>
  <?php if (isset($_SESSION['user_id'])): ?>
    <a href="pages/profile.php">Profile</a>
    <a href="pages/messages.php">Messages</a>
    <a href="pages/likes.php">My Likes</a>
    <a href="php/Utils/Logout.php">Logout</a>
  <?php else: ?>
    <a href="pages/login.php">Login</a>
    <a href="pages/signup.php">Sign Up</a>
  <?php endif; ?>
</div>

<!-- ── HERO ── -->
<div class="hero">
  <div class="hero-placeholder">
    <div class="hero-placeholder-figure"></div>
  </div>

  <div class="hero-text">
    <h1 class="hero-headline">
      Wear What<br><em>You Love</em>
    </h1>
    <div class="hero-sub">
      <p>Curated secondhand fashion.<br>Designer pieces, honest prices.</p>
      <a href="pages/explore.php" class="hero-cta">Shop Now</a>
    </div>
  </div>
</div>

<!-- ── MARQUEE ── -->
<div class="marquee-strip">
  <div class="marquee-track">
    <span>Acne Studios</span><span class="dot">·</span>
    <span>Lemaire</span><span class="dot">·</span>
    <span>Totême</span><span class="dot">·</span>
    <span>Helmut Lang</span><span class="dot">·</span>
    <span>COS</span><span class="dot">·</span>
    <span>Maison Margiela</span><span class="dot">·</span>
    <span>Arket</span><span class="dot">·</span>
    <span>A.P.C.</span><span class="dot">·</span>
    <span>Our Legacy</span><span class="dot">·</span>
    <span>Nike</span><span class="dot">·</span>
    <span>Adidas</span><span class="dot">·</span>
    <span>Zara</span><span class="dot">·</span>
    <span>H&M</span><span class="dot">·</span>
    <span>Gucci</span><span class="dot">·</span>
    <span>Levi's</span><span class="dot">·</span>
    <span>Ralph Lauren</span><span class="dot">·</span>
    <span>Supreme</span><span class="dot">·</span>
    <span>Puma</span><span class="dot">·</span>
    <span>New Balance</span><span class="dot">·</span>
    <span>Carhartt</span><span class="dot">·</span>
    <span>Stone Island</span><span class="dot">·</span>
    <span>Versace</span><span class="dot">·</span>
    <span>Balenciaga</span><span class="dot">·</span>
    <span>Off-White</span><span class="dot">·</span>
    <!-- duplicate for seamless loop -->
    <span>Acne Studios</span><span class="dot">·</span>
    <span>Lemaire</span><span class="dot">·</span>
    <span>Totême</span><span class="dot">·</span>
    <span>Helmut Lang</span><span class="dot">·</span>
    <span>COS</span><span class="dot">·</span>
    <span>Maison Margiela</span><span class="dot">·</span>
    <span>Arket</span><span class="dot">·</span>
    <span>A.P.C.</span><span class="dot">·</span>
    <span>Our Legacy</span><span class="dot">·</span>
    <span>Nike</span><span class="dot">·</span>
    <span>Adidas</span><span class="dot">·</span>
    <span>Zara</span><span class="dot">·</span>
    <span>H&M</span><span class="dot">·</span>
    <span>Gucci</span><span class="dot">·</span>
    <span>Levi's</span><span class="dot">·</span>
    <span>Ralph Lauren</span><span class="dot">·</span>
    <span>Supreme</span><span class="dot">·</span>
    <span>Puma</span><span class="dot">·</span>
    <span>New Balance</span><span class="dot">·</span>
    <span>Carhartt</span><span class="dot">·</span>
    <span>Stone Island</span><span class="dot">·</span>
    <span>Versace</span><span class="dot">·</span>
    <span>Balenciaga</span><span class="dot">·</span>
    <span>Off-White</span><span class="dot">·</span>
  </div>
</div>

<!-- ── CATEGORIES ── -->
<div class="categories reveal">
  <a href="pages/mens.php" class="category-card" style="text-decoration:none;">
    <div class="category-bg" style="background-image:url('assets/img/man-modified.jpg'); background-size:cover; background-position:center;"></div>
    <div class="category-label">
      <h3>Men</h3>
      <p>Explore collection</p>
    </div>
  </a>
  <a href="pages/womens.php" class="category-card" style="text-decoration:none;">
    <div class="category-bg" style="background-image:url('assets/img/woman-modified.jpg'); background-size:cover; background-position:center;"></div>
    <div class="category-label">
      <h3>Women</h3>
      <p>Explore collection</p>
    </div>
  </a>
  <a href="pages/kids.php" class="category-card" style="text-decoration:none;">
    <div class="category-bg" style="background-image:url('assets/img/kids2.0.jpg'); background-size:cover; background-position:center;"></div>
    <div class="category-label">
      <h3 id="cat_piclable">Kids</h3>
      <p id="cat_descption">Explore collection</p>
    </div>
  </a>
  <a href="pages/sell.php" class="category-card" style="text-decoration:none;">
    <div class="category-bg category-bg-sell"></div>
    <div class="category-sell-plus">+</div>
    <div class="category-label">
      <h3>Sell</h3>
      <p>List your pieces</p>
    </div>
  </a>
</div>

<!-- ── FEATURED / JUST IN ── -->
<div class="featured reveal">
  <div class="section-header">
    <h2 class="section-title">Just In</h2>
    <a href="pages/explore.php" class="section-link">View all</a>
  </div>

  <div class="products-grid">
    <?php if (!empty($featured)): ?>
      <?php foreach ($featured as $i => $item): ?>
        <a href="pages/listing.php?id=<?= $item['id'] ?>"
           class="product-card" style="text-decoration:none;color:inherit;">
          <div class="product-image">
            <?php if (!empty($item['cover_photo'])): ?>
              <img class="product-image-inner"
                   src="<?= htmlspecialchars($item['cover_photo']) ?>"
                   alt="<?= htmlspecialchars($item['name']) ?>"
                   loading="lazy"
                   style="object-fit:cover;width:100%;height:100%;display:block;">
            <?php else: ?>
              <div class="product-image-inner <?= 'p'.(($i%4)+1) ?>"></div>
            <?php endif; ?>
          </div>
          <div class="product-info">
            <h4><?= htmlspecialchars($item['seller_username'] ?? '') ?></h4>
            <p><?= htmlspecialchars($item['name']) ?></p>
            <div class="product-price">
              $<?= number_format((float)$item['price'], 2) ?>
              <?php if (!empty($item['condition_name'])): ?>
                <span class="original"><?= htmlspecialchars($item['condition_name']) ?></span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    <?php else: ?>
      <?php
      $placeholders = [
        ['brand'=>'Acne Studios','name'=>'Distressed Denim Jacket','price'=>'$340','cls'=>'p1'],
        ['brand'=>'Lemaire','name'=>'Croissant Shoulder Bag','price'=>'$420','cls'=>'p2'],
        ['brand'=>'Totême','name'=>'Barrel-Leg Trousers','price'=>'$210','cls'=>'p3'],
        ['brand'=>'Arket','name'=>'Merino Rib Turtleneck','price'=>'$68','cls'=>'p4'],
      ];
      foreach ($placeholders as $p): ?>
        <div class="product-card">
          <div class="product-image">
            <div class="product-image-inner <?= $p['cls'] ?>"></div>
          </div>
          <div class="product-info">
            <h4><?= $p['brand'] ?></h4>
            <p><?= $p['name'] ?></p>
            <div class="product-price"><?= $p['price'] ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ── FOOTER ── -->
<footer>
  <div class="footer-top">
    <div class="footer-brand">
      <div class="logo">Re<span>V</span>ènta</div>
      <p>A curated marketplace for pre-loved designer fashion. Quality pieces, honest descriptions, fair prices.</p>
    </div>
    <div class="footer-col">
      <h5>Shop</h5>
      <a href="pages/mens.php">Men</a>
      <a href="pages/womens.php">Women</a>
      <a href="pages/kids.php">Kids</a>
      <a href="pages/explore.php">All Items</a>
    </div>
    <div class="footer-col">
      <h5>Sell</h5>
      <a href="pages/sell.php">List an Item</a>
      <a href="pages/profile.php">My Listings</a>
    </div>
    <div class="footer-col">
      <h5>Account</h5>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="pages/profile.php">Profile</a>
        <a href="pages/messages.php">Messages</a>
        <a href="pages/likes.php">My Likes</a>
        <a href="php/Utils/Logout.php">Logout</a>
      <?php else: ?>
        <a href="pages/login.php">Login</a>
        <a href="pages/signup.php">Sign Up</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> ReVènta. All rights reserved.</p>
    <p>Sustainable fashion for everyone.</p>
  </div>
</footer>

<script>
// Hamburger
const ham  = document.getElementById('navHamburger');
const menu = document.getElementById('navMobileMenu');
ham.addEventListener('click', () => {
  ham.classList.toggle('open');
  menu.classList.toggle('open');
});

// Scroll reveal
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
</script>
</body>
</html>
