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

  <!-- ── Inbox Sidebar ───────────────────────────────────────── -->
  <div class="inbox-sidebar" id="inbox-sidebar">
    <div class="inbox-header">Messages</div>
                  <a class="chat-row active"
           href="messages.php?chat=3"
           data-chat="3">
          <img class="chat-row-avatar"
               src="https://ui-avatars.com/api/?name=Haydenee&background=111&color=fff&size=60"
               alt="">
          <div class="chat-row-body">
            <div class="chat-row-name">Haydenee</div>
                          <div class="chat-row-listing">📦 hayden · $123.00</div>
                        <div class="chat-row-preview">wejlj</div>
          </div>
                      <div class="chat-row-time">Mar 12</div>
                  </a>
            </div>

  <!-- ── Chat Pane ──────────────────────────────────────────── -->
  <div class="chat-pane" id="chat-pane">

    
    <!-- Chat header -->
    <div class="chat-header">
      <button class="chat-back" id="chat-back">‹ Back</button>
      <img class="chat-header-avatar"
           src="https://ui-avatars.com/api/?name=Haydenee&background=111&color=fff&size=60"
           alt="">
      <div>
        <div class="chat-header-name">Haydenee</div>
        <div class="chat-header-sub">Active now</div>
      </div>
    </div>

    <!-- Listing bar -->
        <a class="chat-listing-bar" href="listing.php?id=5">
              <img class="listing-thumb" src="/Reventa/uploads/listings/listing_5_69b2e711c11907.37696150.png" alt="">
            <div class="listing-bar-info">
        <div class="listing-bar-name">hayden</div>
        <div class="listing-bar-price">
          $123.00                  </div>
      </div>
      <span style="margin-left:auto;color:#aaa">›</span>
    </a>
    
    <!-- Messages -->
    <div class="chat-messages" id="chat-messages">
                                  <div class="msg-row mine" data-msg-id="5">
                        <div class="msg-bubble">wejlj</div>
            <div class="msg-time">12:52 PM</div>
          </div>
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
const CHAT_ID   = 3;
const MY_ID     = 5;
const MY_AVATAR = "https:\/\/ui-avatars.com\/api\/?name=Me&background=111&color=fff&size=60";

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
</body>
</html>