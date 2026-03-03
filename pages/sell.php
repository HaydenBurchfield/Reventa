<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>ReVenta — Sell</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>

<nav id="top-nav">
  <a href="index.php" class="nav-logo">ReVenta<span>.</span></a>
  <div class="nav-search"><input type="text" id="search-input" placeholder="Search items, brands, sellers..."></div>
  <div class="nav-links">
    <a href="../index.php" class="nav-tab-link">Home</a>
    <a href="../pages/explore.php" class="nav-tab-link active">Explore</a>
    <a href="../pages/messages.php" class="nav-tab-link">Messages</a>
    <a href="../pages/profile.php" class="nav-tab-link">Profile</a>
  </div>
  <a href="../pages/sell.php"><button class="btn-sell">+ Sell</button></a>
</nav>

<main id="app">
  <div class="explore-header">
    <h2>Explore</h2>
    <div class="filter-row">
      <select class="filter-select" id="sort-select">
        <option value="newest">Newest First</option>
        <option value="price-low">Price: Low → High</option>
        <option value="price-high">Price: High → Low</option>
      </select>
      <select class="filter-select" id="condition-select">
        <option value="all">All Conditions</option>
        <option value="like-new">Like New</option>
        <option value="very-good">Very Good</option>
        <option value="good">Good</option>
      </select>
    </div>
  </div>
  <div class="categories"><div class="categories-scroll" id="explore-filters">
    <div class="cat-pill active" data-cat="all">All</div><div class="cat-pill" data-cat="tops">Tops</div><div class="cat-pill" data-cat="bottoms">Bottoms</div><div class="cat-pill" data-cat="dresses">Dresses</div><div class="cat-pill" data-cat="outerwear">Outerwear</div><div class="cat-pill" data-cat="shoes">Shoes</div><div class="cat-pill" data-cat="accessories">Accessories</div><div class="cat-pill" data-cat="vintage">Vintage</div><div class="cat-pill" data-cat="luxury">Luxury</div><div class="cat-pill" data-cat="bags">Bags</div>
  </div></div>
  <div class="product-grid" id="explore-grid"></div>
</main>


<main id="app">
  <div class="sell-page">
    <h2>List an Item</h2>
    <p class="sell-subtitle">What are you selling today?</p>
    <div class="sell-form">
      <div class="upload-zone" id="upload-zone">
        <div class="upload-icon">📸</div>
        <div class="upload-label">Add up to 8 photos</div>
        <div class="upload-hint">Tap to upload or drag and drop</div>
      </div>
      <div class="form-group">
        <label>Title</label>
        <input type="text" class="form-input" placeholder="e.g. Vintage Levi's 501 Jeans W32">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Price</label>
          <div class="price-input-wrap">
            <span class="currency">$</span>
            <input type="number" class="form-input price-input" placeholder="0.00">
          </div>
        </div>
        <div class="form-group">
          <label>Category</label>
          <select class="form-input form-select">
            <option>Select category</option>
            <option>Tops</option><option>Bottoms</option><option>Dresses</option>
            <option>Outerwear</option><option>Shoes</option><option>Accessories</option><option>Bags</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Condition</label>
        <div class="condition-pills">
          <div class="cond-pill active">Like New</div>
          <div class="cond-pill">Very Good</div>
          <div class="cond-pill">Good</div>
          <div class="cond-pill">Fair</div>
        </div>
      </div>
      <div class="form-group">
        <label>Brand</label>
        <input type="text" class="form-input" placeholder="e.g. Levi's, Zara, Nike...">
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea class="form-input form-textarea" placeholder="Describe your item — size, colour, any flaws..."></textarea>
      </div>
      <button class="btn-list" id="btn-list">List for Sale</button>
    </div>
  </div>
</main>

<nav id="bottom-nav">
  <a class="bottom-item" href="index.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><div class="bottom-label">Home</div></a>
  <a class="bottom-item" href="explore.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div><div class="bottom-label">Explore</div></a>
  <a class="bottom-item sell-btn active" href="sell.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div><div class="bottom-label">Sell</div></a>
  <a class="bottom-item" href="likes.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div><div class="bottom-label">Likes</div></a>
  <a class="bottom-item" href="messages.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div><div class="bottom-label">Messages</div></a>
  <a class="bottom-item" href="profile.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div class="bottom-label">Profile</div></a>
</nav>

<script src="data.js"></script>
<script src="app.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.cond-pill').forEach(p =>
      p.addEventListener('click', () => {
        document.querySelectorAll('.cond-pill').forEach(x => x.classList.remove('active'));
        p.classList.add('active');
      })
    );
    document.getElementById('upload-zone')?.addEventListener('click', () => showToast('📸 Photo upload would open here'));
    document.getElementById('btn-list')?.addEventListener('click', () => showToast('🎉 Item listed successfully!'));
    const si = document.getElementById('search-input');
    si?.addEventListener('keydown', e => {
      if (e.key === 'Enter') window.location.href = `explore.php?q=${encodeURIComponent(si.value)}`;
    });
  });
</script>
</body>
</html>