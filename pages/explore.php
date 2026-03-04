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

// Build filters from GET params
$filters = [
    'category_id'  => !empty($_GET['category_id'])  ? (int)$_GET['category_id']  : null,
    'condition_id' => !empty($_GET['condition_id'])  ? (int)$_GET['condition_id'] : null,
    'search'       => !empty($_GET['q'])             ? trim($_GET['q'])            : null,
    'sort'         => $_GET['sort'] ?? 'newest',
];

$listing  = new Listing();
$listings = $listing->getListings($filters);

// Helper: rebuild URL with overrides, keeping other params
function buildUrl(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== null && $v !== '');
    return 'explore.php?' . htmlspecialchars(http_build_query($params));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>ReVenta — Explore</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/styles.css">
<style>
  .product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(155px, 1fr));
    gap: 14px;
    padding: 14px 16px 100px;
  }
  .product-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    box-shadow: 0 1px 4px rgba(0,0,0,.08);
    transition: transform .15s, box-shadow .15s;
    text-decoration: none;
    display: block;
    color: inherit;
  }
  .product-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.12); }
  .card-img {
    width: 100%; aspect-ratio: 1/1;
    object-fit: cover; background: #f0f0f0; display: block;
  }
  .card-placeholder {
    width: 100%; aspect-ratio: 1/1;
    background: #f0f0f0;
    display: flex; align-items: center; justify-content: center;
    color: #ccc; font-size: 30px;
  }
  .card-body   { padding: 10px 12px 12px; }
  .card-title  { font-weight: 500; font-size: 14px; margin: 0 0 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .card-price  { font-weight: 700; font-size: 15px; }
  .card-meta   { font-size: 11px; color: #999; margin-top: 3px; }

  .empty-state { grid-column: 1/-1; text-align: center; padding: 70px 20px; color: #aaa; }
  .empty-state .empty-icon { font-size: 42px; margin-bottom: 10px; }
  .empty-state p { margin: 0; font-size: 15px; }

  .explore-header { padding: 20px 16px 0; }
  .explore-header h2 { margin: 0 0 12px; font-size: 26px; font-weight: 700; }

  .filter-row { display: flex; gap: 10px; flex-wrap: wrap; padding: 0 16px 10px; }
  .filter-select {
    padding: 7px 10px; border-radius: 8px;
    border: 1.5px solid #e0e0e0; font-size: 13px;
    font-family: inherit; background: #fff; cursor: pointer; outline: none;
  }
  .filter-select:focus { border-color: #111; }

  .categories        { padding: 0 16px 10px; }
  .categories-scroll { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px; scrollbar-width: none; }
  .categories-scroll::-webkit-scrollbar { display: none; }
  .cat-pill {
    padding: 6px 14px; border-radius: 20px; white-space: nowrap;
    border: 1.5px solid #e0e0e0; font-size: 13px;
    cursor: pointer; text-decoration: none; color: inherit;
    transition: all .15s; flex-shrink: 0;
  }
  .cat-pill:hover  { border-color: #999; }
  .cat-pill.active { background: #111; color: #fff; border-color: #111; }
</style>
</head>
<body>

<nav id="top-nav">
  <a href="../index.php" class="nav-logo">ReVenta<span>.</span></a>
  <form method="GET" action="explore.php" class="nav-search" style="margin:0;">
    <?php if (!empty($filters['category_id'])): ?>
      <input type="hidden" name="category_id" value="<?= (int)$filters['category_id'] ?>">
    <?php endif; ?>
    <?php if (!empty($filters['sort']) && $filters['sort'] !== 'newest'): ?>
      <input type="hidden" name="sort" value="<?= htmlspecialchars($filters['sort']) ?>">
    <?php endif; ?>
    <?php if (!empty($filters['condition_id'])): ?>
      <input type="hidden" name="condition_id" value="<?= (int)$filters['condition_id'] ?>">
    <?php endif; ?>
    <input type="text" name="q" id="search-input"
           value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
           placeholder="Search items, brands, sellers...">
  </form>
  <div class="nav-links">
    <a href="../index.php"         class="nav-tab-link">Home</a>
    <a href="explore.php"          class="nav-tab-link active">Explore</a>
    <a href="messages.php"         class="nav-tab-link">Messages</a>
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="profile.php"        class="nav-tab-link">Profile</a>
    <?php else: ?>
      <a href="login.php"          class="nav-tab-link">Login</a>
    <?php endif; ?>
  </div>
  <a href="sell.php"><button class="btn-sell">+ Sell</button></a>
</nav>

<main id="app">
  <div class="explore-header">
    <h2>Explore</h2>
    <div class="filter-row">
      <select class="filter-select" onchange="applyFilter('sort', this.value)">
        <option value="newest"     <?= $filters['sort']==='newest'     ? 'selected':'' ?>>Newest First</option>
        <option value="price-low"  <?= $filters['sort']==='price-low'  ? 'selected':'' ?>>Price: Low → High</option>
        <option value="price-high" <?= $filters['sort']==='price-high' ? 'selected':'' ?>>Price: High → Low</option>
      </select>
      <select class="filter-select" onchange="applyFilter('condition_id', this.value)">
        <option value="">All Conditions</option>
        <?php foreach ($conditions as $cond): ?>
          <option value="<?= $cond->id ?>" <?= $filters['condition_id']==$cond->id ? 'selected':'' ?>>
            <?= htmlspecialchars($cond->name) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Category pills -->
  <div class="categories">
    <div class="categories-scroll">
      <a class="cat-pill <?= empty($filters['category_id']) ? 'active' : '' ?>"
         href="<?= buildUrl(['category_id' => null]) ?>">All</a>
      <?php foreach ($categories as $cat): ?>
        <a class="cat-pill <?= $filters['category_id']==$cat->id ? 'active' : '' ?>"
           href="<?= buildUrl(['category_id' => $cat->id]) ?>">
          <?= htmlspecialchars($cat->name) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Grid -->
  <div class="product-grid">
    <?php if (empty($listings)): ?>
      <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <p>No listings found.<br>Try a different filter or search.</p>
      </div>
    <?php else: ?>
      <?php foreach ($listings as $item): ?>
        <a class="product-card" href="listing.php?id=<?= $item['id'] ?>">
          <?php if (!empty($item['cover_photo'])): ?>
            <img class="card-img"
                 src="<?= htmlspecialchars($item['cover_photo']) ?>"
                 alt="<?= htmlspecialchars($item['name']) ?>"
                 loading="lazy">
          <?php else: ?>
            <div class="card-placeholder">📦</div>
          <?php endif; ?>
          <div class="card-body">
            <div class="card-title"><?= htmlspecialchars($item['name']) ?></div>
            <div class="card-price">$<?= number_format((float)$item['price'], 2) ?></div>
            <div class="card-meta">
              <?= htmlspecialchars($item['condition_name'] ?? '') ?>
              <?php if (!empty($item['seller_username'])): ?> · <?= htmlspecialchars($item['seller_username']) ?><?php endif; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<nav id="bottom-nav">
  <a class="bottom-item" href="../index.php">
    <div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
    <div class="bottom-label">Home</div>
  </a>
  <a class="bottom-item active" href="explore.php">
    <div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
    <div class="bottom-label">Explore</div>
  </a>
  <a class="bottom-item sell-btn" href="sell.php">
    <div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div>
    <div class="bottom-label">Sell</div>
  </a>
  <a class="bottom-item" href="likes.php">
    <div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div>
    <div class="bottom-label">Likes</div>
  </a>
  <a class="bottom-item" href="messages.php">
    <div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
    <div class="bottom-label">Messages</div>
  </a>
  <a class="bottom-item" href="profile.php">
    <div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
    <div class="bottom-label">Profile</div>
  </a>
</nav>

<script>
function applyFilter(key, value) {
  const url = new URL(window.location.href);
  if (!value) { url.searchParams.delete(key); }
  else        { url.searchParams.set(key, value); }
  window.location.href = url.toString();
}
</script>
</body>
</html>