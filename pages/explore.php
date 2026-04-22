<?php
require_once '../php/objects/User.php';
require_once '../php/objects/Condition.php';
require_once '../php/objects/Category.php';
require_once '../php/objects/Listing.php';
session_start();

$condition  = new Condition();
$conditions = $condition->getAllConditions();

$Category   = new Category();
$categories = $Category->getAllCategories();

$filters = [
    'category_id'  => !empty($_GET['category_id'])  ? (int)$_GET['category_id']  : null,
    'condition_id' => !empty($_GET['condition_id'])  ? (int)$_GET['condition_id'] : null,
    'search'       => !empty($_GET['q'])             ? trim($_GET['q'])            : null,
    'sort'         => $_GET['sort'] ?? 'newest',
];

$listing  = new Listing();
$listings = $listing->getListings($filters);

function buildUrl(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== null && $v !== '');
    return 'explore.php?' . htmlspecialchars(http_build_query($params));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Explore — ReVènta</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/explore.css">
<style>
/* Extra overrides for PHP data */
.explore-card-info h4 { color: var(--mid); }
.explore-empty { grid-column: 1/-1; text-align: center; padding: 80px 20px; color: var(--mid); }
.explore-empty-icon { font-size: 40px; margin-bottom: 12px; }
.explore-empty p { font-family: var(--serif); font-size: 20px; margin-bottom: 6px; }
.explore-empty small { font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; }
</style>
</head>
<body class="page-body">

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
    <a href="explore.php" class="active">Explore</a>
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
    <a href="likes.php">My Likes</a>
    <a href="../php/Utils/Logout.php">Logout</a>
  <?php else: ?>
    <a href="login.php">Login</a>
    <a href="signup.php">Sign Up</a>
  <?php endif; ?>
</div>

<!-- Search form built into top area -->
<div class="explore-header" style="padding-top:48px;">
  <form method="GET" action="explore.php" style="display:flex;gap:8px;width:100%;max-width:500px;">
    <?php if (!empty($filters['category_id'])): ?>
      <input type="hidden" name="category_id" value="<?= (int)$filters['category_id'] ?>">
    <?php endif; ?>
    <?php if (!empty($filters['sort']) && $filters['sort'] !== 'newest'): ?>
      <input type="hidden" name="sort" value="<?= htmlspecialchars($filters['sort']) ?>">
    <?php endif; ?>
    <?php if (!empty($filters['condition_id'])): ?>
      <input type="hidden" name="condition_id" value="<?= (int)$filters['condition_id'] ?>">
    <?php endif; ?>
    <input type="text" name="q"
           value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
           placeholder="Search items, brands, sellers…"
           style="flex:1;font-family:var(--sans);font-size:12px;letter-spacing:.08em;border:1px solid var(--light);padding:10px 16px;outline:none;border-radius:0;transition:border-color .2s;"
           onfocus="this.style.borderColor='#0a0a0a'" onblur="this.style.borderColor='#d0d0d0'">
    <button type="submit" style="font-family:var(--sans);font-size:9px;font-weight:500;letter-spacing:.18em;text-transform:uppercase;background:var(--black);color:var(--white);border:none;padding:10px 20px;cursor:pointer;">Search</button>
  </form>

  <!-- Category tabs -->
  <div class="category-tabs" style="margin-top:24px;">
    <a href="<?= buildUrl(['category_id' => null]) ?>"
       class="tab <?= empty($filters['category_id']) ? 'active' : '' ?>">All</a>
    <?php foreach ($categories as $cat): ?>
      <a href="<?= buildUrl(['category_id' => $cat->id]) ?>"
         class="tab <?= $filters['category_id'] == $cat->id ? 'active' : '' ?>">
        <?= htmlspecialchars($cat->name) ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- Filter bar -->
<div class="filter-bar">
  <span class="filter-label">Filter</span>

  <select class="filter-pill" onchange="applyFilter('sort', this.value)">
    <option value="newest"     <?= $filters['sort']==='newest'     ? 'selected':'' ?>>Newest</option>
    <option value="price-low"  <?= $filters['sort']==='price-low'  ? 'selected':'' ?>>Price ↑</option>
    <option value="price-high" <?= $filters['sort']==='price-high' ? 'selected':'' ?>>Price ↓</option>
  </select>

  <select class="filter-pill" onchange="applyFilter('condition_id', this.value)">
    <option value="">Condition</option>
    <?php foreach ($conditions as $cond): ?>
      <option value="<?= $cond->id ?>" <?= $filters['condition_id'] == $cond->id ? 'selected':'' ?>>
        <?= htmlspecialchars($cond->name) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <span class="filter-count"><?= count($listings) ?> item<?= count($listings) !== 1 ? 's' : '' ?></span>
</div>

<!-- Grid -->
<div class="explore-grid-wrap">
  <div class="explore-grid" id="exploreGrid">
    <?php if (empty($listings)): ?>
      <div class="explore-empty">
        <div class="explore-empty-icon">✦</div>
        <p>Nothing found</p>
        <small>Try a different filter or search term</small>
      </div>
    <?php else: ?>
      <?php foreach ($listings as $item): ?>
        <div class="explore-card" onclick="window.location='listing.php?id=<?= $item['id'] ?>'">
          <div class="explore-card-image">
            <?php if (!empty($item['cover_photo'])): ?>
              <img style="width:100%;height:100%;object-fit:cover;display:block;"
                   src="../<?= htmlspecialchars(ltrim($item['cover_photo'], '/')) ?>"
                   alt="<?= htmlspecialchars($item['name']) ?>"
                   loading="lazy">
            <?php else: ?>
              <div class="explore-card-image-inner <?= 'g'.((($item['id']-1)%8)+1) ?>"></div>
            <?php endif; ?>

            <?php if ($item['is_sold']): ?>
              <span class="explore-badge sold">Sold</span>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id'])): ?>
              <button class="wish-btn" aria-label="Like"
                      onclick="event.stopPropagation(); toggleLike(this, <?= $item['id'] ?>)">
                <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
              </button>
            <?php endif; ?>
          </div>
          <div class="explore-card-info">
            <h4><?= htmlspecialchars($item['seller_username'] ?? '') ?></h4>
            <p><?= htmlspecialchars($item['name']) ?></p>
            <span class="explore-card-price">
              $<?= number_format((float)$item['price'], 2) ?>
              <?php if (!empty($item['condition_name'])): ?>
                <span class="original"><?= htmlspecialchars($item['condition_name']) ?></span>
              <?php endif; ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
function applyFilter(key, value) {
  const url = new URL(window.location.href);
  if (!value) { url.searchParams.delete(key); }
  else        { url.searchParams.set(key, value); }
  window.location.href = url.toString();
}

async function toggleLike(btn, listingId) {
  try {
    const res  = await fetch('../php/api/like.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=toggle&listing_id=${listingId}`
    });
    const data = await res.json();
    btn.classList.toggle('active', data.liked);
  } catch(e) { console.error(e); }
}

const ham = document.getElementById('navHamburger');
const menu = document.getElementById('navMobileMenu');
ham.addEventListener('click', () => { ham.classList.toggle('open'); menu.classList.toggle('open'); });
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>