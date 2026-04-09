<?php
require_once '../php/objects/User.php';
require_once '../php/objects/Chat.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$chat   = new Chat();
$chats  = $chat->getChatsForUser($userId);

$activeChatId = !empty($_GET['chat_id']) ? (int)$_GET['chat_id'] : null;
$activeChat   = null;
$messages     = [];

if ($activeChatId) {
    $activeChat = $chat->getChatById($activeChatId, $userId);
    if ($activeChat) {
        $messages = $chat->getMessages($activeChatId);
        // Mark read etc. could go here
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Messages — ReVènta</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/pages.css">
</head>
<body class="msg-body">

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
    <a href="profile.php">Profile</a>
    <a href="messages.php" class="active">Messages</a>
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

<div class="msg-layout">

  <!-- ── SIDEBAR ── -->
  <div class="msg-sidebar" id="msgSidebar">
    <div class="msg-sidebar-header">
      <span class="msg-sidebar-title">Messages</span>
    </div>

    <div class="msg-thread-list">
      <?php if (empty($chats)): ?>
        <div style="padding:40px 24px;text-align:center;color:var(--mid);">
          <div style="font-size:28px;margin-bottom:12px;">✉</div>
          <p style="font-size:12px;letter-spacing:.08em;">No conversations yet.</p>
          <p style="font-size:11px;margin-top:6px;"><a href="explore.php" style="color:var(--black);">Browse listings</a> to start chatting.</p>
        </div>
      <?php else: ?>
        <?php foreach ($chats as $c):
          $isMe     = ($c['buyer_id'] == $userId);
          $otherName = $isMe ? $c['seller_username'] : $c['buyer_username'];
          $otherAvatar = $isMe ? $c['seller_avatar'] : $c['buyer_avatar'];
          $isActive  = ($activeChatId == $c['id']);
          $timeLabel = '';
          if (!empty($c['last_message_at'])) {
              $diff = time() - strtotime($c['last_message_at']);
              if ($diff < 3600)        $timeLabel = floor($diff/60).'m';
              elseif ($diff < 86400)   $timeLabel = floor($diff/3600).'h';
              else                     $timeLabel = date('M j', strtotime($c['last_message_at']));
          }
        ?>
          <div class="msg-thread <?= $isActive ? 'active' : '' ?>"
               onclick="openChat(<?= $c['id'] ?>)">
            <div class="msg-thread-avatar">
              <?php if (!empty($otherAvatar)): ?>
                <img src="../<?= htmlspecialchars($otherAvatar) ?>" alt="">
              <?php else: ?>
                <svg viewBox="0 0 18 18" fill="none">
                  <circle cx="9" cy="6" r="3.5" fill="white"/>
                  <ellipse cx="9" cy="14.5" rx="6.5" ry="3.5" fill="white"/>
                </svg>
              <?php endif; ?>
            </div>

            <?php if (!empty($c['listing_photo'])): ?>
              <div class="msg-thread-listing">
                <img src="../<?= htmlspecialchars($c['listing_photo']) ?>" alt="">
              </div>
            <?php endif; ?>

            <div class="msg-thread-body">
              <p class="msg-thread-name">@<?= htmlspecialchars($otherName) ?></p>
              <p class="msg-thread-preview">
                <?= !empty($c['last_message']) ? htmlspecialchars($c['last_message']) : htmlspecialchars($c['listing_name'] ?? '—') ?>
              </p>
            </div>
            <?php if ($timeLabel): ?>
              <span class="msg-thread-time"><?= $timeLabel ?></span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── CHAT PANEL ── -->
  <div class="msg-chat <?= $activeChatId ? 'visible' : '' ?>" id="msgChat">

    <?php if ($activeChat): ?>
      <?php
        $isMe    = ($activeChat['buyer_id'] == $userId);
        $other   = $isMe ? $activeChat['seller_username'] : $activeChat['buyer_username'];
        $otherAv = $isMe ? $activeChat['seller_avatar']   : $activeChat['buyer_avatar'];
      ?>

      <div class="msg-chat-header">
        <button class="msg-back-btn" onclick="closeMobileChat()">
          <svg viewBox="0 0 10 16" width="8" height="14" fill="none">
            <polyline points="9,1 1,8 9,15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Back
        </button>
        <div class="msg-chat-avatar">
          <?php if (!empty($otherAv)): ?>
            <img src="../<?= htmlspecialchars($otherAv) ?>" alt="">
          <?php else: ?>
            <svg viewBox="0 0 20 20" fill="none">
              <circle cx="10" cy="7" r="3.8" fill="white"/>
              <ellipse cx="10" cy="16" rx="7" ry="4" fill="white"/>
            </svg>
          <?php endif; ?>
        </div>
        <span class="msg-chat-name">@<?= htmlspecialchars($other) ?></span>
      </div>

      <?php if (!empty($activeChat['listing_id'])): ?>
        <div class="msg-listing-strip">
          <div class="msg-listing-thumb">
            <?php if (!empty($activeChat['listing_photo'])): ?>
              <img src="../<?= htmlspecialchars($activeChat['listing_photo']) ?>" alt="">
            <?php endif; ?>
          </div>
          <div class="msg-listing-info">
            <div class="msg-listing-name">
              <a href="listing.php?id=<?= $activeChat['listing_id'] ?>"
                 style="color:inherit;text-decoration:none;">
                <?= htmlspecialchars($activeChat['listing_name'] ?? '') ?>
              </a>
              <?php if ($activeChat['listing_is_sold']): ?>
                <span class="msg-sold-tag">Sold</span>
              <?php endif; ?>
            </div>
            <div class="msg-listing-price">$<?= number_format((float)($activeChat['listing_price']??0), 2) ?></div>
          </div>
        </div>
      <?php endif; ?>

      <div class="msg-chat-messages" id="chatMessages">
        <?php foreach ($messages as $msg):
          $mine = ($msg['sender_id'] == $userId);
        ?>
          <div class="msg-bubble <?= $mine ? 'me' : 'them' ?>">
            <?= htmlspecialchars($msg['content']) ?>
            <div class="msg-bubble-time">
              <?= date('g:i a', strtotime($msg['created_at'])) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="msg-chat-input-row">
        <input class="msg-input" id="msgInput" type="text"
               placeholder="Type a message…"
               onkeydown="if(event.key==='Enter') sendMsg()">
        <button class="msg-send-btn" onclick="sendMsg()">Send</button>
      </div>

    <?php else: ?>
      <div class="msg-empty">
        <div class="msg-empty-icon">✉</div>
        <p>Select a conversation</p>
      </div>
    <?php endif; ?>
  </div>

</div>

<script>
const sidebar = document.getElementById('msgSidebar');
const chatPanel = document.getElementById('msgChat');
const isMobile = () => window.innerWidth <= 767;

function openChat(chatId) {
  window.location.href = 'messages.php?chat_id=' + chatId;
}

function closeMobileChat() {
  if (isMobile()) {
    sidebar.classList.remove('hidden');
    chatPanel.classList.remove('visible');
    history.pushState({}, '', 'messages.php');
  }
}

// On mobile with active chat, hide sidebar
<?php if ($activeChatId && $activeChat): ?>
if (isMobile()) {
  sidebar.classList.add('hidden');
  chatPanel.classList.add('visible');
}
<?php endif; ?>

// Scroll to bottom of messages
const msgs = document.getElementById('chatMessages');
if (msgs) msgs.scrollTop = msgs.scrollHeight;

async function sendMsg() {
  const inp  = document.getElementById('msgInput');
  const text = inp.value.trim();
  if (!text) return;

  <?php if ($activeChatId && $activeChat): ?>
  try {
    const res  = await fetch('../php/api/send_message.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `chat_id=<?= $activeChatId ?>&content=` + encodeURIComponent(text)
    });
    const data = await res.json();
    if (data.success) {
      const div = document.createElement('div');
      div.className = 'msg-bubble me';
      div.innerHTML = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                     + '<div class="msg-bubble-time">Just now</div>';
      msgs.appendChild(div);
      msgs.scrollTop = msgs.scrollHeight;
      inp.value = '';
    }
  } catch(e) { console.error(e); }
  <?php else: ?>
  alert('No active conversation.');
  <?php endif; ?>
}

// Long-poll for new messages
<?php if ($activeChatId && $activeChat && !empty($messages)): ?>
let lastId = <?= end($messages)['id'] ?>;
setInterval(async () => {
  try {
    const res  = await fetch('../php/api/poll_messages.php?chat_id=<?= $activeChatId ?>&last_id=' + lastId);
    const data = await res.json();
    if (data.messages && data.messages.length) {
      data.messages.forEach(m => {
        lastId = m.id;
        const div = document.createElement('div');
        div.className = 'msg-bubble ' + (m.sender_id == <?= $userId ?> ? 'me' : 'them');
        div.innerHTML = m.content.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                       + '<div class="msg-bubble-time">Just now</div>';
        msgs.appendChild(div);
      });
      msgs.scrollTop = msgs.scrollHeight;
    }
  } catch(e) {}
}, 4000);
<?php endif; ?>

const ham = document.getElementById('navHamburger');
const navMenu = document.getElementById('navMobileMenu');
ham.addEventListener('click', () => { ham.classList.toggle('open'); navMenu.classList.toggle('open'); });
</script>
</body>
</html>
