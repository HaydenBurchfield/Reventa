<?php
require_once '../php/objects/User.php';
require_once '../php/objects/Listing.php';
require_once '../php/objects/Category.php';
require_once '../php/objects/Condition.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$Category   = new Category();
$categories = $Category->getAllCategories();

$conditionObj = new Condition();
$conditions   = $conditionObj->getAllConditions();

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price']    ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $condition_id = (int)($_POST['condition_id'] ?? 0);

    if (!$name || !$price || !$condition_id) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } else {
        $listingObj = new Listing();
        $listingObj->name        = $name;
        $listingObj->description = $description;
        $listingObj->price       = $price;
        $listingObj->category_id = $category_id ?: null;
        $listingObj->condition_id = $condition_id;
        $listingObj->seller_id   = $_SESSION['user_id'];
        $listingObj->view_count  = 0;

        if ($listingObj->insert()) {
            // Handle photo uploads
            if (!empty($_FILES['photos']['name'][0])) {
                $uploadDir = '../uploads/listings/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
                $sort = 0;
                foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
                    if (!$tmp || $_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $mime = mime_content_type($tmp);
                    if (!in_array($mime, $allowed)) continue;
                    $ext  = pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION);
                    $filename = uniqid('listing_') . '.' . $ext;
                    if (move_uploaded_file($tmp, $uploadDir . $filename)) {
                        $listingObj->addPhoto($listingObj->id, '/uploads/listings/' . $filename, $sort++);
                    }
                }
            }
            header("Location: listing.php?id=" . $listingObj->id);
            exit;
        } else {
            $message = "Something went wrong. Please try again.";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Sell — ReVènta</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/sell.css">
</head>
<body class="page-body">

<nav>
  <div class="nav-left">
    <a href="mens.php">Men</a>
    <a href="womens.php">Women</a>
    <a href="kids.php">Kids</a>
    <a href="sell.php" class="nav-sell active">Sell+</a>
  </div>
  <a href="../index.php" class="nav-logo">Re<span id="theV">V</span>è<span>nta</span></a>
  <div class="nav-right">
    <a href="../index.php">Home</a>
    <a href="explore.php">Explore</a>
    <a href="profile.php">Profile</a>
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
  <a href="../php/Utils/Logout.php">Logout</a>
</div>

<div class="sell-page-header">
  <h1>List an Item</h1>
  <p>Upload photos &amp; details — go live in minutes</p>
  <?php if ($message): ?>
    <div style="margin-top:16px;padding:10px 14px;font-size:12px;letter-spacing:.06em;border:1px solid;
         color:<?= $messageType==='error'?'#c0392b':'#1a7a40' ?>;
         border-color:<?= $messageType==='error'?'#c0392b':'#1a7a40' ?>;
         background:<?= $messageType==='error'?'#fdf2f1':'#edf7f2' ?>;">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>
</div>

<form method="POST" action="sell.php" enctype="multipart/form-data">
<div class="sell-wrapper">

  <!-- LEFT: UPLOAD ZONE -->
  <div>
    <div class="upload-zone" id="uploadZone">
      <input type="file" id="fileInput" name="photos[]" accept="image/*" multiple>
      <svg class="upload-icon" viewBox="0 0 40 40" fill="none">
        <rect x="4" y="4" width="32" height="32" rx="1" stroke="#0a0a0a" stroke-width="1.2"/>
        <line x1="20" y1="13" x2="20" y2="27" stroke="#0a0a0a" stroke-width="1.2"/>
        <polyline points="14,19 20,13 26,19" stroke="#0a0a0a" stroke-width="1.2" fill="none"/>
      </svg>
      <button class="upload-btn" type="button">Upload Photos</button>
      <span class="upload-hint">JPG, PNG — up to 8 images</span>
    </div>
  </div>

  <!-- RIGHT: FORM -->
  <div class="sell-form">
    <label class="form-section-label">About the Piece</label>

    <label class="field-label" for="name">Title *</label>
    <input type="text" id="name" name="name" required
           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
           placeholder="e.g. Acne Studios Oversized Denim Jacket">

    <label class="field-label" for="desc">Description</label>
    <textarea id="desc" name="description" placeholder="Describe the item — fabric, fit, any wear…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

    <label class="form-section-label">Info</label>

    <label class="field-label" for="category_id">Category</label>
    <select id="category_id" name="category_id">
      <option value="" disabled selected>— Select —</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat->id ?>" <?= (($_POST['category_id'] ?? '') == $cat->id) ? 'selected' : '' ?>>
          <?= htmlspecialchars($cat->name) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label class="field-label" for="condition_id">Condition *</label>
    <select id="condition_id" name="condition_id" required>
      <option value="" disabled selected>— Select —</option>
      <?php foreach ($conditions as $cond): ?>
        <option value="<?= $cond->id ?>" <?= (($_POST['condition_id'] ?? '') == $cond->id) ? 'selected' : '' ?>>
          <?= htmlspecialchars($cond->name) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <div class="price-post-row">
      <div class="price-input-wrap">
        <span class="price-currency">US$</span>
        <input type="number" id="price" name="price" placeholder="0.00" min="0" step="0.01" required
               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
      </div>
      <button class="btn-post" type="submit">Post Listing</button>
    </div>
  </div>

</div>
</form>

<script>
const zone = document.getElementById('uploadZone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
zone.addEventListener('drop', e => {
  e.preventDefault(); zone.classList.remove('drag-over');
  document.getElementById('fileInput').files = e.dataTransfer.files;
  showPreviews(e.dataTransfer.files);
});
document.getElementById('fileInput').addEventListener('change', e => showPreviews(e.target.files));

function showPreviews(files) {
  if (!files.length) return;
  const arr = Array.from(files).slice(0, 8);

  // Remove any existing preview grid without touching the file input
  const existing = zone.querySelector('.upload-previews');
  if (existing) existing.remove();

  // Hide the upload prompt icons
  zone.querySelector('.upload-icon')?.remove();
  zone.querySelector('.upload-btn')?.remove();
  zone.querySelector('.upload-hint')?.remove();

  const grid = document.createElement('div');
  grid.className = 'upload-previews';
  zone.appendChild(grid);

  arr.forEach(f => {
    const url = URL.createObjectURL(f);
    const img = document.createElement('img');
    img.src = url;
    img.className = 'preview-thumb';
    img.style.cssText = 'object-fit:cover;width:100%;height:100%;';
    grid.appendChild(img);
  });
}

const ham = document.getElementById('navHamburger');
const navMenu = document.getElementById('navMobileMenu');
ham.addEventListener('click', () => { ham.classList.toggle('open'); navMenu.classList.toggle('open'); });
</script>
 <script src="../js/main.js"></script>
</body>
</html>