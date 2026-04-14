<?php
require_once '../php/objects/User.php';
require_once '../php/objects/Listing.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userObj = new User();
$userObj->populate($_SESSION['user_id']);

$message = "";
$messageType = "";

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userObj->full_name    = trim($_POST['full_name']    ?? '');
    $userObj->bio          = trim($_POST['bio']          ?? '');
    $userObj->phone_number = trim($_POST['phone_number'] ?? '');
    $userObj->adress       = trim($_POST['address']      ?? '');

    // Handle avatar upload
    if (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $mime = mime_content_type($_FILES['avatar']['tmp_name']);
        if (in_array($mime, $allowed)) {
            $uploadDir = '../uploads/avatars/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $_SESSION['user_id'] . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $filename)) {
                $userObj->profile_picture = 'uploads/avatars/' . $filename;
            }
        }
    }

    if ($userObj->updateProfile()) {
        $message = "Profile updated successfully.";
        $messageType = "success";
        $_SESSION['username'] = $userObj->username;
    } else {
        $message = "Failed to update profile.";
        $messageType = "error";
    }
}

// Get user's listings
$listingObj = new Listing();
$listings   = $listingObj->getListingsBySeller($_SESSION['user_id']);
$totalSold  = count(array_filter($listings, fn($l) => $l['is_sold']));
$active     = array_filter($listings, fn($l) => !$l['is_sold']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Profile — ReVènta</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/pages.css">
<style>
.profile-bio-input {
  width:100%; font-family:var(--sans); font-size:13px; font-weight:300;
  letter-spacing:.04em; color:var(--black); border:1px solid #d0d0d0;
  padding:11px 14px; outline:none; resize:vertical; min-height:80px;
  transition:border-color .2s; border-radius:0;
}
.profile-bio-input:focus { border-color:var(--black); }
.profile-save-btn {
  font-family:var(--sans); font-size:10px; font-weight:400; letter-spacing:.22em;
  text-transform:uppercase; color:var(--white); background:var(--black);
  border:none; padding:13px 32px; cursor:pointer; transition:opacity .2s; margin-top:20px;
}
.profile-save-btn:hover { opacity:.75; }
.profile-form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.profile-form-label { font-size:9px; font-weight:500; letter-spacing:.18em; text-transform:uppercase; color:var(--mid); margin-bottom:8px; display:block; }
.profile-form-input { width:100%; font-family:var(--sans); font-size:13px; font-weight:300; color:var(--black); border:1px solid #d0d0d0; padding:11px 14px; outline:none; border-radius:0; transition:border-color .2s; }
.profile-form-input:focus { border-color:var(--black); }
.edit-section { margin-top:40px; border-top:1px solid var(--light); padding-top:32px; }
.edit-section-title { font-family:var(--serif); font-size:22px; font-weight:300; margin-bottom:24px; }
.logout-link { font-size:10px; font-weight:400; letter-spacing:.18em; text-transform:uppercase; color:var(--mid); text-decoration:none; border-bottom:1px solid transparent; transition:color .2s,border-color .2s; }
.logout-link:hover { color:var(--black); border-color:var(--black); }
</style>
</head>
<body class="profile-body">

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
    <a href="explore.php">Explore</a>
    <a href="profile.php" class="active">Profile</a>
    <a href="messages.php">Messages</a>
  </div>
  <button class="nav-hamburger" id="navHamburger"><span></span><span></span><span></span></button>
</nav>
<div class="nav-mobile-menu" id="navMobileMenu">
  <a href="../index.php">Home</a>
  <a href="explore.php">Explore</a>
  <a href="mens.php">Men</a>
  <a href="womens.php">Women</a>
  <a href="sell.php">Sell+</a>
  <a href="profile.php">Profile</a>
  <a href="messages.php">Messages</a>
  <a href="likes.php">My Likes</a>
  <a href="../php/Utils/Logout.php">Logout</a>
</div>

<div class="profile-wrap">

  <?php if ($message): ?>
    <div style="padding:10px 14px;font-size:12px;letter-spacing:.06em;border:1px solid;margin-bottom:24px;
         color:<?= $messageType==='error'?'#c0392b':'#1a7a40' ?>;
         border-color:<?= $messageType==='error'?'#c0392b':'#1a7a40' ?>;
         background:<?= $messageType==='error'?'#fdf2f1':'#edf7f2' ?>;">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <!-- Avatar + username -->
  <div class="profile-avatar-wrap">
    <form method="POST" action="profile.php" enctype="multipart/form-data" id="avatarForm">
      <div class="profile-avatar-circle" title="Click to change photo">
        <?php if (!empty($userObj->profile_picture)): ?>
          <img src="../<?= htmlspecialchars($userObj->profile_picture) ?>" alt="Avatar">
        <?php else: ?>
          <svg viewBox="0 0 70 70" fill="none">
            <circle cx="35" cy="22" r="14" fill="white"/>
            <ellipse cx="35" cy="56" rx="24" ry="14" fill="white"/>
          </svg>
        <?php endif; ?>
        <input type="file" name="avatar" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
      </div>
      <!-- hidden fields to preserve other data on avatar-only submit -->
      <input type="hidden" name="full_name"    value="<?= htmlspecialchars($userObj->full_name ?? '') ?>">
      <input type="hidden" name="bio"          value="<?= htmlspecialchars($userObj->bio ?? '') ?>">
      <input type="hidden" name="phone_number" value="<?= htmlspecialchars($userObj->phone_number ?? '') ?>">
      <input type="hidden" name="address"      value="<?= htmlspecialchars($userObj->adress ?? '') ?>">
    </form>

    <div class="profile-username-row">
      <span style="font-family:var(--sans);font-size:14px;font-weight:500;letter-spacing:.08em;">
        @<?= htmlspecialchars($userObj->username) ?>
      </span>
      <a href="settings.php" class="nav-settings" aria-label="Settings">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="3"/>
          <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
        </svg>
      </a>
    </div>
    <div style="margin-top:6px;">
      <a href="likes.php" style="font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--mid);text-decoration:none;border-bottom:1px solid transparent;transition:color .2s,border-color .2s;" onmouseover="this.style.color='#0a0a0a';this.style.borderColor='#0a0a0a'" onmouseout="this.style.color='';this.style.borderColor=''">My Likes</a>
      &nbsp;·&nbsp;
      <a href="../php/Utils/Logout.php" class="logout-link">Logout</a>
    </div>
  </div>

  <!-- Stats -->
  <div class="profile-stats">
    <div>
      <p class="profile-stat-label">Listings</p>
      <div class="profile-stat-value-row"><h1><?= count($listings) ?></h1></div>
    </div>
    <div>
      <p class="profile-stat-label">Active</p>
      <div class="profile-stat-value-row"><h1><?= count($active) ?></h1></div>
    </div>
    <div>
      <p class="profile-stat-label">Sold</p>
      <div class="profile-stat-value-row"><h1><?= $totalSold ?></h1></div>
    </div>
    <div>
      <p class="profile-stat-label">Member Since</p>
      <div class="profile-stat-value-row" style="font-family:var(--serif);font-size:14px;font-weight:300;">
        <?= !empty($userObj->birthday) ? date('Y', strtotime($userObj->birthday)) : '—' ?>
      </div>
    </div>
  </div>

  <!-- Active listings grid -->
  <?php if (!empty($listings)): ?>
    <div class="profile-tabs" style="margin-top:40px;">
      <span class="profile-tab active">My Listings</span>
    </div>
    <div class="profile-items-grid">
      <?php foreach ($listings as $item): ?>
        <div class="profile-item-card" onclick="window.location='listing.php?id=<?= $item['id'] ?>'">
          <div class="profile-item-image">
            <?php if (!empty($item['cover_photo'])): ?>
              <img style="width:100%;height:100%;object-fit:cover;display:block;"
                   src="../<?= htmlspecialchars($item['cover_photo']) ?>"
                   alt="<?= htmlspecialchars($item['name']) ?>"
                   loading="lazy">
            <?php else: ?>
              <div class="profile-item-image-inner <?= 's'.((($item['id']-1)%8)+1) ?>"></div>
            <?php endif; ?>
          </div>
          <div class="profile-item-info">
            <div class="profile-item-name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="profile-item-price">
              $<?= number_format((float)$item['price'], 2) ?>
              <?php if ($item['is_sold']): ?>
                <span class="profile-sold-badge">Sold</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<script>
const ham = document.getElementById('navHamburger');
const menu = document.getElementById('navMobileMenu');
ham.addEventListener('click', () => { ham.classList.toggle('open'); menu.classList.toggle('open'); });
</script>
<script src="../js/main.js"></script>
</body>
</html>
