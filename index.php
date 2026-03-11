<?php 

require_once __DIR__ . '/php/objects/User.php';
require_once __DIR__ . '/php/objects/Condition.php';
require_once __DIR__ . '/php/objects/Category.php';
require_once __DIR__ . '/php/objects/Listing.php';
session_start();  

$condition  = new Condition();
$conditions = $condition->getAllConditions();

$Category   = new Category();
$categories = $Category->getAllCategories();

// Load recent listings for the homepage grid
$listingObj   = new Listing();
$recentListings = $listingObj->getListings(['limit' => 24, 'sort' => 'newest']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>ReVenta — Buy & Sell Fashion</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/styles.css">
<style>
  /* ── Product grid ── */
  .product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(155px, 1fr));
    gap: 14px;
    padding: 14px 16px 20px;
  }
  .product-card {
    background: #fff; border-radius: 12px; overflow: hidden;
    cursor: pointer; box-shadow: 0 1px 4px rgba(0,0,0,.08);
    transition: transform .15s, box-shadow .15s;
    text-decoration: none; display: block; color: inherit;
  }
  .product-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.12); }
  .card-img { width: 100%; aspect-ratio: 1/1; object-fit: cover; background: #f0f0f0; display: block; }
  .card-placeholder { width: 100%; aspect-ratio: 1/1; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #ccc; font-size: 30px; }
  .card-body   { padding: 10px 12px 12px; }
  .card-title  { font-weight: 500; font-size: 14px; margin: 0 0 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .card-price  { font-weight: 700; font-size: 15px; }
  .card-meta   { font-size: 11px; color: #999; margin-top: 3px; }

  .empty-state { grid-column:1/-1; text-align:center; padding:50px 20px; color:#aaa; }
  .empty-state .empty-icon { font-size:36px; margin-bottom:8px; }
  .empty-state p { margin:0; font-size:14px; }
</style>
</head>
<body>

<nav id="top-nav">
  <div class="nav-logo">ReVenta<span>.</span></div>
  <form method="GET" action="pages/explore.php" class="nav-search" style="margin:0">
    <input type="text" name="q" id="search-input" placeholder="Search items, brands, sellers...">
  </form>
  <div class="nav-links">
    <a href="index.php" class="nav-tab-link active">Home</a>
    <a href="pages/explore.php" class="nav-tab-link">Explore</a>
    <a href="pages/messages.php" class="nav-tab-link">Messages</a>

    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="pages/profile.php" class="nav-tab-link">Profile</a>
      <a href="/php/Utils/Logout.php" class="nav-tab-link">Logout</a>
    <?php else : ?>
      <a href="pages/login.php" class="nav-tab-link">Login</a>
    <?php endif; ?>
  </div>
  <a href="pages/sell.php"><button class="btn-sell">+ Sell</button></a>
</nav>

<main id="app">
  <div class="hero">
    <div class="hero-text">
      <span class="hero-tag">New Arrivals Daily</span>
      <h1>Wear<br>What You<br><em>Love.</em></h1>
      <p>Thousands of unique pieces from sellers around the world. Pre-loved, curated, real.</p>
      <div class="hero-ctas">
        <a href="pages/explore.php"><button class="btn-primary">Start Shopping</button></a>
        <a href="pages/sell.php"><button class="btn-ghost">List an Item</button></a>
      </div>
    </div>
  </div>

  <!-- Category filter pills -->
  <div class="categories">
    <div class="categories-scroll" id="home-cats">
      <div class="cat-pill active" data-cat="">All</div>
      <?php foreach ($categories as $cat): ?>
        <div class="cat-pill" data-cat="<?= $cat->id ?>"><?= htmlspecialchars($cat->name) ?></div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="section-header"><h2>Trending Now</h2><a href="pages/explore.php">View all →</a></div>

  <!-- Product grid – populated by PHP on load, then refreshed via JS when filter changes -->
  <div class="product-grid" id="home-grid">
    <?php if (empty($recentListings)): ?>
      <div class="empty-state">
        <div class="empty-icon">🛍️</div>
        <p>No listings yet. <a href="pages/sell.php" style="color:#111;font-weight:600">Be the first to sell!</a></p>
      </div>
    <?php else: ?>
      <?php foreach ($recentListings as $item): ?>
        <a class="product-card" href="pages/listing.php?id=<?= $item['id'] ?>">
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

  <div style="height:2.5rem"></div>

  <div class="promo-banner">
    <div class="promo-text">
      <span class="tag">Limited Time</span>
      <h3>Zero Fees<br>This Week</h3>
      <p>List anything, keep everything. No fees on your first 10 sales.</p>
    </div>
  </div>
</main>

<nav id="bottom-nav">
  <a class="bottom-item active" href="index.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><div class="bottom-label">Home</div></a>
  <a class="bottom-item" href="pages/explore.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div><div class="bottom-label">Explore</div></a>
  <a class="bottom-item sell-btn" href="pages/sell.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div><div class="bottom-label">Sell</div></a>
  <a class="bottom-item" href="pages/likes.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div><div class="bottom-label">Likes</div></a>
  <a class="bottom-item" href="pages/messages.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div><div class="bottom-label">Messages</div></a>
  <a class="bottom-item" href="pages/profile.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div class="bottom-label">Profile</div></a>
</nav>

<script>
function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function renderGrid(listings) {
  const grid = document.getElementById('home-grid');
  if (!listings.length) {
    grid.innerHTML = '<div class="empty-state"><div class="empty-icon">🔍</div><p>No listings in this category yet.</p></div>';
    return;
  }
  grid.innerHTML = listings.map(item => {
    const img = item.cover_photo
      ? `<img class="card-img" src="${escHtml(item.cover_photo)}" alt="${escHtml(item.name)}" loading="lazy">`
      : `<div class="card-placeholder">📦</div>`;
    const meta = [item.condition_name, item.seller_username].filter(Boolean).join(' · ');
    return `<a class="product-card" href="pages/listing.php?id=${item.id}">
      ${img}
      <div class="card-body">
        <div class="card-title">${escHtml(item.name)}</div>
        <div class="card-price">$${parseFloat(item.price).toFixed(2)}</div>
        <div class="card-meta">${escHtml(meta)}</div>
      </div></a>`;
  }).join('');
}

// Category pill filter (AJAX)
document.getElementById('home-cats').addEventListener('click', e => {
  const pill = e.target.closest('.cat-pill');
  if (!pill) return;
  document.querySelectorAll('#home-cats .cat-pill').forEach(p => p.classList.remove('active'));
  pill.classList.add('active');

  const catId = pill.dataset.cat;
  const url   = 'php/api/listings.php?limit=24&sort=newest' + (catId ? `&category_id=${catId}` : '');

  fetch(url)
    .then(r => r.json())
    .then(renderGrid)
    .catch(() => {});
});
</script>
</body>
</html>