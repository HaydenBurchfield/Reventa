<?php 

  require_once '../php/objects/User.php';
  session_start();  

  // $name = $_SESSION['username'] ?? null;
  // $user = new User();
  // if ($name) {
  //   $user->populate($_SESSION['user_id']);
  // } else {
  //   header('Location: login.php');
  //   exit();
  // }
  


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>THRIFT — Profile</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>

<nav id="top-nav">
  <a href="index.php" class="nav-logo">THRIFT<span>.</span></a>
  <div class="nav-search"><input type="text" id="search-input" placeholder="Search items, brands, sellers..."></div>
  <div class="nav-links">
    <a href="../index.php" class="nav-tab-link">Home</a>
    <a href="../pages/explore.php" class="nav-tab-link">Explore</a>
    <a href="../pages/messages.php" class="nav-tab-link">Messages</a>
    <a href="../pages/profile.php" class="nav-tab-link active">Profile</a>
  </div>
  <a href="../pages/sell.php"><button class="btn-sell">+ Sell</button></a>
</nav>

<main id="app">
  <div class="profile-cover"></div>
  <div class="profile-info">
    <div class="profile-avatar"><img src="https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=200&q=80" alt=""></div>
    <div class="profile-meta">
      <h3>@closetbyluna</h3>
      <p>Vintage obsessed. Based in NYC. 🌿</p>
      <div class="profile-stats" >
        <div class="stat"><span>309</span>Items</div>
        <div class="stat"><span>1.2k</span>Followers</div>
        <div class="stat"><span>98%</span>Rating</div>
      </div>
    </div>
    <button class="btn-edit-profile">Edit Profile</button>
  </div>
  <div class="profile-tabs">
    <div class="profile-tab active" data-ptab="selling">Selling</div>
    <div class="profile-tab" data-ptab="sold">Sold</div>
    <div class="profile-tab" data-ptab="reviews">Reviews</div>
  </div>
  <div class="product-grid" id="profile-grid"></div>
</main>

<nav id="bottom-nav">
  <a class="bottom-item" href="../index.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><div class="bottom-label">Home</div></a>
  <a class="bottom-item active" href="../pages/explore.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div><div class="bottom-label">Explore</div></a>
  <a class="bottom-item" href="../pages/likes.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div><div class="bottom-label">Likes</div></a>
  <a class="bottom-item" href="../pages/messages.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div><div class="bottom-label">Messages</div></a>
  <a class="bottom-item" href="../pages/profile.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div class="bottom-label">Profile</div></a>
</nav>

<script src="../assets/js/data.js"></script>
<script src="../assets/js/modal.js"></script>
<script src="../assets/js/app.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    renderGrid('profile-grid', PRODUCTS.slice(0, 6));

    document.querySelectorAll('.profile-tab').forEach(t =>
      t.addEventListener('click', () => {
        document.querySelectorAll('.profile-tab').forEach(x => x.classList.remove('active'));
        t.classList.add('active');
        if (t.dataset.ptab === 'sold') renderGrid('profile-grid', PRODUCTS.slice(2, 5));
        else if (t.dataset.ptab === 'reviews') {
          document.getElementById('profile-grid').innerHTML = `<div class="empty-state" style="grid-column:1/-1"><div class="empty-icon">⭐</div><div class="empty-title">All 5-star reviews</div><div class="empty-sub">98% positive rating from 204 buyers</div></div>`;
        } else {
          renderGrid('profile-grid', PRODUCTS.slice(0, 6));
        }
      })
    );

    const si = document.getElementById('search-input');
    si?.addEventListener('keydown', e => {
      if (e.key === 'Enter') window.location.href = `explore.php?q=${encodeURIComponent(si.value)}`;
    });
  });
</script>
</body>
</html>