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
  /* ════════════════════════════════
     HOME PAGE — PROPORTIONATE LAYOUT
     All sizing is relative (%, vw, clamp)
     Max-width capped at 1400px
  ════════════════════════════════ */

  /* Page wrapper */
  #app {
    background: #ffffff;
    display: flex;
    flex-direction: column;
    gap: clamp(6px, 1vw, 12px);
    padding: clamp(6px, 1vw, 12px);
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
    box-sizing: border-box;
  }

  /* ── HERO ─────────────────────── */
  .home-hero {
    display: grid;
    grid-template-columns: 55% 1fr;      /* slider slightly wider */
    gap: clamp(6px, 1vw, 12px);
    border-radius: 14px;
    overflow: hidden;
    min-height: 0;                        /* don't force height */
  }

  /* ── SLIDER ── */
  .hero-slider {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    background: var(--red);
    aspect-ratio: 4/3;                   /* proportionate, scales with width */
  }

  .hero-track {
    display: flex;
    height: 100%;
    transition: transform .45s cubic-bezier(.4,0,.2,1);
    will-change: transform;
  }

  .hero-slide {
    min-width: 100%;
    height: 100%;
    flex-shrink: 0;
    position: relative;
    background: var(--red);
  }

  .hero-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  .hero-slide-caption {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    padding: clamp(.8rem, 2vw, 2rem) clamp(.8rem, 1.5vw, 1.4rem) clamp(2rem, 4vw, 3.2rem);
    background: linear-gradient(to top, rgba(0,0,0,.6) 0%, transparent 100%);
    color: #fff;
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(.8rem, 1.2vw, 1.2rem);
    letter-spacing: 2px;
    pointer-events: none;
  }

  /* Arrows */
  .hero-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    width: clamp(28px, 3vw, 40px);
    height: clamp(28px, 3vw, 40px);
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,.18);
    backdrop-filter: blur(6px);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .2s, transform .2s;
  }
  .hero-arrow:hover {
    background: rgba(255,255,255,.35);
    transform: translateY(-50%) scale(1.08);
  }
  .hero-arrow-prev { left: clamp(6px, 1.2vw, 14px); }
  .hero-arrow-next { right: clamp(6px, 1.2vw, 14px); }

  /* Dots */
  .hero-dots {
    position: absolute;
    bottom: clamp(8px, 1.2vw, 16px);
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 6px;
    z-index: 10;
  }
  .hero-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: rgba(255,255,255,.4);
    cursor: pointer;
    transition: background .2s, transform .2s;
    border: none; padding: 0;
  }
  .hero-dot.active { background: #fff; transform: scale(1.3); }

  /* Hero text panel */
  .home-hero-text {
    background: #f0ece5;
    padding: clamp(1.2rem, 3vw, 2.5rem) clamp(1rem, 2.5vw, 2rem);
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: clamp(.5rem, 1vw, .9rem);
    border-radius: 12px;
  }

  .home-hero-text .hero-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--red);
    font-size: clamp(.6rem, .8vw, .75rem);
    font-weight: 500;
    letter-spacing: 3px;
    text-transform: uppercase;
    border-bottom: 1px solid var(--red);
    padding-bottom: 3px;
    width: fit-content;
  }

  .home-hero-text h2 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(1.4rem, 3.2vw, 3rem);
    letter-spacing: 2px;
    color: var(--black);
    line-height: .95;
  }

  .home-hero-text p {
    font-size: clamp(.75rem, 1vw, .95rem);
    line-height: 1.7;
    color: #666;
    font-weight: 300;
  }

  .home-hero-ctas {
    display: flex;
    gap: clamp(.4rem, .8vw, .8rem);
    flex-wrap: wrap;
    margin-top: .2rem;
  }

  .home-hero-ctas .btn-primary,
  .home-hero-ctas .btn-ghost {
    padding: clamp(7px, .8vw, 11px) clamp(14px, 1.8vw, 24px);
    font-size: clamp(.72rem, .9vw, .9rem);
    border-radius: 3px;
    text-transform: uppercase;
    letter-spacing: .5px;
  }

  .home-hero-ctas .btn-ghost {
    border-color: #ccc;
    color: #888;
  }

  /* ── SECTION LABEL ──────────────── */
  .home-section-label {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(.85rem, 1.1vw, 1.1rem);
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--black);
    padding: 2px 0;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .home-section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #bbb;
  }

  /* ── PRODUCT CARDS ──────────────── */
  .home-products {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: clamp(6px, 1vw, 12px);
  }

  .product-card {
    background: var(--red);
    border-radius: 10px;
    overflow: hidden;
    cursor: pointer;
    transition: transform .2s, box-shadow .2s;
    text-decoration: none;
    display: block;
    color: inherit;
    position: relative;
  }

  .product-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 28px rgba(0,0,0,.18);
  }

  .card-img-wrap { overflow: hidden; width: 100%; }

  .card-img {
    width: 100%;
    aspect-ratio: 3/4;
    object-fit: cover;
    display: block;
    transition: transform .4s ease;
  }
  .product-card:hover .card-img { transform: scale(1.05); }

  .card-placeholder {
    width: 100%;
    aspect-ratio: 3/4;
    background: #c0221f;
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255,255,255,.3);
    font-size: clamp(20px, 3vw, 36px);
  }

  .card-body {
    padding: clamp(7px, .8vw, 12px) clamp(8px, 1vw, 14px);
    background: #f5f0e8;
  }

  .card-label {
    font-size: clamp(.6rem, .7vw, .7rem);
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #999;
    margin-bottom: 3px;
    font-weight: 500;
  }

  .card-title {
    font-weight: 500;
    font-size: clamp(.75rem, .9vw, .9rem);
    margin: 0 0 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #0a0a0a;
  }

  .card-price {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(14px, 1.3vw, 18px);
    letter-spacing: 1px;
    color: #0a0a0a;
  }

  .card-meta {
    font-size: clamp(9px, .75vw, 11px);
    color: #aaa;
    margin-top: 2px;
  }

  .empty-state {
    grid-column: 1/-1;
    text-align: center;
    padding: 50px 20px;
    color: #bbb;
  }

  /* ── CATEGORY TILES ─────────────── */
  .home-categories {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: clamp(6px, 1vw, 12px);
  }

  .cat-tile {
    background: var(--red);
    border-radius: 10px;
    overflow: hidden;
    position: relative;
    aspect-ratio: 1/1;                   /* square — clean & proportionate */
    cursor: pointer;
    text-decoration: none;
    display: block;
  }

  .cat-tile img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .5s ease;
  }
  .cat-tile:hover img { transform: scale(1.06); }

  .cat-tile::before {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.28);
    z-index: 1;
    transition: background .3s;
  }
  .cat-tile:hover::before { background: rgba(0,0,0,.42); }

  .cat-label {
    position: absolute;
    z-index: 2;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-8deg);
    background: #0a0a0a;
    color: #fff;
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(.75rem, 1.2vw, 1.2rem);
    letter-spacing: 3px;
    padding: clamp(4px, .5vw, 7px) clamp(12px, 2vw, 26px);
    white-space: nowrap;
    clip-path: polygon(6% 0%, 100% 0%, 94% 100%, 0% 100%);
    transition: transform .3s;
  }
  .cat-tile:hover .cat-label {
    transform: translate(-50%, -50%) rotate(-8deg) scale(1.07);
  }

  /* ── PROMO FOOTER ROW ───────────── */
  .home-promo-row {
    display: grid;
    grid-template-columns: 1fr 2fr 2fr;
    gap: clamp(6px, 1vw, 12px);
    align-items: stretch;
  }

  .promo-box-red {
    background: var(--red);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 1/1;
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(1rem, 2vw, 1.8rem);
    letter-spacing: 3px;
    color: rgba(255,255,255,.9);
    text-align: center;
    padding: 1rem;
  }

  .promo-text-col {
    background: #f0ece5;
    border-radius: 10px;
    padding: clamp(.8rem, 1.5vw, 1.5rem) clamp(.8rem, 1.5vw, 1.5rem);
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: clamp(5px, .6vw, 9px);
  }

  .promo-text-line {
    height: clamp(7px, .7vw, 11px);
    background: #ddd;
    border-radius: 4px;
    width: 100%;
  }
  .promo-text-line:nth-child(2) { width: 82%; }
  .promo-text-line:nth-child(3) { width: 63%; }
  .promo-text-line:nth-child(4) { width: 88%; }
  .promo-text-line:nth-child(5) { width: 50%; }

  /* ── BREAKPOINTS ────────────────── */

  /* Tablet */
  @media (max-width: 900px) {
    .home-hero       { grid-template-columns: 1fr; }
    .hero-slider     { aspect-ratio: 16/9; border-radius: 12px; }
    .home-hero-text  { border-radius: 12px; padding: 1.4rem 1.4rem; }
    .home-hero-text h2 { font-size: clamp(1.6rem, 5vw, 3rem); }
    .home-products   { grid-template-columns: repeat(2, 1fr); }
    .home-categories { grid-template-columns: repeat(3, 1fr); }
    .home-promo-row  { grid-template-columns: 1fr 1fr; }
    .promo-box-red   { display: none; }
  }

  /* Mobile */
  @media (max-width: 540px) {
    .home-categories { grid-template-columns: repeat(2, 1fr); }
    .home-promo-row  { grid-template-columns: 1fr; }
    .cat-label       { font-size: clamp(.7rem, 3.5vw, 1rem); }
  }
