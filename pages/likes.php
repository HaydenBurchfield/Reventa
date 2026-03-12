<?php
require_once '../php/objects/User.php';
require_once '../php/objects/Listing.php';
require_once '../php/Utils/DatabaseConnection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Fetch liked listings for this user
$db   = new DatabaseConnection();
$conn = $db->getConnection();

$sql = "
    SELECT l.id, l.name, l.price, l.description, l.created_at, l.is_sold,
           c.name AS condition_name,
           cat.name AS category_name,
           u.username AS seller_username, u.id AS seller_id,
           u.profile_picture AS seller_avatar,
           (SELECT lp.photo_url FROM listing_photo lp
            WHERE lp.listing_id = l.id ORDER BY lp.sort_order ASC LIMIT 1) AS cover_photo,
           ll.created_at AS liked_at
    FROM listing_like ll
    JOIN listing l       ON l.id  = ll.listing_id
    JOIN `condition` c   ON c.id  = l.condition_id
    LEFT JOIN category cat ON cat.id = l.category_id
    JOIN user u          ON u.id  = l.seller_id
    WHERE ll.user_id = ?
    ORDER BY ll.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result   = $stmt->get_result();
$liked    = [];
while ($row = $result->fetch_assoc()) $liked[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>ReVenta — Liked Items</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/styles.css">
<style>
  .likes-header {
    padding: 24px 16px 8px;
    display: flex;
    align-items: baseline;
    gap: 10px;
  }
  .likes-header h2 {
    font-size: 26px;
    font-weight: 800;
    margin: 0;
  }
  .likes-count {
    font-size: 14px;
    color: #999;
    font-weight: 500;
  }

  /* Product grid (same as explore) */
  .product-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    padding: 8px 12px 100px;
  }
  @media (min-width: 540px) { .product-grid { grid-template-columns: repeat(3, 1fr); } }
  @media (min-width: 800px) { .product-grid { grid-template-columns: repeat(4, 1fr); } }

  .card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
    position: relative;
  }
  .card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.1); }
  .card-img-wrap {
    position: relative;
    aspect-ratio: 1/1;
    background: #f2f2f2;
    overflow: hidden;
  }
  .card-img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
    transition: transform .3s;
  }
  .card:hover .card-img { transform: scale(1.04); }
  .card-no-img {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 40px; color: #ccc;
  }
  .card-sold-badge {
    position: absolute; top: 8px; left: 8px;
    background: rgba(0,0,0,.75); color: #fff;
    font-size: 11px; font-weight: 700;
    padding: 3px 8px; border-radius: 20px;
    letter-spacing: .5px;
  }
  .card-unlike-btn {
    position: absolute; top: 8px; right: 8px;
    background: rgba(255,255,255,.92);
    border: none; border-radius: 50%;
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    box-shadow: 0 1px 4px rgba(0,0,0,.15);
    transition: transform .15s, background .15s;
    z-index: 2;
  }
  .card-unlike-btn:hover { transform: scale(1.15); background: #fff; }
  .card-unlike-btn svg { width: 16px; height: 16px; }
  .card-body { padding: 10px 10px 12px; }
  .card-name {
    font-size: 13px; font-weight: 600;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin-bottom: 3px;
  }
  .card-price { font-size: 15px; font-weight: 800; color: #111; }
  .card-meta {
    font-size: 11px; color: #aaa; margin-top: 4px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }

  /* Empty state */
  .empty-state {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    text-align: center; padding: 60px 24px;
    color: #999;
  }
  .empty-icon { font-size: 56px; margin-bottom: 16px; line-height: 1; }
  .empty-title { font-size: 20px; font-weight: 700; color: #222; margin-bottom: 6px; }
  .empty-sub { font-size: 14px; color: #999; max-width: 260px; line-height: 1.5; }
  .btn-primary {
    display: inline-block; margin-top: 20px;
    background: #111; color: #fff;
    padding: 13px 28px; border-radius: 12px;
    font-size: 15px; font-weight: 700;
    text-decoration: none; border: none; cursor: pointer;
    font-family: inherit;
    transition: opacity .15s;
  }
  .btn-primary:hover { opacity: .85; }

  /* Toast notification */
  .toast {
    position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%) translateY(20px);
    background: #111; color: #fff;
    padding: 10px 20px; border-radius: 24px;
    font-size: 14px; font-weight: 500;
    opacity: 0; transition: opacity .25s, transform .25s;
    pointer-events: none; z-index: 999; white-space: nowrap;
  }
  .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
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
    <a href="profile.php"  class="nav-tab-link">Profile</a>
    <a href="likes.php"    class="nav-tab-link active">My Likes</a>
    <a href="../php/Utils/Logout.php" class="nav-tab-link">Logout</a>
  </div>
  <a href="sell.php"><button class="btn-sell">+ Sell</button></a>
</nav>

<main id="app">
  <div class="likes-header">
    <h2>Liked Items</h2>
    <span class="likes-count" id="likes-count"><?= count($liked) ?> item<?= count($liked) !== 1 ? 's' : '' ?></span>
  </div>

  <?php if (empty($liked)): ?>
    <div class="empty-state">
      <div class="empty-icon">♡</div>
      <div class="empty-title">No liked items yet</div>
      <div class="empty-sub">Tap the heart on any listing to save it here for later.</div>
      <a href="explore.php" class="btn-primary">Browse Listings</a>
    </div>
  <?php else: ?>
    <div class="product-grid" id="likes-grid">
      <?php foreach ($liked as $item): ?>
        <div class="card" data-id="<?= $item['id'] ?>">
          <div class="card-img-wrap" onclick="window.location='listing.php?id=<?= $item['id'] ?>'">
            <?php if (!empty($item['cover_photo'])): ?>
              <img class="card-img"
                   src="<?= htmlspecialchars($item['cover_photo']) ?>"
                   alt="<?= htmlspecialchars($item['name']) ?>"
                   loading="lazy">
            <?php else: ?>
              <div class="card-no-img">📦</div>
            <?php endif; ?>
            <?php if ($item['is_sold']): ?>
              <span class="card-sold-badge">SOLD</span>
            <?php endif; ?>
            <button class="card-unlike-btn"
                    title="Remove from likes"
                    onclick="event.stopPropagation(); toggleLike(this, <?= $item['id'] ?>)">
              <svg viewBox="0 0 24 24" fill="#e53935" stroke="#e53935" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
              </svg>
            </button>
          </div>
          <div class="card-body" onclick="window.location='listing.php?id=<?= $item['id'] ?>'">
            <div class="card-name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="card-price">$<?= number_format((float)$item['price'], 2) ?></div>
            <div class="card-meta">
              <?= htmlspecialchars($item['condition_name'] ?? '') ?>
              <?php if (!empty($item['category_name'])): ?>
                · <?= htmlspecialchars($item['category_name']) ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<div class="toast" id="toast"></div>

<nav id="bottom-nav">
  <a class="bottom-item" href="../index.php">
    <div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
    <div class="bottom-label">Home</div>
  </a>
  <a class="bottom-item" href="explore.php">
    <div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
    <div class="bottom-label">Explore</div>
  </a>
  <a class="bottom-item sell-btn" href="sell.php">
    <div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div>
    <div class="bottom-label">Sell</div>
  </a>
  <a class="bottom-item active" href="likes.php">
    <div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="#111" stroke="#111" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div>
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
let removing = false;

async function toggleLike(btn, listingId) {
  if (removing) return;
  removing = true;

  try {
    const res  = await fetch('../php/api/like.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=toggle&listing_id=${listingId}`
    });
    const data = await res.json();

    if (!data.liked) {
      // Animate card out
      const card = btn.closest('.card');
      card.style.transition = 'opacity .3s, transform .3s';
      card.style.opacity    = '0';
      card.style.transform  = 'scale(.9)';
      setTimeout(() => {
        card.remove();
        updateCount();
        checkEmpty();
      }, 300);
      showToast('Removed from likes');
    }
  } catch(e) {
    console.error(e);
  } finally {
    removing = false;
  }
}

function updateCount() {
  const cards = document.querySelectorAll('#likes-grid .card');
  const n = cards.length;
  document.getElementById('likes-count').textContent = n + ' item' + (n !== 1 ? 's' : '');
}

function checkEmpty() {
  const grid = document.getElementById('likes-grid');
  if (!grid) return;
  if (!grid.querySelector('.card')) {
    grid.outerHTML = `
      <div class="empty-state">
        <div class="empty-icon">♡</div>
        <div class="empty-title">No liked items yet</div>
        <div class="empty-sub">Tap the heart on any listing to save it here for later.</div>
        <a href="explore.php" class="btn-primary">Browse Listings</a>
      </div>`;
    document.getElementById('likes-count').textContent = '0 items';
  }
}

let toastTimer;
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), 2200);
}
</script>
</body>
</html>