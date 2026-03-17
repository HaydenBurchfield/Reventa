<?php
require_once '../php/objects/User.php';
require_once '../php/objects/Chat.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId  = $_SESSION['user_id'];
$chatObj = new Chat();

// ── Handle AJAX: send message ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $data    = json_decode(file_get_contents('php://input'), true);
    $chatId  = (int)($data['chat_id'] ?? 0);
    $content = trim($data['content'] ?? '');

    if (!$chatId || !$content) { echo json_encode(['ok'=>false,'error'=>'Missing data']); exit; }

    // Verify user is a participant
    $chat = $chatObj->getChatById($chatId, $userId);
    if (!$chat) { echo json_encode(['ok'=>false,'error'=>'Not authorized']); exit; }

    $newId = $chatObj->sendMessage($chatId, $userId, $content);
    if ($newId) {
        // Return the new message
        $msgs = $chatObj->getMessagesSince($chatId, $newId - 1);
        echo json_encode(['ok'=>true, 'messages'=>$msgs]);
    } else {
        echo json_encode(['ok'=>false,'error'=>'Send failed']);
    }
    exit;
}

// ── Handle AJAX: poll for new messages ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['poll'])) {
    header('Content-Type: application/json');
    $chatId = (int)($_GET['chat_id'] ?? 0);
    $lastId = (int)($_GET['last_id'] ?? 0);

    $chat = $chatObj->getChatById($chatId, $userId);
    if (!$chat) { echo json_encode(['ok'=>false]); exit; }

    $msgs = $chatObj->getMessagesSince($chatId, $lastId);
    echo json_encode(['ok'=>true,'messages'=>$msgs]);
    exit;
}

// ── Load inbox ────────────────────────────────────────────────
$chats         = $chatObj->getChatsForUser($userId);
$activeChatId  = isset($_GET['chat']) ? (int)$_GET['chat'] : null;
$activeChat    = null;
$activeMessages = [];

if ($activeChatId) {
    $activeChat = $chatObj->getChatById($activeChatId, $userId);
    if ($activeChat) {
        $activeMessages = $chatObj->getMessages($activeChatId);
    } else {
        $activeChatId = null;
    }
}

// If no chat selected and there are chats, select the first
if (!$activeChatId && !empty($chats)) {
    $activeChatId  = $chats[0]['id'];
    $activeChat    = $chatObj->getChatById($activeChatId, $userId);
    $activeMessages = $chatObj->getMessages($activeChatId);
}

function chatAvatar(array $chat, int $myId): string {
    $isbuyer = $myId == $chat['buyer_id'];
    $pic     = $isbuyer ? $chat['seller_avatar'] : $chat['buyer_avatar'];
    $name    = $isbuyer ? $chat['seller_username'] : $chat['buyer_username'];
    if ($pic) return htmlspecialchars($pic);
    return 'https://ui-avatars.com/api/?name=' . urlencode($name??'U') . '&background=111&color=fff&size=60';
}
function chatName(array $chat, int $myId): string {
    $isbuyer = $myId == $chat['buyer_id'];
    return htmlspecialchars($isbuyer ? $chat['seller_username'] : $chat['buyer_username']);
}
function myAvatar(int $myId, ?string $pic): string {
    if ($pic) return htmlspecialchars($pic);
    return 'https://ui-avatars.com/api/?name=Me&background=111&color=fff&size=60';
}
function msgAvatar(array $msg): string {
    if (!empty($msg['profile_picture'])) return htmlspecialchars($msg['profile_picture']);
    return 'https://ui-avatars.com/api/?name=' . urlencode($msg['username']??'U') . '&background=eee&color=555&size=60';
}

// Current user's avatar
$meObj = new User();
$meObj->populate($userId);
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>ReVenta — Messages</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/styles.css">
<link rel="stylesheet" href="../assets/css/messages.css">
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
    <a href="messages.php" class="nav-tab-link active">Messages</a>
    <a href="profile.php"  class="nav-tab-link">Profile</a>
    <a href="likes.php"    class="nav-tab-link">My Likes</a>
    <a href="../php/Utils/Logout.php" class="nav-tab-link">Logout</a>
  </div>
  <a href="sell.php"><button class="btn-sell">+ Sell</button></a>
</nav>

