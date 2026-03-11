<?php
require_once '../config.php';
require_once '../php/objects/User.php';
require_once '../php/objects/Condition.php';
require_once '../php/objects/Category.php';
require_once '../php/objects/Listing.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$condition  = new Condition();
$conditions = $condition->getAllConditions();
$Category   = new Category();
$categories = $Category->getAllCategories();

$error   = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name']         ?? '');
    $price        = $_POST['price']             ?? '';
    $description  = trim($_POST['description']  ?? '');
    $condition_id = (int)($_POST['condition_id'] ?? 0);
    $category_id  = (int)($_POST['category_id']  ?? 0);

    if (!$name) {
        $error = 'Please enter a title.';
    } elseif (!is_numeric($price) || (float)$price <= 0) {
        $error = 'Please enter a valid price.';
    } elseif (!$condition_id) {
        $error = 'Please select a condition.';
    } else {
        $listing               = new Listing();
        $listing->name         = $name;
        $listing->price        = (float)$price;
        $listing->description  = $description;
        $listing->condition_id = $condition_id;
        $listing->category_id  = $category_id ?: null;
        $listing->seller_id    = $_SESSION['user_id'];

        if ($listing->insert()) {
            $listingId = $listing->id;

            // Use LISTING_IMG_DIR from config.php
            if (!is_dir(LISTING_IMG_DIR)) @mkdir(LISTING_IMG_DIR, 0755, true);

            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $maxSize = 8 * 1024 * 1024;

            if (!empty($_FILES['photos']['name'][0])) {
                $files = $_FILES['photos'];
                $count = min(count($files['name']), 8);
                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK)   continue;
                    if ($files['size'][$i]  >  $maxSize)         continue;
                    if (!in_array($files['type'][$i], $allowed)) continue;

                    $ext      = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    $filename = uniqid("listing_{$listingId}_", true) . '.' . $ext;
                    $dest     = LISTING_IMG_DIR . $filename;

                    if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
                        // Store full web URL using LISTING_IMG_URL from config
                        $listing->addPhoto($listingId, LISTING_IMG_URL . $filename, $i);
                    }
                }
            }

            header("Location: listing.php?id={$listingId}");
            exit;
        } else {
            $error = 'Failed to create listing. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>ReVenta — Sell</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/styles.css">
<style>
  .sell-page        { max-width:560px; margin:0 auto; padding:20px 16px 100px; }
  .sell-page h2     { font-size:26px; font-weight:700; margin:0 0 4px; }
  .sell-subtitle    { color:#888; font-size:14px; margin:0 0 24px; }
  .upload-zone {
    border:2px dashed #ddd; border-radius:14px;
    padding:28px 16px; text-align:center; cursor:pointer;
    margin-bottom:12px; transition:border-color .2s,background .2s;
  }
  .upload-zone:hover, .upload-zone.drag-over { border-color:#111; background:#fafafa; }
  .upload-zone input[type=file] { display:none; }
  .upload-icon  { font-size:32px; margin-bottom:6px; }
  .upload-label { font-weight:600; font-size:15px; }
  .upload-hint  { color:#aaa; font-size:12px; margin-top:2px; }
  .photo-previews { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
  .photo-thumb { position:relative; width:78px; height:78px; border-radius:10px; overflow:hidden; flex-shrink:0; }
  .photo-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
  .photo-thumb .rm {
    position:absolute; top:3px; right:3px;
    background:rgba(0,0,0,.55); color:#fff; border:none; border-radius:50%;
    width:20px; height:20px; font-size:11px; cursor:pointer;
    display:flex; align-items:center; justify-content:center;
  }
  .photo-thumb.cover::after {
    content:'Cover'; position:absolute; bottom:0; left:0; right:0;
    background:rgba(0,0,0,.5); color:#fff; font-size:10px; text-align:center; padding:2px 0;
  }
  .form-group       { margin-bottom:18px; }
  .form-group label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; }
  .form-input {
    width:100%; padding:11px 14px; border:1.5px solid #e0e0e0; border-radius:10px;
    font-size:14px; font-family:inherit; background:#fff;
    box-sizing:border-box; outline:none; transition:border-color .15s;
  }
  .form-input:focus { border-color:#111; }
  .form-input.error { border-color:#e53935; }
  .form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  .price-input-wrap { position:relative; }
  .price-input-wrap .currency { position:absolute; left:13px; top:50%; transform:translateY(-50%); font-size:14px; color:#555; pointer-events:none; }
  .price-input-wrap .form-input { padding-left:26px; }
  .form-textarea { resize:vertical; min-height:90px; }
  .condition-pills { display:flex; gap:8px; flex-wrap:wrap; }
  .cond-pill {
    padding:7px 14px; border-radius:20px; border:1.5px solid #ddd;
    font-size:13px; cursor:pointer; transition:all .15s; user-select:none;
  }
  .cond-pill:hover  { border-color:#999; }
  .cond-pill.active { background:#111; color:#fff; border-color:#111; }
  .form-error { background:#ffeaea; border:1px solid #f5c6c6; color:#c0392b; padding:12px 16px; border-radius:10px; font-size:14px; margin-bottom:18px; }
  .field-error { color:#e53935; font-size:12px; margin-top:4px; display:none; }
  .btn-list {
    width:100%; padding:15px; background:#111; color:#fff; border:none; border-radius:12px;
    font-size:16px; font-weight:700; cursor:pointer; margin-top:8px;
    transition:opacity .15s; font-family:inherit;
  }
  .btn-list:hover { opacity:.85; }
  .btn-list:disabled { opacity:.5; cursor:not-allowed; }
</style>
</head>
<body>
<nav id="top-nav">
  <a href="../index.php" class="nav-logo">ReVenta<span>.</span></a>
  <div class="nav-search">
    <input type="text" id="search-input" placeholder="Search items, brands, sellers...">
  </div>
  <div class="nav-links">
    <a href="../index.php" class="nav-tab-link">Home</a>
    <a href="explore.php"  class="nav-tab-link">Explore</a>
    <a href="messages.php" class="nav-tab-link">Messages</a>
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="profile.php" class="nav-tab-link">Profile</a>
      <a href="../php/Utils/Logout.php" class="nav-tab-link">Logout</a>
    <?php else: ?>
      <a href="login.php"   class="nav-tab-link">Login</a>
    <?php endif; ?>
  </div>
  <a href="sell.php"><button class="btn-sell">+ Sell</button></a>
</nav>

<main id="app">
  <div class="sell-page">
    <h2>List an Item</h2>
    <p class="sell-subtitle">Fill in the details below to list your item for sale.</p>

    <?php if ($error): ?>
      <div class="form-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="sell-form" method="POST" enctype="multipart/form-data" action="sell.php">
      <!-- Photo upload -->
      <div class="upload-zone" id="upload-zone">
        <input type="file" id="photo-input" name="photos[]" accept="image/*" multiple>
        <div class="upload-icon">📷</div>
        <div class="upload-label">Add Photos</div>
        <div class="upload-hint">Up to 8 photos · JPG, PNG, WEBP · Max 8 MB each</div>
      </div>
      <div class="photo-previews" id="photo-previews"></div>

      <!-- Title -->
      <div class="form-group">
        <label for="f-title">Title *</label>
        <input type="text" id="f-title" name="name" class="form-input"
               placeholder="e.g. Vintage Levi's Denim Jacket" maxlength="50"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        <div class="field-error" id="err-title">Please enter a title.</div>
      </div>

      <!-- Price + Category -->
      <div class="form-row">
        <div class="form-group">
          <label for="f-price">Price *</label>
          <div class="price-input-wrap">
            <span class="currency">$</span>
            <input type="number" id="f-price" name="price" class="form-input price-input"
                   placeholder="0.00" min="0.01" step="0.01"
                   value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
          </div>
          <div class="field-error" id="err-price">Enter a valid price.</div>
        </div>
        <div class="form-group">
          <label for="f-category">Category</label>
          <select id="f-category" name="category_id" class="form-input">
            <option value="">Select category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat->id ?>"
                <?= (($_POST['category_id'] ?? '') == $cat->id) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat->name) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Condition -->
      <div class="form-group">
        <label>Condition *</label>
        <div class="condition-pills" id="condition-pills">
          <?php foreach ($conditions as $cond): ?>
            <div class="cond-pill <?= (($_POST['condition_id'] ?? '') == $cond->id) ? 'active' : '' ?>"
                 data-cond="<?= $cond->id ?>">
              <?= htmlspecialchars($cond->name) ?>
            </div>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="condition_id" id="condition-hidden"
               value="<?= (int)($_POST['condition_id'] ?? 0) ?>">
        <div class="field-error" id="err-condition">Please select a condition.</div>
      </div>

      <!-- Description -->
      <div class="form-group">
        <label for="f-desc">Description</label>
        <textarea id="f-desc" name="description" class="form-input form-textarea"
                  placeholder="Describe your item — size, colour, any flaws…"
                  maxlength="2000"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn-list" id="btn-list">List for Sale</button>
    </form>
  </div>
</main>

<nav id="bottom-nav">
  <a class="bottom-item" href="../index.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><div class="bottom-label">Home</div></a>
  <a class="bottom-item" href="explore.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div><div class="bottom-label">Explore</div></a>
  <a class="bottom-item sell-btn active" href="sell.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div><div class="bottom-label">Sell</div></a>
  <a class="bottom-item" href="likes.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div><div class="bottom-label">Likes</div></a>
  <a class="bottom-item" href="messages.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div><div class="bottom-label">Messages</div></a>
  <a class="bottom-item" href="profile.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div class="bottom-label">Profile</div></a>
</nav>

<script>
let selectedFiles = [];
const uploadZone   = document.getElementById('upload-zone');
const photoInput   = document.getElementById('photo-input');
const previewStrip = document.getElementById('photo-previews');

uploadZone.addEventListener('click', () => photoInput.click());
uploadZone.addEventListener('dragover',  e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
uploadZone.addEventListener('drop', e => {
  e.preventDefault(); uploadZone.classList.remove('drag-over');
  addFiles([...e.dataTransfer.files]);
});
photoInput.addEventListener('change', () => addFiles([...photoInput.files]));

function addFiles(files) {
  const imgs = files.filter(f => f.type.startsWith('image/'));
  const rem  = 8 - selectedFiles.length;
  if (rem <= 0) return;
  selectedFiles = [...selectedFiles, ...imgs.slice(0, rem)];
  syncInput(); renderPreviews();
}
function removeFile(i) { selectedFiles.splice(i, 1); syncInput(); renderPreviews(); }
function syncInput() {
  const dt = new DataTransfer();
  selectedFiles.forEach(f => dt.items.add(f));
  photoInput.files = dt.files;
}
function renderPreviews() {
  previewStrip.innerHTML = '';
  selectedFiles.forEach((file, i) => {
    const url   = URL.createObjectURL(file);
    const thumb = document.createElement('div');
    thumb.className = 'photo-thumb' + (i === 0 ? ' cover' : '');
    thumb.innerHTML = `<img src="${url}" alt=""><button type="button" class="rm">✕</button>`;
    thumb.querySelector('.rm').addEventListener('click', () => removeFile(i));
    previewStrip.appendChild(thumb);
  });
  uploadZone.style.display = selectedFiles.length >= 8 ? 'none' : '';
}

document.getElementById('condition-pills').addEventListener('click', e => {
  const pill = e.target.closest('.cond-pill');
  if (!pill) return;
  document.querySelectorAll('.cond-pill').forEach(p => p.classList.remove('active'));
  pill.classList.add('active');
  document.getElementById('condition-hidden').value = pill.dataset.cond;
  document.getElementById('err-condition').style.display = 'none';
});

document.getElementById('sell-form').addEventListener('submit', e => {
  let ok = true;
  const title = document.getElementById('f-title').value.trim();
  const errT  = document.getElementById('err-title');
  if (!title) { document.getElementById('f-title').classList.add('error'); errT.style.display = 'block'; ok = false; }
  else        { document.getElementById('f-title').classList.remove('error'); errT.style.display = 'none'; }

  const price = parseFloat(document.getElementById('f-price').value);
  const errP  = document.getElementById('err-price');
  if (!price || price <= 0) { document.getElementById('f-price').classList.add('error'); errP.style.display = 'block'; ok = false; }
  else                      { document.getElementById('f-price').classList.remove('error'); errP.style.display = 'none'; }

  const cond = document.getElementById('condition-hidden').value;
  const errC = document.getElementById('err-condition');
  if (!cond || cond === '0') { errC.style.display = 'block'; ok = false; }
  else                       { errC.style.display = 'none'; }

  if (!ok) { e.preventDefault(); return; }
  const btn = document.getElementById('btn-list');
  btn.disabled = true; btn.textContent = 'Listing…';
});

document.getElementById('search-input')?.addEventListener('keydown', e => {
  if (e.key === 'Enter') window.location.href = `explore.php?q=${encodeURIComponent(e.target.value)}`;
});
</script>
</body>
</html>