</style>
</head>
<body>

<nav id="top-nav">
  <div class="nav-logo"><img src="assets/img/logo.png" alt="ReVenta Logo" id="logo"></div>
  <form method="GET" action="pages/explore.php" class="nav-search" style="margin:0">
    <input type="text" name="q" id="search-input" placeholder="Search items, brands, sellers...">
  </form>
  <div class="nav-links">
    <a href="index.php" class="nav-tab-link active">Home</a>
    <a href="pages/explore.php" class="nav-tab-link">Explore</a>
    <a href="pages/messages.php" class="nav-tab-link">Messages</a>

    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="pages/profile.php" class="nav-tab-link">Profile</a>
      <a href="pages/likes.php" class="nav-tab-link">My Likes</a>
      <a href="php/Utils/Logout.php" class="nav-tab-link">Logout</a>
    <?php else : ?>
      <a href="pages/login.php" class="nav-tab-link">Login</a>
    <?php endif; ?>
  </div>
  <a href="pages/sell.php"><button class="btn-sell">+ Sell</button></a>
</nav>

<main id="app">

  <!-- ── HERO ── -->
  <div class="home-hero">

    <!-- LEFT: Image slider -->
    <div class="hero-slider" id="heroSlider">
      <div class="hero-track" id="heroTrack">

        <!-- Slide 1 – first recent listing cover, or solid red -->
        <?php
          $slides = [];
          foreach ($recentListings as $l) {
            if (!empty($l['cover_photo'])) { $slides[] = $l; }
            if (count($slides) >= 5) break;
          }
        ?>
        <?php if (!empty($slides)): ?>
          <?php foreach ($slides as $i => $slide): ?>
            <div class="hero-slide">
              <img src="<?= htmlspecialchars($slide['cover_photo']) ?>"
                   alt="<?= htmlspecialchars($slide['name']) ?>"
                   <?= $i > 0 ? 'loading="lazy"' : '' ?>>
              <div class="hero-slide-caption"><?= htmlspecialchars($slide['name']) ?></div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <!-- Fallback: 3 solid red placeholder slides -->
          <div class="hero-slide"><div style="width:100%;height:100%;min-height:340px;background:var(--red)"></div></div>
          <div class="hero-slide"><div style="width:100%;height:100%;min-height:340px;background:#cc2020"></div></div>
          <div class="hero-slide"><div style="width:100%;height:100%;min-height:340px;background:#e02525"></div></div>
        <?php endif; ?>

      </div>

      <!-- Arrows -->
      <button class="hero-arrow hero-arrow-prev" id="heroPrev" aria-label="Previous">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button class="hero-arrow hero-arrow-next" id="heroNext" aria-label="Next">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </button>

      <!-- Dots (generated by JS) -->
      <div class="hero-dots" id="heroDots"></div>
    </div>

    <!-- RIGHT: Text panel -->
    <div class="home-hero-text">
      <span class="hero-tag">New Arrivals Daily</span>
      <h2>Wear What You<br><em style="color:var(--red);font-style:normal;">Love.</em></h2>
      <p>Thousands of unique pieces from sellers around the world. Pre-loved, curated, real.</p>
      <div class="home-hero-ctas">
        <a href="pages/explore.php"><button class="btn-primary">Start Shopping</button></a>
        <a href="pages/sell.php"><button class="btn-ghost">List an Item</button></a>
      </div>
    </div>

  </div>

  <!-- ── FOR YOU ── -->
  <div class="home-section-label">For You</div>

  <div class="home-products" id="home-grid">
    <?php if (empty($recentListings)): ?>
      <div class="empty-state">
        <div style="font-size:36px;margin-bottom:8px">🛍️</div>
        <p>No listings yet. <a href="pages/sell.php" style="color:#111;font-weight:600">Be the first to sell!</a></p>
      </div>
    <?php else: ?>
      <?php foreach (array_slice($recentListings, 0, 4) as $item): ?>
        <a class="product-card" href="pages/listing.php?id=<?= $item['id'] ?>">
          <?php if (!empty($item['cover_photo'])): ?>
            <div class="card-img-wrap">
              <img class="card-img"
                   src="<?= htmlspecialchars($item['cover_photo']) ?>"
                   alt="<?= htmlspecialchars($item['name']) ?>"
                   loading="lazy">
            </div>
          <?php else: ?>
            <div class="card-placeholder">📦</div>
          <?php endif; ?>
          <div class="card-body">
            <div class="card-label">Suggestion</div>
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

  <!-- ── CATEGORIES ── -->
  <div class="home-section-label">Shop by Category</div>

  <div class="home-categories">
    <a class="cat-tile" href="pages/explore.php?category=men">
      <img src="./assets/img/Men.jpg" alt="Men" loading="lazy">
      <span class="cat-label">Men</span>
    </a>
    <a class="cat-tile" href="pages/explore.php?category=women">
      <img src="./assets/img/Women.jpg" alt="Women" loading="lazy">
      <span class="cat-label">Woman</span>
    </a>
    <a class="cat-tile" href="pages/explore.php?category=plus-size">
      <img src="./assets/img/Plus-Size.jpg" alt="Plus Size" loading="lazy">
      <span class="cat-label">Plus Size</span>
    </a>
    <a class="cat-tile" href="pages/explore.php?category=accessories">
      <img src="./assets/img/Accessories.jpg" alt="Accessories" loading="lazy">
      <span class="cat-label">Accessories</span>
    </a>
    <a class="cat-tile" href="pages/explore.php?category=shoes">
      <img src="./assets/img/Sports.jpg" alt="Shoes" loading="lazy">
      <span class="cat-label">Shoes</span>
    </a>
    <a class="cat-tile" href="pages/explore.php?category=sports">
      <img src="./assets/img/Sports2.jpg" alt="Sports" loading="lazy">
      <span class="cat-label">Sports</span>
    </a>
  </div>

  <!-- ── PROMO FOOTER ROW ── -->
  <div class="home-promo-row">
    <div class="promo-box-red">SALES</div>
    <div class="promo-text-col">
      <div class="promo-text-line"></div>
      <div class="promo-text-line"></div>
      <div class="promo-text-line"></div>
      <div class="promo-text-line"></div>
      <div class="promo-text-line"></div>
    </div>
    <div class="promo-text-col">
      <div class="promo-text-line"></div>
      <div class="promo-text-line"></div>
      <div class="promo-text-line"></div>
      <div class="promo-text-line"></div>
      <div class="promo-text-line"></div>
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
(function () {
  const track   = document.getElementById('heroTrack');
  const slider  = document.getElementById('heroSlider');
  const dotsEl  = document.getElementById('heroDots');
  const prevBtn = document.getElementById('heroPrev');
  const nextBtn = document.getElementById('heroNext');

  if (!track) return;

  const slides = track.querySelectorAll('.hero-slide');
  const total  = slides.length;
  let   current = 0;
  let   autoTimer;

  /* ── Build dots ── */
  slides.forEach((_, i) => {
    const d = document.createElement('button');
    d.className = 'hero-dot' + (i === 0 ? ' active' : '');
    d.setAttribute('aria-label', 'Go to slide ' + (i + 1));
    d.addEventListener('click', () => goTo(i));
    dotsEl.appendChild(d);
  });

  function goTo(idx) {
    current = (idx + total) % total;
    track.style.transform = `translateX(-${current * 100}%)`;
    dotsEl.querySelectorAll('.hero-dot').forEach((d, i) =>
      d.classList.toggle('active', i === current)
    );
  }

  function next() { goTo(current + 1); }
  function prev() { goTo(current - 1); }

  nextBtn.addEventListener('click', () => { next(); resetAuto(); });
  prevBtn.addEventListener('click', () => { prev(); resetAuto(); });

  /* ── Auto-advance every 4 s ── */
  function startAuto() { autoTimer = setInterval(next, 4000); }
  function resetAuto()  { clearInterval(autoTimer); startAuto(); }
  startAuto();

  /* Pause on hover */
  slider.addEventListener('mouseenter', () => clearInterval(autoTimer));
  slider.addEventListener('mouseleave', startAuto);

  /* ── Touch / swipe ── */
  let touchStartX = 0;
  let touchDeltaX = 0;
  let isDragging  = false;

  slider.addEventListener('touchstart', e => {
    touchStartX = e.touches[0].clientX;
    touchDeltaX = 0;
    isDragging  = true;
    clearInterval(autoTimer);
    track.style.transition = 'none';
  }, { passive: true });

  slider.addEventListener('touchmove', e => {
    if (!isDragging) return;
    touchDeltaX = e.touches[0].clientX - touchStartX;
    const offset = -current * 100 + (touchDeltaX / slider.offsetWidth) * 100;
    track.style.transform = `translateX(${offset}%)`;
  }, { passive: true });

  slider.addEventListener('touchend', () => {
    isDragging = false;
    track.style.transition = '';
    if (touchDeltaX < -50)       next();
    else if (touchDeltaX > 50)   prev();
    else                          goTo(current);
    startAuto();
  });
})();
</script>
</body>
</html>