<main id="app">

  <!-- Inbox Sidebar -->
  <div class="inbox-sidebar" id="inbox-sidebar">
    <div class="inbox-header">Messages</div>

    <?php if (empty($chats)): ?>
      <div style="padding:40px 20px;text-align:center;color:#aaa;font-size:14px;">
        No conversations yet.<br>
        <a href="explore.php" style="color:#111;font-weight:500;text-decoration:none">Browse listings →</a>
      </div>
    <?php else: ?>
      <?php foreach ($chats as $chat):
        $isActive     = ($chat['id'] == $activeChatId);
        $otherName    = chatName($chat, $userId);
        $otherAvatar  = chatAvatar($chat, $userId);
        $preview      = htmlspecialchars(mb_strimwidth($chat['last_message'] ?? '', 0, 60, '…'));
        $listingLabel = '';
        if (!empty($chat['listing_name'])) {
          $listingLabel = htmlspecialchars($chat['listing_name']);
          if ($chat['listing_price'] !== null) $listingLabel .= ' · $' . number_format((float)$chat['listing_price'], 2);
        }
        $timeStr = '';
        if (!empty($chat['last_message_at'])) {
          $ts      = strtotime($chat['last_message_at']);
          $timeStr = (date('Y-m-d') === date('Y-m-d', $ts)) ? date('g:i A', $ts) : date('M j', $ts);
        }
      ?>
      <a class="chat-row <?= $isActive ? 'active' : '' ?>"
         href="messages.php?chat=<?= (int)$chat['id'] ?>"
         data-chat="<?= (int)$chat['id'] ?>">
        <img class="chat-row-avatar" src="<?= $otherAvatar ?>" alt="">
        <div class="chat-row-body">
          <div class="chat-row-name"><?= $otherName ?></div>
          <?php if ($listingLabel): ?><div class="chat-row-listing">📦 <?= $listingLabel ?></div><?php endif; ?>
          <?php if ($preview): ?><div class="chat-row-preview"><?= $preview ?></div><?php endif; ?>
        </div>
        <?php if ($timeStr): ?><div class="chat-row-time"><?= $timeStr ?></div><?php endif; ?>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Chat Pane -->
  <div class="chat-pane <?= $activeChatId ? 'mobile-open' : '' ?>" id="chat-pane">

    <?php if ($activeChat): ?>

    <!-- Chat header -->
    <div class="chat-header">
      <a class="chat-back" id="chat-back" href="messages.php">&#8249; Back</a>
      <?php
        $headerAvatar = chatAvatar($activeChat, $userId);
        $headerName   = chatName($activeChat, $userId);
        $otherId      = ($userId == $activeChat['buyer_id']) ? $activeChat['seller_id'] : $activeChat['buyer_id'];
      ?>
      <img class="chat-header-avatar" src="<?= $headerAvatar ?>" alt="">
      <div>
        <div class="chat-header-name"><?= $headerName ?></div>
        <a href="profile.php?id=<?= (int)$otherId ?>" style="text-decoration:none">
          <div class="chat-header-sub">View profile</div>
        </a>
      </div>
    </div>

    <!-- Listing bar -->
    <?php if (!empty($activeChat['listing_id'])): ?>
    <a class="chat-listing-bar" href="listing.php?id=<?= (int)$activeChat['listing_id'] ?>">
      <?php if (!empty($activeChat['listing_photo'])): ?>
        <img class="listing-thumb" src="<?= htmlspecialchars($activeChat['listing_photo']) ?>" alt="">
      <?php else: ?>
        <div class="listing-thumb" style="background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:20px;">📦</div>
      <?php endif; ?>
      <div class="listing-bar-info">
        <div class="listing-bar-name"><?= htmlspecialchars($activeChat['listing_name'] ?? '') ?></div>
        <div class="listing-bar-price">
          <?= $activeChat['listing_price'] !== null ? '$' . number_format((float)$activeChat['listing_price'], 2) : '' ?>
          <?php if (!empty($activeChat['listing_is_sold'])): ?>
            <span style="margin-left:6px;font-size:11px;background:#111;color:#fff;padding:2px 7px;border-radius:10px;">SOLD</span>
          <?php endif; ?>
        </div>
      </div>
      <span style="margin-left:auto;color:#aaa">›</span>
    </a>
    <?php endif; ?>

    <!-- Messages -->
    <div class="chat-messages" id="chat-messages">
      <?php foreach ($activeMessages as $msg):
        $mine       = ($msg['sender_id'] == $userId);
        $msgAvatar  = msgAvatar($msg);
        $msgTime    = date('g:i A', strtotime($msg['created_at']));
      ?>
        <div class="msg-row <?= $mine ? 'mine' : 'theirs' ?>" data-msg-id="<?= (int)$msg['id'] ?>">
          <?php if (!$mine): ?>
            <img class="msg-avatar" src="<?= $msgAvatar ?>" alt="">
          <?php endif; ?>
          <div class="msg-bubble"><?= nl2br(htmlspecialchars($msg['content'])) ?></div>
          <div class="msg-time"><?= $msgTime ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Composer -->
    <div class="chat-composer">
      <textarea class="composer-input" id="composer"
                placeholder="Type a message…" rows="1"></textarea>
      <button class="composer-send" id="send-btn" title="Send">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="22" y1="2" x2="11" y2="13"/>
          <polygon points="22 2 15 22 11 13 2 9 22 2"/>
        </svg>
      </button>
    </div>

    <?php else: ?>
    <!-- No chat selected (desktop empty state) -->
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:#bbb;gap:12px;">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ddd" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      <p style="font-size:14px;margin:0">Select a conversation</p>
    </div>
    <?php endif; ?>

  </div>

