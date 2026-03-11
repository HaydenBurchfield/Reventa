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
<style>
  /* Layout */
  #app { display: flex; height: calc(100vh - 56px); overflow: hidden; }

  /* Inbox sidebar */
  .inbox-sidebar {
    width: 320px; flex-shrink: 0;
    border-right: 1.5px solid #eee;
    overflow-y: auto; display: flex; flex-direction: column;
  }
  .inbox-header {
    padding: 16px; border-bottom: 1.5px solid #eee;
    font-size: 20px; font-weight: 700; flex-shrink: 0;
  }
  .inbox-empty { padding: 40px 20px; text-align: center; color: #aaa; font-size: 14px; }

  .chat-row {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; cursor: pointer; transition: background .15s;
    border-bottom: 1px solid #f5f5f5; text-decoration: none; color: inherit;
  }
  .chat-row:hover    { background: #fafafa; }
  .chat-row.active   { background: #f0f0f0; }
  .chat-row-avatar   { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
  .chat-row-body     { flex: 1; min-width: 0; }
  .chat-row-name     { font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .chat-row-listing  { font-size: 12px; color: #888; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }
  .chat-row-preview  { font-size: 12px; color: #aaa; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
  .chat-row-time     { font-size: 11px; color: #ccc; flex-shrink: 0; }

  /* Chat pane */
  .chat-pane {
    flex: 1; display: flex; flex-direction: column; min-width: 0;
    background: #fff;
  }
  .chat-empty-state {
    flex: 1; display: flex; align-items: center; justify-content: center;
    flex-direction: column; color: #aaa; gap: 12px;
  }
  .chat-empty-state .icon { font-size: 44px; }
  .chat-empty-state p { font-size: 15px; margin: 0; }

  /* Chat header */
  .chat-header {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; border-bottom: 1.5px solid #eee;
    flex-shrink: 0;
  }
  .chat-header-avatar { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; }
  .chat-header-name   { font-weight: 700; font-size: 15px; }
  .chat-header-sub    { font-size: 12px; color: #888; }
  .chat-listing-bar {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 16px; background: #f8f8f8;
    border-bottom: 1px solid #eee; text-decoration: none; color: inherit;
    transition: background .15s; flex-shrink: 0;
  }
  .chat-listing-bar:hover { background: #f0f0f0; }
  .listing-thumb { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; background: #e0e0e0; flex-shrink: 0; }
  .listing-thumb-placeholder { width: 40px; height: 40px; border-radius: 8px; background: #e0e0e0; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
  .listing-bar-info .listing-bar-name  { font-size: 13px; font-weight: 600; }
  .listing-bar-info .listing-bar-price { font-size: 12px; color: #888; }

  /* Message list */
  .chat-messages {
    flex: 1; overflow-y: auto; padding: 16px;
    display: flex; flex-direction: column; gap: 12px;
  }
  .msg-row { display: flex; align-items: flex-end; gap: 8px; }
  .msg-row.mine { flex-direction: row-reverse; }
  .msg-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
  .msg-bubble {
    max-width: 70%; padding: 10px 14px; border-radius: 18px;
    font-size: 14px; line-height: 1.5; word-break: break-word;
  }
  .msg-row.mine   .msg-bubble { background: #111; color: #fff; border-bottom-right-radius: 4px; }
  .msg-row.theirs .msg-bubble { background: #f0f0f0; color: #111; border-bottom-left-radius: 4px; }
  .msg-time { font-size: 10px; color: #bbb; white-space: nowrap; padding-bottom: 2px; }

  /* Composer */
  .chat-composer {
    display: flex; align-items: flex-end; gap: 10px;
    padding: 12px 16px; border-top: 1.5px solid #eee;
    flex-shrink: 0;
  }
  .composer-input {
    flex: 1; padding: 10px 14px;
    border: 1.5px solid #e0e0e0; border-radius: 22px;
    font-family: inherit; font-size: 14px; resize: none;
    outline: none; transition: border-color .15s;
    max-height: 120px; overflow-y: auto; min-height: 42px;
    box-sizing: border-box;
  }
  .composer-input:focus { border-color: #111; }
  .composer-send {
    width: 42px; height: 42px; border-radius: 50%;
    background: #111; color: #fff; border: none;
    font-size: 18px; cursor: pointer; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: opacity .15s;
  }
  .composer-send:hover { opacity: .8; }
  .composer-send:disabled { opacity: .3; cursor: default; }

  /* Mobile: hide sidebar when chat is open */
  @media (max-width: 640px) {
    .inbox-sidebar { width: 100%; border-right: none; }
    .chat-pane     { display: none; position: fixed; inset: 56px 0 56px; z-index: 50; }
    .chat-pane.mobile-open { display: flex; }
    .chat-back { display: flex !important; }
  }
  .chat-back {
    display: none; align-items: center; gap: 6px;
    background: none; border: none; font-family: inherit;
    font-size: 14px; font-weight: 600; cursor: pointer; padding: 0;
  }
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
    <a href="messages.php" class="nav-tab-link active">Messages</a>
    <a href="profile.php"  class="nav-tab-link">Profile</a>
    <a href="../php/Utils/Logout.php" class="nav-tab-link">Logout</a>
  </div>
  <a href="sell.php"><button class="btn-sell">+ Sell</button></a>
</nav>

<main id="app">

  <!-- ── Inbox Sidebar ───────────────────────────────────────── -->
  <div class="inbox-sidebar" id="inbox-sidebar">
    <div class="inbox-header">Messages</div>
    <?php if (empty($chats)): ?>
      <div class="inbox-empty">
        <div style="font-size:36px;margin-bottom:8px">💬</div>
        No conversations yet.<br>
        Browse listings and message sellers to get started.
      </div>
    <?php else: ?>
      <?php foreach ($chats as $ch): ?>
        <a class="chat-row <?= $ch['id'] == $activeChatId ? 'active' : '' ?>"
           href="messages.php?chat=<?= $ch['id'] ?>"
           data-chat="<?= $ch['id'] ?>">
          <img class="chat-row-avatar"
               src="<?= chatAvatar($ch, $userId) ?>"
               alt="">
          <div class="chat-row-body">
            <div class="chat-row-name"><?= chatName($ch, $userId) ?></div>
            <?php if (!empty($ch['listing_name'])): ?>
              <div class="chat-row-listing">📦 <?= htmlspecialchars($ch['listing_name']) ?> · $<?= number_format((float)$ch['listing_price'],2) ?></div>
            <?php endif; ?>
            <div class="chat-row-preview"><?= htmlspecialchars(mb_strimwidth($ch['last_message'] ?? 'No messages yet', 0, 50, '…')) ?></div>
          </div>
          <?php if ($ch['last_message_at']): ?>
            <div class="chat-row-time"><?= date('M j', strtotime($ch['last_message_at'])) ?></div>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ── Chat Pane ──────────────────────────────────────────── -->
  <div class="chat-pane" id="chat-pane">

    <?php if ($activeChat): ?>

    <!-- Chat header -->
    <div class="chat-header">
      <button class="chat-back" id="chat-back">‹ Back</button>
      <img class="chat-header-avatar"
           src="<?= chatAvatar($activeChat, $userId) ?>"
           alt="">
      <div>
        <div class="chat-header-name"><?= chatName($activeChat, $userId) ?></div>
        <div class="chat-header-sub">Active now</div>
      </div>
    </div>

    <!-- Listing bar -->
    <?php if (!empty($activeChat['listing_name'])): ?>
    <a class="chat-listing-bar" href="listing.php?id=<?= (int)$activeChat['listing_id'] ?>">
      <?php if (!empty($activeChat['listing_photo'])): ?>
        <img class="listing-thumb" src="<?= htmlspecialchars($activeChat['listing_photo']) ?>" alt="">
      <?php else: ?>
        <div class="listing-thumb-placeholder">📦</div>
      <?php endif; ?>
      <div class="listing-bar-info">
        <div class="listing-bar-name"><?= htmlspecialchars($activeChat['listing_name']) ?></div>
        <div class="listing-bar-price">
          $<?= number_format((float)$activeChat['listing_price'],2) ?>
          <?= $activeChat['listing_is_sold'] ? ' · <strong>SOLD</strong>' : '' ?>
        </div>
      </div>
      <span style="margin-left:auto;color:#aaa">›</span>
    </a>
    <?php endif; ?>

    <!-- Messages -->
    <div class="chat-messages" id="chat-messages">
      <?php if (empty($activeMessages)): ?>
        <div style="text-align:center;color:#ccc;padding:30px;font-size:13px">
          No messages yet. Say hello!
        </div>
      <?php else: ?>
        <?php foreach ($activeMessages as $msg): ?>
          <?php $mine = ($msg['sender_id'] == $userId); ?>
          <div class="msg-row <?= $mine ? 'mine' : 'theirs' ?>" data-msg-id="<?= $msg['id'] ?>">
            <?php if (!$mine): ?>
              <img class="msg-avatar" src="<?= msgAvatar($msg) ?>" alt="">
            <?php endif; ?>
            <div class="msg-bubble"><?= nl2br(htmlspecialchars($msg['content'])) ?></div>
            <div class="msg-time"><?= date('h:i A', strtotime($msg['created_at'])) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
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
    <div class="chat-empty-state">
      <div class="icon">💬</div>
      <p>Select a conversation</p>
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

<?php if ($activeChat): ?>
<script>
const CHAT_ID   = <?= (int)$activeChatId ?>;
const MY_ID     = <?= (int)$userId ?>;
const MY_AVATAR = <?= json_encode($meObj->profile_picture
    ? $meObj->profile_picture
    : 'https://ui-avatars.com/api/?name=' . urlencode($meObj->username??'Me') . '&background=111&color=fff&size=60') ?>;

const messagesDiv = document.getElementById('chat-messages');
const composer    = document.getElementById('composer');
const sendBtn     = document.getElementById('send-btn');

// ── Scroll to bottom ──────────────────────────────────────────
function scrollBottom() {
  messagesDiv.scrollTop = messagesDiv.scrollHeight;
}
scrollBottom();

// ── Build message HTML ────────────────────────────────────────
function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function nl2br(s) { return escHtml(s).replace(/\n/g,'<br>'); }
function fmtTime(dt) {
  const d = new Date(dt.replace(' ','T'));
  return d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
}
function buildMsgHtml(msg) {
  const mine = msg.sender_id == MY_ID;
  const avatar = mine ? MY_AVATAR : (msg.profile_picture || `https://ui-avatars.com/api/?name=${encodeURIComponent(msg.username||'?')}&background=eee&color=555&size=60`);
  const avatarHtml = mine ? '' : `<img class="msg-avatar" src="${escHtml(avatar)}" alt="">`;
  return `<div class="msg-row ${mine?'mine':'theirs'}" data-msg-id="${msg.id}">
    ${avatarHtml}
    <div class="msg-bubble">${nl2br(msg.content)}</div>
    <div class="msg-time">${fmtTime(msg.created_at)}</div>
  </div>`;
}

// ── Get last visible message ID ───────────────────────────────
function lastMsgId() {
  const rows = messagesDiv.querySelectorAll('[data-msg-id]');
  if (!rows.length) return 0;
  return +rows[rows.length-1].dataset.msgId;
}

// ── Send message ──────────────────────────────────────────────
async function sendMessage() {
  const content = composer.value.trim();
  if (!content) return;
  composer.value = '';
  composer.style.height = '';
  sendBtn.disabled = true;

  // Optimistic UI
  const tmpId = Date.now();
  const tmpHtml = `<div class="msg-row mine" data-msg-id="tmp-${tmpId}">
    <div class="msg-bubble">${nl2br(content)}</div>
    <div class="msg-time">Sending…</div>
  </div>`;
  messagesDiv.insertAdjacentHTML('beforeend', tmpHtml);
  scrollBottom();

  try {
    const res  = await fetch('messages.php', {
      method:  'POST',
      headers: { 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest' },
      body:    JSON.stringify({ chat_id: CHAT_ID, content }),
    });
    const data = await res.json();
    if (data.ok && data.messages.length) {
      // Replace temp
      const tmp = messagesDiv.querySelector(`[data-msg-id="tmp-${tmpId}"]`);
      if (tmp) tmp.outerHTML = buildMsgHtml(data.messages[0]);
      scrollBottom();
    }
  } catch (e) {}
  sendBtn.disabled = false;
}

sendBtn.addEventListener('click', sendMessage);
composer.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});
// Auto-resize textarea
composer.addEventListener('input', () => {
  composer.style.height = 'auto';
  composer.style.height = Math.min(composer.scrollHeight, 120) + 'px';
});

// ── Poll for new messages every 3 seconds ────────────────────
let polling = true;
async function poll() {
  if (!polling) return;
  const last = lastMsgId();
  if (typeof last === 'number' && last > 0) {
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
setTimeout(poll, 3000);
window.addEventListener('beforeunload', () => { polling = false; });

// ── Mobile: back button ───────────────────────────────────────
document.getElementById('chat-back')?.addEventListener('click', () => {
  document.getElementById('chat-pane').classList.remove('mobile-open');
});

// Auto-open chat pane on mobile if a chat is selected
if (window.innerWidth <= 640 && CHAT_ID) {
  document.getElementById('chat-pane').classList.add('mobile-open');
}

// Clicking a chat row on mobile opens the pane (full page reload is fine)
</script>
<?php endif; ?>
</body>
</html>