<?php
require_once '../php/objects/User.php';
require_once '../php/objects/Listing.php';
require_once '../php/objects/Chat.php';
session_start();

$listingId = (int)($_GET['id'] ?? 0);
if (!$listingId) { header('Location: explore.php'); exit; }

$listingObj = new Listing();
$item = $listingObj->getListingById($listingId);
if (!$item) { header('Location: explore.php'); exit; }

$currentUserId = $_SESSION['user_id'] ?? null;
$isSeller      = $currentUserId && $currentUserId == $item['seller_id'];
$isLoggedIn    = (bool)$currentUserId;

// ── Handle "Message Seller" button ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$isLoggedIn) { header('Location: login.php'); exit; }

    if ($_POST['action'] === 'message') {
        $chat   = new Chat();
        $chatId = $chat->getOrCreateChat($listingId, $currentUserId, $item['seller_id']);
        if ($chatId) {
            // Send optional initial message
            $msg = trim($_POST['initial_message'] ?? '');
            if ($msg) $chat->sendMessage($chatId, $currentUserId, $msg);
            header("Location: messages.php?chat=" . $chatId);
            exit;
        }
    }

    if ($_POST['action'] === 'mark_sold' && $isSeller) {
        $listingObj->markSold($listingId);
        header("Location: listing.php?id=$listingId&sold=1");
        exit;
    }
}