</main>

<nav id="bottom-nav">
  <a class="bottom-item" href="../index.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><div class="bottom-label">Home</div></a>
  <a class="bottom-item" href="explore.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div><div class="bottom-label">Explore</div></a>
  <a class="bottom-item sell-btn" href="sell.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div><div class="bottom-label">Sell</div></a>
  <a class="bottom-item" href="likes.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div><div class="bottom-label">Likes</div></a>
  <a class="bottom-item active" href="messages.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div><div class="bottom-label">Messages</div></a>
  <a class="bottom-item" href="profile.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div class="bottom-label">Profile</div></a>
</nav>

<script>
const CHAT_ID   = <?= $activeChatId ? (int)$activeChatId : 'null' ?>;
const MY_ID     = <?= (int)$userId ?>;
const MY_AVATAR = <?= json_encode(myAvatar($userId, $meObj->profile_picture)) ?>;

const messagesDiv = document.getElementById('chat-messages');
const composer    = document.getElementById('composer');
const sendBtn     = document.getElementById('send-btn');

function scrollBottom() {
  if (messagesDiv) messagesDiv.scrollTop = messagesDiv.scrollHeight;
}
scrollBottom();

function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function nl2br(s) { return escHtml(s).replace(/\n/g,'<br>'); }
function fmtTime(dt) {
  const d = new Date(dt.replace(' ','T'));
  return d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
}
function buildMsgHtml(msg) {
  const mine      = msg.sender_id == MY_ID;
  const avatarUrl = mine ? MY_AVATAR
    : (msg.profile_picture || `https://ui-avatars.com/api/?name=${encodeURIComponent(msg.username||'?')}&background=eee&color=555&size=60`);
  const avatarHtml = mine ? '' : `<img class="msg-avatar" src="${escHtml(avatarUrl)}" alt="">`;
  return `<div class="msg-row ${mine?'mine':'theirs'}" data-msg-id="${msg.id}">
    ${avatarHtml}
    <div class="msg-bubble">${nl2br(msg.content)}</div>
    <div class="msg-time">${fmtTime(msg.created_at)}</div>
  </div>`;
}

function lastMsgId() {
  if (!messagesDiv) return 0;
  const rows = messagesDiv.querySelectorAll('[data-msg-id]');
  if (!rows.length) return 0;
  const id = rows[rows.length-1].dataset.msgId;
  return isNaN(+id) ? 0 : +id;
}

async function sendMessage() {
  if (!CHAT_ID || !composer) return;
  const content = composer.value.trim();
  if (!content) return;
  composer.value = '';
  composer.style.height = '';
  sendBtn.disabled = true;

  const tmpId   = Date.now();
  messagesDiv.insertAdjacentHTML('beforeend', `<div class="msg-row mine" data-msg-id="tmp-${tmpId}">
    <div class="msg-bubble">${nl2br(content)}</div>
    <div class="msg-time">Sending…</div>
  </div>`);
  scrollBottom();

  try {
    const res  = await fetch('messages.php', {
      method:  'POST',
      headers: { 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest' },
      body:    JSON.stringify({ chat_id: CHAT_ID, content }),
    });
    const data = await res.json();
    if (data.ok && data.messages.length) {
      const tmp = messagesDiv.querySelector(`[data-msg-id="tmp-${tmpId}"]`);
      if (tmp) tmp.outerHTML = buildMsgHtml(data.messages[0]);
      scrollBottom();
    }
  } catch(e) { console.error(e); }
  sendBtn.disabled = false;
}

if (sendBtn)  sendBtn.addEventListener('click', sendMessage);
if (composer) {
  composer.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
  });
  composer.addEventListener('input', () => {
    composer.style.height = 'auto';
    composer.style.height = Math.min(composer.scrollHeight, 120) + 'px';
  });
}

let polling = !!CHAT_ID;
async function poll() {
  if (!polling || !CHAT_ID) return;
  const last = lastMsgId();
  if (last > 0) {
    try {
      const res  = await fetch(`messages.php?poll=1&chat_id=${CHAT_ID}&last_id=${last}`, {
        headers: { 'X-Requested-With':'XMLHttpRequest' }
      });
      const data = await res.json();
      if (data.ok && data.messages.length) {
        data.messages.forEach(msg => {
          if (!messagesDiv.querySelector(`[data-msg-id="${msg.id}"]`)) {
            messagesDiv.insertAdjacentHTML('beforeend', buildMsgHtml(msg));
          }
        });
        scrollBottom();
      }
    } catch(e) {}
  }
  setTimeout(poll, 3000);
}
if (CHAT_ID) setTimeout(poll, 3000);
window.addEventListener('beforeunload', () => { polling = false; });

document.getElementById('chat-back')?.addEventListener('click', e => {
  if (window.innerWidth <= 640) {
    e.preventDefault();
    document.getElementById('chat-pane').classList.remove('mobile-open');
    history.pushState(null, '', 'messages.php');
  }
});
</script>
</body>
</html>