<?php
require_once '../config.php';
require_once '../php/objects/User.php';
require_once '../php/objects/Listing.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user = new User();
$user->populate($_SESSION['user_id']);

$error   = '';
$success = '';

// ── Handle POST — avatar upload only ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['profile_picture'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if ($file['size'] <= 5 * 1024 * 1024 && in_array($file['type'], $allowed)) {
            if (!is_dir(AVATAR_DIR)) @mkdir(AVATAR_DIR, 0755, true);

            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $dest     = AVATAR_DIR . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                if (!empty($user->profile_picture)) {
                    $oldRelative = str_replace(AVATAR_URL, '', $user->profile_picture);
                    $oldPath     = AVATAR_DIR . basename($oldRelative);
                    if (file_exists($oldPath)) @unlink($oldPath);
                }
                $user->profile_picture = AVATAR_URL . $filename;
                if ($user->updateProfile()) {
                    $success = 'Photo updated!';
                    $user->populate($_SESSION['user_id']);
                } else {
                    $error = 'Failed to save photo.';
                }
            } else {
                $error = 'Could not save image. Make sure uploads/avatars/ is writable (chmod 755).';
            }
        } else {
            $error = 'Invalid image. Max 5 MB, JPG/PNG/WEBP/GIF only.';
        }
    }
}

// Avatar src — use stored URL or fallback to generated avatar
function avatarSrc(?string $pic, string $username): string {
    if (!empty($pic)) return htmlspecialchars($pic);
    return 'https://ui-avatars.com/api/?name=' . urlencode($username ?: 'U')
         . '&background=111&color=fff&size=200';
}

// Load listings
$listingObj     = new Listing();
$all            = $listingObj->getListingsBySeller($_SESSION['user_id']);
$activeListings = array_values(array_filter($all, fn($l) => !$l['is_sold']));
$soldListings   = array_values(array_filter($all, fn($l) =>  $l['is_sold']));

