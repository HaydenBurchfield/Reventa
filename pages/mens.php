<?php
require_once '../php/objects/Listing.php';
require_once '../php/objects/Category.php';
session_start();

// Find the Men category id dynamically
$catObj    = new Category();
$allCats   = $catObj->getAllCategories();
$menCatId  = null;
foreach ($allCats as $c) {
    if (stripos($c->name, 'men') !== false && stripos($c->name, 'women') === false && stripos($c->name, 'kid') === false) {
        $menCatId = $c->id;
        break;
    }
}

$listingObj = new Listing();
$listings   = $listingObj->getListings([
    'category_id' => $menCatId,
    'sort'        => 'newest',
    'limit'       => 40,
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Men — ReVènta</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/pages.css">
</head>
<body class="shop-body">

<nav>
  <div class="nav-left">
    <a href="mens.php" class="active">Men</a>
    <a href="womens.php">Women</a>
    <a href="kids.php">Kids</a>
    <a href="sell.php" class="nav-sell">Sell+</a>
  </div>
  <a href="../index.php" class="nav-logo">Re<span id="theV">V</span>è<span>nta</span></a>
  <div class="nav-right">
    <a href="../index.php">Home</a>
    <a href="explore.php">Explore</a>
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="profile.php">Profile</a>
      <a href="messages.php">Messages</a>
    <?php else: ?>
      <a href="login.php">Login</a>
      <a href="signup.php">Sign Up</a>
    <?php endif; ?>
  </div>
  <button class="nav-hamburger" id="navHamburger"><span></span><span></span><span></span></button>
</nav>
<div class="nav-mobile-menu" id="navMobileMenu">
  <a href="../index.php">Home</a>
  <a href="explore.php">Explore</a>
  <a href="mens.php">Men</a>
  <a href="womens.php">Women</a>
  <a href="kids.php">Kids</a>
  <a href="sell.php">Sell+</a>
  <?php if (isset($_SESSION['user_id'])): ?>
    <a href="profile.php">Profile</a>
    <a href="messages.php">Messages</a>
    <a href="../php/Utils/Logout.php">Logout</a>
  <?php else: ?>
    <a href="login.php">Login</a>
    <a href="signup.php">Sign Up</a>
  <?php endif; ?>
</div>

<div class="shop-header">
  <span class="shop-tag">Men</span>
</div>

<div class="shop-grid-wrap">
  <div class="shop-grid">
    <?php if (!empty($listings)): ?>
      <?php foreach ($listings as $i => $item): ?>
        <div class="shop-card" onclick="window.location='listing.php?id=<?= $item['id'] ?>'">
          <div class="shop-card-image">
            <?php if (!empty($item['cover_photo'])): ?>
              <img class="shop-card-image-inner"
                   src="../<?= htmlspecialchars($item['cover_photo']) ?>"
                   alt="<?= htmlspecialchars($item['name']) ?>"
                   loading="lazy"
                   style="object-fit:cover;width:100%;height:100%;display:block;">
            <?php else: ?>
              <div class="shop-card-image-inner <?= 's'.(($i%8)+1) ?>"></div>
            <?php endif; ?>
          </div>
          <div class="shop-card-info">
            <h4><?= htmlspecialchars($item['seller_username'] ?? '') ?></h4>
            <p><?= htmlspecialchars($item['name']) ?></p>
            <span class="shop-card-price">$<?= number_format((float)$item['price'], 2) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <!-- Placeholder cards when no DB listings -->
      <?php
      $samples = [
        ['Our Legacy','Wide-Leg Trousers','$195','s1','New'],
        ['COS','Relaxed Linen Shirt','$72','s2',''],
        ['Helmut Lang','Archive Denim Jacket','$420','s3','Rare'],
        ['Arket','Merino Crewneck','$88','s4',''],
        ['A.P.C.','Slim Raw Denim','$160','s5',''],
        ['Acne Studios','Leather Belt','$95','s6','New'],
        ['Lemaire','Twisted Polo Shirt','$230','s7',''],
        ['Totême','Wool Overcoat','$540','s8',''],
      ];
      foreach ($samples as $s): ?>
        <div class="shop-card" onclick="window.location='explore.php'">
          <div class="shop-card-image">
            <div class="shop-card-image-inner <?= $s[3] ?>"></div>
            <?php if ($s[4]): ?><span class="shop-badge"><?= $s[4] ?></span><?php endif; ?>
          </div>
          <div class="shop-card-info">
            <h4><?= $s[0] ?></h4>
            <p><?= $s[1] ?></p>
            <span class="shop-card-price"><?= $s[2] ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
const ham = document.getElementById('navHamburger');
const menu = document.getElementById('navMobileMenu');
ham.addEventListener('click', () => { ham.classList.toggle('open'); menu.classList.toggle('open'); });
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