function sellerAvatar($item): string {
    if (!empty($item['seller_avatar'])) return htmlspecialchars($item['seller_avatar']);
    return 'https://ui-avatars.com/api/?name=' . urlencode($item['seller_username'] ?? 'U')
         . '&background=111&color=fff&size=80';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>ReVenta — <?= htmlspecialchars($item['name']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/styles.css">
<style>
  .listing-page { max-width: 680px; margin: 0 auto; padding-bottom: 120px; }

  /* Photo gallery */
  .gallery { position: relative; background: #f0f0f0; aspect-ratio: 1/1; overflow: hidden; }
  .gallery-main { width: 100%; height: 100%; object-fit: cover; display: block; }
  .gallery-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 60px; color: #ccc; }
  .gallery-prev, .gallery-next {
    position: absolute; top: 50%; transform: translateY(-50%);
    background: rgba(255,255,255,.85); border: none; border-radius: 50%;
    width: 36px; height: 36px; font-size: 16px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 8px rgba(0,0,0,.15); transition: background .15s;
  }
  .gallery-prev { left: 12px; }
  .gallery-next { right: 12px; }
  .gallery-prev:hover, .gallery-next:hover { background: #fff; }
  .gallery-dots { position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); display: flex; gap: 6px; }
  .gallery-dot { width: 7px; height: 7px; border-radius: 50%; background: rgba(255,255,255,.6); cursor: pointer; transition: background .15s; }
  .gallery-dot.active { background: #fff; }
  .thumb-strip { display: flex; gap: 6px; padding: 8px 16px; overflow-x: auto; scrollbar-width: none; }
  .thumb-strip::-webkit-scrollbar { display: none; }
  .thumb-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid transparent; flex-shrink: 0; transition: border-color .15s; }
  .thumb-img.active { border-color: #111; }

  /* Info */
  .listing-info { padding: 16px 16px 0; }
  .listing-title { font-size: 22px; font-weight: 700; margin: 0 0 4px; }
  .listing-price { font-size: 26px; font-weight: 800; margin: 0 0 8px; }
  .listing-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
  .badge {
    padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
    background: #f0f0f0; color: #555;
  }
  .badge-sold { background: #111; color: #fff; }
  .listing-desc { font-size: 14px; color: #444; line-height: 1.6; margin-bottom: 16px; }
  .listing-date { font-size: 12px; color: #aaa; margin-bottom: 16px; }

  /* Seller card */
  .seller-card {
    display: flex; align-items: center; gap: 12px;
    background: #f8f8f8; border-radius: 14px; padding: 14px 16px;
    margin: 0 16px 16px; text-decoration: none; color: inherit;
    transition: background .15s;
  }
  .seller-card:hover { background: #f0f0f0; }
  .seller-avatar { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
  .seller-info .seller-name { font-weight: 700; font-size: 15px; }
  .seller-info .seller-label { font-size: 12px; color: #999; margin-top: 1px; }

  /* Action buttons */
  .action-bar { padding: 0 16px; display: flex; flex-direction: column; gap: 10px; }
  .btn-buy {
    width: 100%; padding: 15px; background: #111; color: #fff;
    border: none; border-radius: 12px; font-size: 16px; font-weight: 700;
    cursor: pointer; font-family: inherit; transition: opacity .15s;
  }
  .btn-buy:hover { opacity: .85; }
  .btn-buy:disabled { opacity: .4; cursor: not-allowed; }
  .btn-msg {
    width: 100%; padding: 13px; background: #fff; color: #111;
    border: 1.5px solid #ddd; border-radius: 12px; font-size: 15px; font-weight: 600;
    cursor: pointer; font-family: inherit; transition: all .15s;
  }
  .btn-msg:hover { border-color: #111; }
  .btn-seller-action {
    width: 100%; padding: 13px; background: #fff; color: #e53935;
    border: 1.5px solid #e53935; border-radius: 12px; font-size: 15px; font-weight: 600;
    cursor: pointer; font-family: inherit;
  }

  /* Message modal */
  .modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.5); z-index: 1000;
    align-items: flex-end; justify-content: center;
  }
  .modal-overlay.open { display: flex; }
  .modal-sheet {
    background: #fff; border-radius: 20px 20px 0 0;
    padding: 24px 20px 40px; width: 100%; max-width: 680px;
    animation: slideUp .2s ease;
  }
  @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
  .modal-sheet h3 { margin: 0 0 14px; font-size: 18px; font-weight: 700; }
  .modal-sheet textarea {
    width: 100%; min-height: 90px; padding: 12px;
    border: 1.5px solid #e0e0e0; border-radius: 10px;
    font-family: inherit; font-size: 14px; resize: none;
    box-sizing: border-box; outline: none;
  }
  .modal-sheet textarea:focus { border-color: #111; }
  .modal-actions { display: flex; gap: 10px; margin-top: 12px; }
  .modal-send {
    flex: 1; padding: 13px; background: #111; color: #fff;
    border: none; border-radius: 10px; font-size: 15px; font-weight: 700;
    cursor: pointer; font-family: inherit;
  }
  .modal-cancel {
    padding: 13px 20px; background: #fff; border: 1.5px solid #ddd;
    border-radius: 10px; font-size: 14px; cursor: pointer; font-family: inherit;
  }
  .alert-banner {
    margin: 12px 16px; padding: 12px 16px; border-radius: 10px;
    font-size: 14px; font-weight: 500;
  }
  .alert-success { background: #eafaf1; color: #1e8449; border: 1px solid #a9dfbf; }
</style>
</head>
<body>

<nav id="top-nav">
  <a href="../index.php" class="nav-logo">ReVenta<span>.</span></a>
  <div class="nav-search">
    <form method="GET" action="explore.php" style="margin:0;width:100%">
      <input type="text" name="q" placeholder="Search items, brands, sellers...">
    </form>
  </div>
  <div class="nav-links">
    <a href="../index.php" class="nav-tab-link">Home</a>
    <a href="explore.php"  class="nav-tab-link">Explore</a>
    <a href="messages.php" class="nav-tab-link">Messages</a>
    <?php if ($isLoggedIn): ?>
      <a href="profile.php"  class="nav-tab-link">Profile</a>
      <a href="likes.php"    class="nav-tab-link">My Likes</a>
      <a href="../php/Utils/Logout.php" class="nav-tab-link">Logout</a>
    <?php else: ?>
      <a href="login.php"    class="nav-tab-link">Login</a>
    <?php endif; ?>
  </div>
  <a href="sell.php"><button class="btn-sell">+ Sell</button></a>
</nav>

<main id="app">
  <div class="listing-page">

    <?php if (isset($_GET['sold'])): ?>
      <div class="alert-banner alert-success">✓ Listing marked as sold!</div>
    <?php endif; ?>

    <!-- Photo Gallery -->
    <div class="gallery" id="gallery">
      <?php $photos = $item['photos'] ?? []; ?>
      <?php if (!empty($photos)): ?>
        <img class="gallery-main" id="gallery-main"
             src="<?= htmlspecialchars($photos[0]['photo_url']) ?>"
             alt="<?= htmlspecialchars($item['name']) ?>">
        <?php if (count($photos) > 1): ?>
          <button class="gallery-prev" id="gallery-prev">‹</button>
          <button class="gallery-next" id="gallery-next">›</button>
          <div class="gallery-dots" id="gallery-dots">
            <?php foreach ($photos as $i => $p): ?>
              <div class="gallery-dot <?= $i===0?'active':'' ?>" data-idx="<?= $i ?>"></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="gallery-placeholder">📦</div>
      <?php endif; ?>
    </div>

    <?php if (count($photos) > 1): ?>
    <div class="thumb-strip" id="thumb-strip">
      <?php foreach ($photos as $i => $p): ?>
        <img class="thumb-img <?= $i===0?'active':'' ?>"
             src="<?= htmlspecialchars($p['photo_url']) ?>"
             alt="" data-idx="<?= $i ?>"
             loading="lazy">
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Listing Info -->
    <div class="listing-info">
      <div class="listing-badges">
        <?php if ($item['is_sold']): ?>
          <span class="badge badge-sold">SOLD</span>
        <?php endif; ?>
        <?php if (!empty($item['condition_name'])): ?>
          <span class="badge"><?= htmlspecialchars($item['condition_name']) ?></span>
        <?php endif; ?>
        <?php if (!empty($item['category_name'])): ?>
          <span class="badge"><?= htmlspecialchars($item['category_name']) ?></span>
        <?php endif; ?>
      </div>
      <h1 class="listing-title"><?= htmlspecialchars($item['name']) ?></h1>
      <div class="listing-price">$<?= number_format((float)$item['price'], 2) ?></div>
      <?php if (!empty($item['description'])): ?>
        <p class="listing-desc"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
      <?php endif; ?>
      <div class="listing-date">Listed <?= date('M j, Y', strtotime($item['created_at'])) ?></div>
      <div class="listing-date">Views: <?= $item['view_count'] ?></div>
    </div>

    <!-- Seller Card -->
    <a class="seller-card" href="#">
      <img class="seller-avatar"
           src="<?= sellerAvatar($item) ?>"
           alt="<?= htmlspecialchars($item['seller_username']) ?>">
      <div class="seller-info">
        <div class="seller-name">@<?= htmlspecialchars($item['seller_username']) ?></div>
        <div class="seller-label">Seller · View profile</div>
      </div>
      <span style="margin-left:auto;color:#aaa;font-size:18px">›</span>
    </a>

    <!-- Action Buttons -->
    <div class="action-bar">
      <?php if ($isSeller): ?>
        <?php if (!$item['is_sold']): ?>
          <form method="POST">
            <input type="hidden" name="action" value="mark_sold">
            <button type="submit" class="btn-seller-action">Mark as Sold</button>
          </form>
        <?php endif; ?>
        <a href="sell.php"><button class="btn-msg">List Another Item</button></a>

      <?php elseif ($item['is_sold']): ?>
        <button class="btn-buy" disabled>This item is sold</button>

      <?php elseif (!$isLoggedIn): ?>
        <a href="login.php"><button class="btn-buy">Login to Buy</button></a>
        <a href="login.php"><button class="btn-msg">💬 Message Seller</button></a>

      <?php else: ?>
        <button class="btn-buy" id="btn-buy">Buy Now</button>
        <button class="btn-msg" id="btn-msg">💬 Message Seller</button>
      <?php endif; ?>
    </div>

  </div>
</main>

<!-- Message Modal -->
<?php if ($isLoggedIn && !$isSeller && !$item['is_sold']): ?>
<div class="modal-overlay" id="msg-modal">
  <div class="modal-sheet">
    <h3>Message @<?= htmlspecialchars($item['seller_username']) ?></h3>
    <form method="POST" id="msg-form">
      <input type="hidden" name="action" value="message">
      <textarea name="initial_message"
                placeholder="Hi, is this still available? …"
                id="msg-textarea"></textarea>
      <div class="modal-actions">
        <button type="submit" class="modal-send">Send & Open Chat</button>
        <button type="button" class="modal-cancel" id="modal-cancel">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Buy Now Modal -->
<div class="modal-overlay" id="buy-modal">
  <div class="modal-sheet">
    <h3>Buy "<?= htmlspecialchars($item['name']) ?>"</h3>
    <p style="font-size:14px;color:#555;margin:0 0 16px">
      Send the seller a message to arrange payment and pickup/shipping.
      This will open a chat thread about this listing.
    </p>
    <form method="POST" id="buy-form">
      <input type="hidden" name="action" value="message">
      <textarea name="initial_message"
                placeholder="Hi! I'd like to buy this item. Is it still available?"
                id="buy-textarea">Hi! I'd like to buy this. Is it still available?</textarea>
      <div class="modal-actions">
        <button type="submit" class="modal-send">Send Message →</button>
        <button type="button" class="modal-cancel" id="buy-cancel">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<nav id="bottom-nav">
  <a class="bottom-item" href="../index.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><div class="bottom-label">Home</div></a>
  <a class="bottom-item active" href="explore.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div><div class="bottom-label">Explore</div></a>
  <a class="bottom-item sell-btn" href="sell.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div><div class="bottom-label">Sell</div></a>
  <a class="bottom-item" href="likes.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div><div class="bottom-label">Likes</div></a>
  <a class="bottom-item" href="messages.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div><div class="bottom-label">Messages</div></a>
  <a class="bottom-item" href="profile.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div class="bottom-label">Profile</div></a>
</nav>

<script>
// ── Photo gallery ─────────────────────────────────────────────
const photos = <?= json_encode(array_map(fn($p) => $p['photo_url'], $photos)) ?>;
let current = 0;

function goTo(idx) {
  if (!photos.length) return;
  current = (idx + photos.length) % photos.length;
  document.getElementById('gallery-main').src = photos[current];
  document.querySelectorAll('.gallery-dot').forEach((d,i)  => d.classList.toggle('active', i===current));
  document.querySelectorAll('.thumb-img').forEach((t,i)    => t.classList.toggle('active', i===current));
}

document.getElementById('gallery-prev')?.addEventListener('click', () => goTo(current-1));
document.getElementById('gallery-next')?.addEventListener('click', () => goTo(current+1));
document.querySelectorAll('.gallery-dot').forEach(d => d.addEventListener('click', () => goTo(+d.dataset.idx)));
document.querySelectorAll('.thumb-img').forEach(t  => t.addEventListener('click', () => goTo(+t.dataset.idx)));

// Swipe support
let touchX = null;
const gallery = document.getElementById('gallery');
gallery?.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; });
gallery?.addEventListener('touchend',   e => {
  if (touchX === null) return;
  const dx = e.changedTouches[0].clientX - touchX;
  if (Math.abs(dx) > 40) goTo(dx < 0 ? current+1 : current-1);
  touchX = null;
});

// ── Modals ────────────────────────────────────────────────────
const msgModal  = document.getElementById('msg-modal');
const buyModal  = document.getElementById('buy-modal');

document.getElementById('btn-msg')?.addEventListener('click', () => {
  if (msgModal) msgModal.classList.add('open');
});
document.getElementById('modal-cancel')?.addEventListener('click', () => {
  msgModal?.classList.remove('open');
});
document.getElementById('btn-buy')?.addEventListener('click', () => {
  if (buyModal) buyModal.classList.add('open');
});
document.getElementById('buy-cancel')?.addEventListener('click', () => {
  buyModal?.classList.remove('open');
});
// Close on overlay click
[msgModal, buyModal].forEach(m => {
  m?.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});
</script>
</body>
</html>