function renderCards(array $listings, bool $sold = false): string {
    if (empty($listings)) {
        $msg = $sold
            ? 'Nothing sold yet.'
            : 'No active listings. <a href="sell.php" style="color:#111;font-weight:600">List your first item →</a>';
        return '<div class="empty-state"><div class="empty-icon">📦</div><p>' . $msg . '</p></div>';
    }
    $html = '';
    foreach ($listings as $item) {
        $badge = $sold ? '<span class="card-badge-sold">SOLD</span>' : '';
        $img   = !empty($item['cover_photo'])
            ? '<img class="card-img" src="' . htmlspecialchars($item['cover_photo']) . '" loading="lazy" alt="">'
            : '<div class="card-placeholder">📦</div>';
        $html .= '<a class="product-card" href="listing.php?id=' . (int)$item['id'] . '">'
               . $badge . $img
               . '<div class="card-body">'
               . '<div class="card-title">' . htmlspecialchars($item['name'])          . '</div>'
               . '<div class="card-price">$' . number_format((float)$item['price'], 2) . '</div>'
               . '<div class="card-meta">'   . htmlspecialchars($item['condition_name'] ?? '') . '</div>'
               . '</div></a>';
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>ReVenta — Profile</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/styles.css">
<style>
  .profile-cover { height:140px; background:linear-gradient(135deg,#111 0%,#555 100%); }
  .profile-info  { padding:0 16px 16px; }
  .profile-top-row { display:flex; align-items:flex-end; justify-content:space-between; gap:12px; }
  .profile-avatar {
    width:86px; height:86px; border-radius:50%; border:3px solid #fff;
    overflow:hidden; margin-top:-43px; background:#eee;
    position:relative; cursor:pointer; flex-shrink:0; display:block;
  }
  .profile-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
  .avatar-overlay {
    position:absolute; inset:0; background:rgba(0,0,0,.45);
    display:flex; align-items:center; justify-content:center;
    opacity:0; transition:opacity .2s; border-radius:50%;
    color:#fff; font-size:10px; font-weight:600; text-align:center; line-height:1.4;
  }
  .profile-avatar:hover .avatar-overlay { opacity:1; }
  #avatar-input { display:none; }
  .profile-meta { flex:1; padding-top:10px; }
  .profile-meta h3 { margin:0 0 2px; font-size:20px; font-weight:700; }
  .profile-meta .bio-text { color:#666; font-size:13px; margin:0 0 8px; }
  .profile-stats { display:flex; gap:20px; margin-top:8px; }
  .stat span { font-weight:700; font-size:17px; display:block; }
  .stat { font-size:11px; color:#888; }
  .btn-settings-icon {
    width:36px; height:36px; border-radius:50%; border:1.5px solid #e0e0e0;
    background:#fff; display:flex; align-items:center; justify-content:center;
    cursor:pointer; align-self:flex-start; margin-top:10px;
    text-decoration:none; color:#555; transition:all .15s; flex-shrink:0;
  }
  .btn-settings-icon:hover { border-color:#111; background:#111; color:#fff; }
  .alert { padding:10px 14px; border-radius:9px; font-size:13px; margin:0 16px 12px; }
  .alert-success { background:#eafaf1; color:#1e8449; border:1px solid #a9dfbf; }
  .alert-error   { background:#ffeaea; color:#c0392b; border:1px solid #f5c6c6; }
  .profile-tabs { display:flex; border-bottom:1.5px solid #eee; padding:0 16px; gap:4px; }
  .profile-tab { padding:10px 16px; font-size:14px; font-weight:500; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-1.5px; transition:all .15s; color:#888; }
  .profile-tab.active { color:#111; border-bottom-color:#111; font-weight:700; }
  .product-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(155px,1fr)); gap:14px; padding:14px 16px 100px; }
  .product-card { background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.08); transition:transform .15s,box-shadow .15s; text-decoration:none; display:block; color:inherit; position:relative; }
  .product-card:hover { transform:translateY(-2px); box-shadow:0 4px 16px rgba(0,0,0,.12); }
  .card-img { width:100%; aspect-ratio:1/1; object-fit:cover; background:#f0f0f0; display:block; }
  .card-placeholder { width:100%; aspect-ratio:1/1; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#ccc; font-size:30px; }
  .card-body  { padding:10px 12px 12px; }
  .card-title { font-weight:500; font-size:14px; margin:0 0 3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .card-price { font-weight:700; font-size:15px; }
  .card-meta  { font-size:11px; color:#999; margin-top:3px; }
  .card-badge-sold { position:absolute; top:8px; left:8px; background:rgba(0,0,0,.65); color:#fff; font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; }
  .empty-state { grid-column:1/-1; text-align:center; padding:50px 20px; color:#aaa; }
  .empty-state .empty-icon { font-size:36px; margin-bottom:8px; }
  .empty-state p { margin:0; font-size:14px; }
</style>
</head>
<body>
<nav id="top-nav">
  <div class="nav-logo"><a href="../index.php"><img src="../assets/img/logo.png" alt="ReVenta Logo" id="logo"></a></div>
  <div class="nav-search">
    <form method="GET" action="explore.php" style="margin:0;width:100%">
      <input type="text" name="q" placeholder="Search items, brands, sellers...">
    </form>
  </div>
  <div class="nav-links">
    <a href="../index.php" class="nav-tab-link">Home</a>
    <a href="explore.php"  class="nav-tab-link">Explore</a>
    <a href="messages.php" class="nav-tab-link">Messages</a>
    <a href="profile.php"  class="nav-tab-link active">Profile</a>
    <a href="likes.php"    class="nav-tab-link">My Likes</a>
    <a href="../php/Utils/Logout.php" class="nav-tab-link">Logout</a>
  </div>
  <a href="sell.php"><button class="btn-sell">+ Sell</button></a>
</nav>

<main id="app">
  <div class="profile-cover"></div>

  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST" enctype="multipart/form-data" action="profile.php" id="avatar-form">
    <input type="file" name="profile_picture" id="avatar-input" accept="image/*">
  </form>

  <div class="profile-info">
    <div class="profile-top-row">
      <label class="profile-avatar" for="avatar-input" title="Change profile photo">
        <img src="<?= avatarSrc($user->profile_picture, $user->username ?? '') ?>"
             alt="avatar" id="avatar-img">
        <div class="avatar-overlay">📷<br>Change</div>
      </label>
      <div class="profile-meta" style="flex:1">
        <h3>@<?= htmlspecialchars($user->username ?? '') ?></h3>
        <?php if (!empty($user->full_name)): ?>
          <div style="font-size:13px;color:#444;margin-bottom:2px"><?= htmlspecialchars($user->full_name) ?></div>
        <?php endif; ?>
        <p class="bio-text"><?= htmlspecialchars($user->bio ?: 'No bio yet.') ?></p>
        <div class="profile-stats">
          <div class="stat"><span><?= count($all) ?></span>Items</div>
          <div class="stat"><span><?= count($activeListings) ?></span>Active</div>
          <div class="stat"><span><?= count($soldListings) ?></span>Sold</div>
        </div>
      </div>
      <a class="btn-settings-icon" href="settings.php" title="Settings" aria-label="Settings">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      </a>
    </div>
  </div>

  <div class="profile-tabs">
    <div class="profile-tab active" data-ptab="selling">Selling (<?= count($activeListings) ?>)</div>
    <div class="profile-tab"        data-ptab="sold">Sold (<?= count($soldListings) ?>)</div>
  </div>
  <div class="product-grid" id="profile-grid">
    <?= renderCards($activeListings, false) ?>
  </div>
</main>

<nav id="bottom-nav">
  <a class="bottom-item" href="../index.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><div class="bottom-label">Home</div></a>
  <a class="bottom-item" href="explore.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div><div class="bottom-label">Explore</div></a>
  <a class="bottom-item sell-btn" href="sell.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div><div class="bottom-label">Sell</div></a>
  <a class="bottom-item" href="likes.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div><div class="bottom-label">Likes</div></a>
  <a class="bottom-item" href="messages.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div><div class="bottom-label">Messages</div></a>
  <a class="bottom-item active" href="profile.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div class="bottom-label">Profile</div></a></nav>

<script>
const activeCards = <?= json_encode($activeListings) ?>;
const soldCards   = <?= json_encode($soldListings) ?>;

document.getElementById('avatar-input').addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => { document.getElementById('avatar-img').src = e.target.result; };
  reader.readAsDataURL(file);
  document.getElementById('avatar-form').submit();
});

function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function renderGrid(items, isSold) {
  const grid = document.getElementById('profile-grid');
  if (!items.length) {
    grid.innerHTML = `<div class="empty-state"><div class="empty-icon">📦</div><p>${
      isSold ? 'Nothing sold yet.' : 'No active listings. <a href="sell.php" style="color:#111;font-weight:600">List your first item →</a>'
    }</p></div>`;
    return;
  }
  grid.innerHTML = items.map(item => {
    const badge = isSold ? '<span class="card-badge-sold">SOLD</span>' : '';
    const img   = item.cover_photo
      ? `<img class="card-img" src="${escHtml(item.cover_photo)}" loading="lazy" alt="">`
      : `<div class="card-placeholder">📦</div>`;
    return `<a class="product-card" href="listing.php?id=${item.id}">${badge}${img}
      <div class="card-body">
        <div class="card-title">${escHtml(item.name)}</div>
        <div class="card-price">$${parseFloat(item.price).toFixed(2)}</div>
        <div class="card-meta">${escHtml(item.condition_name||'')}</div>
      </div></a>`;
  }).join('');
}
document.querySelectorAll('.profile-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    renderGrid(tab.dataset.ptab === 'sold' ? soldCards : activeCards, tab.dataset.ptab === 'sold');
  });
});
</script>
</body>
</html>