<?php
require_once '../php/objects/User.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}

$userObj = new User();
$userObj->populate($_SESSION['user_id']);

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($new) < 6) {
            $message = "New password must be at least 6 characters.";
            $messageType = "error";
        } elseif ($new !== $confirm) {
            $message = "New passwords do not match.";
            $messageType = "error";
        } elseif (!password_verify($current, $userObj->password)) {
            $message = "Current password is incorrect.";
            $messageType = "error";
        } else {
            $userObj->password = $new;
            if ($userObj->update()) {
                $message = "Password updated successfully.";
                $messageType = "success";
            } else {
                $message = "Failed to update password.";
                $messageType = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Settings — ReVènta</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/pages.css">
<style>
/* ── Theme tokens ── */
:root {
  --bg:        #f7f6f3;
  --surface:   #ffffff;
  --surface2:  #f2f0ec;
  --border:    rgba(0,0,0,.08);
  --border-md: rgba(0,0,0,.14);
  --text:      #111110;
  --mid:       #7a7974;
  --muted:     #b0ada6;
  --accent:    #111110;
  --danger:    #c0392b;
  --success:   #1a7a40;
  --toggle-bg: #d8d5ce;
  --toggle-knob: #ffffff;
  --serif: 'Cormorant Garamond', Georgia, serif;
  --sans:  'Montserrat', sans-serif;
}
[data-theme="dark"] {
  --bg:        #111110;
  --surface:   #1c1b19;
  --surface2:  #252421;
  --border:    rgba(255,255,255,.07);
  --border-md: rgba(255,255,255,.13);
  --text:      #f0ede8;
  --mid:       #8a8780;
  --muted:     #5a5855;
  --accent:    #f0ede8;
  --toggle-bg: #3a3835;
  --toggle-knob: #f0ede8;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: var(--sans);
  background: var(--bg);
  color: var(--text);
  transition: background .3s, color .3s;
}

/* ── Layout ── */
.settings-body { padding-top: 68px; min-height: 100vh; }
.settings-wrap { max-width: 720px; margin: 0 auto; padding: 56px 24px 120px; }

/* ── Page header ── */
.settings-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-bottom: 48px;
  border-bottom: 1px solid var(--border-md);
  padding-bottom: 20px;
}
.settings-title {
  font-family: var(--serif);
  font-size: clamp(28px, 5vw, 42px);
  font-weight: 300;
  letter-spacing: -.01em;
}

/* ── Section groups ── */
.settings-group { margin-bottom: 8px; }
.settings-group-label {
  font-size: 9px;
  font-weight: 500;
  letter-spacing: .22em;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 8px;
  padding-left: 2px;
}

/* ── Cards ── */
.settings-card {
  background: var(--surface);
  border: 1px solid var(--border);
  padding: 0;
  margin-bottom: 2px;
  transition: background .3s, border-color .3s;
  overflow: hidden;
}
.settings-card:first-of-type { border-radius: 2px 2px 0 0; }
.settings-card:last-of-type  { border-radius: 0 0 2px 2px; margin-bottom: 32px; }
.settings-card:only-child    { border-radius: 2px; margin-bottom: 32px; }

.card-inner { padding: 24px 28px; }

.settings-card-title {
  font-size: 9px;
  font-weight: 500;
  letter-spacing: .2em;
  text-transform: uppercase;
  color: var(--mid);
  margin-bottom: 20px;
}

/* ── Form elements ── */
.sf-row { margin-bottom: 16px; }
.sf-row:last-of-type { margin-bottom: 0; }

.sf-label {
  font-size: 9px;
  font-weight: 500;
  letter-spacing: .16em;
  text-transform: uppercase;
  color: var(--text);
  margin-bottom: 7px;
  display: block;
}
.sf-sublabel {
  font-size: 10px;
  color: var(--mid);
  font-weight: 300;
  letter-spacing: .04em;
  margin-top: 3px;
}

.sf-input {
  width: 100%;
  font-family: var(--sans);
  font-size: 13px;
  font-weight: 300;
  color: var(--text);
  background: var(--surface);
  border: 1px solid var(--border-md);
  padding: 11px 14px;
  outline: none;
  border-radius: 0;
  transition: border-color .2s, background .3s, color .3s;
}
.sf-input:focus { border-color: var(--text); }
.sf-input[readonly] {
  background: var(--surface2);
  color: var(--mid);
  cursor: default;
}

.sf-select {
  width: 100%;
  font-family: var(--sans);
  font-size: 13px;
  font-weight: 300;
  color: var(--text);
  background: var(--surface);
  border: 1px solid var(--border-md);
  padding: 11px 14px;
  outline: none;
  border-radius: 0;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23888'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  cursor: pointer;
  transition: border-color .2s, background .3s, color .3s;
}
.sf-select:focus { border-color: var(--text); }

.sf-btn {
  font-family: var(--sans);
  font-size: 9px;
  font-weight: 500;
  letter-spacing: .22em;
  text-transform: uppercase;
  color: var(--bg);
  background: var(--accent);
  border: 1px solid var(--accent);
  padding: 13px 32px;
  cursor: pointer;
  transition: opacity .2s;
  display: inline-block;
  text-decoration: none;
}
.sf-btn:hover { opacity: .7; }

.sf-btn-ghost {
  background: transparent;
  color: var(--text);
  border-color: var(--border-md);
}
.sf-btn-ghost:hover { background: var(--surface2); opacity: 1; }

.sf-btn-danger {
  background: transparent;
  color: var(--danger);
  border-color: var(--danger);
}
.sf-btn-danger:hover { background: var(--danger); color: #fff; opacity: 1; }

.btn-row { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }

/* ── Divider ── */
.card-divider {
  height: 1px;
  background: var(--border);
  margin: 0 28px;
}

/* ── Toggle rows ── */
.toggle-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 28px;
  border-bottom: 1px solid var(--border);
  gap: 16px;
}
.toggle-row:last-child { border-bottom: none; }
.toggle-text { flex: 1; }
.toggle-title { font-size: 13px; font-weight: 300; color: var(--text); }
.toggle-desc  { font-size: 11px; color: var(--mid); margin-top: 2px; letter-spacing: .03em; }

.toggle {
  position: relative;
  width: 38px;
  height: 22px;
  flex-shrink: 0;
}
.toggle input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
  position: absolute;
  inset: 0;
  background: var(--toggle-bg);
  border-radius: 22px;
  cursor: pointer;
  transition: background .25s;
}
.toggle-slider::before {
  content: '';
  position: absolute;
  width: 16px;
  height: 16px;
  left: 3px;
  top: 3px;
  background: var(--toggle-knob);
  border-radius: 50%;
  transition: transform .25s;
}
.toggle input:checked + .toggle-slider { background: var(--text); }
.toggle input:checked + .toggle-slider::before { transform: translateX(16px); }

/* ── Size selector ── */
.size-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}
.size-chip {
  font-family: var(--sans);
  font-size: 10px;
  font-weight: 400;
  letter-spacing: .1em;
  padding: 7px 14px;
  border: 1px solid var(--border-md);
  background: transparent;
  color: var(--mid);
  cursor: pointer;
  transition: all .15s;
  border-radius: 0;
}
.size-chip.active,
.size-chip:hover {
  background: var(--text);
  color: var(--bg);
  border-color: var(--text);
}

/* ── Alerts ── */
.alert {
  padding: 12px 16px;
  font-size: 11px;
  letter-spacing: .06em;
  border: 1px solid;
  margin-bottom: 24px;
  font-weight: 300;
}
.alert.error   { color: var(--danger); border-color: var(--danger); background: rgba(192,57,43,.06); }
.alert.success { color: var(--success); border-color: var(--success); background: rgba(26,122,64,.06); }

/* ── Dark mode toggle in header ── */
.theme-toggle-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 9px;
  font-weight: 500;
  letter-spacing: .18em;
  text-transform: uppercase;
  color: var(--mid);
  background: none;
  border: 1px solid var(--border-md);
  padding: 8px 14px;
  cursor: pointer;
  transition: all .2s;
  font-family: var(--sans);
}
.theme-toggle-btn:hover { color: var(--text); border-color: var(--text); }
.theme-icon { font-size: 12px; }

/* ── Avatar initials ── */
.avatar {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: var(--surface2);
  border: 1px solid var(--border-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--serif);
  font-size: 18px;
  font-weight: 300;
  color: var(--mid);
  flex-shrink: 0;
}
.account-identity {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--border);
}
.account-name { font-family: var(--serif); font-size: 22px; font-weight: 300; }
.account-since { font-size: 10px; color: var(--mid); letter-spacing: .08em; margin-top: 3px; }

/* ── Grid for two-col ── */
.sf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
@media (max-width: 520px) { .sf-grid { grid-template-columns: 1fr; } }

/* ── Responsive ── */
@media (max-width: 767px) {
  .settings-body { padding-top: 60px; }
  .settings-wrap { padding: 32px 16px 80px; }
  .card-inner { padding: 20px 18px; }
  .toggle-row { padding: 14px 18px; }
  .card-divider { margin: 0 18px; }
}
</style>
</head>
<body class="settings-body">

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
    <a href="settings.php" class="active">Settings</a>
  </div>
  <button class="nav-hamburger" id="navHamburger"><span></span><span></span><span></span></button>
</nav>
<div class="nav-mobile-menu" id="navMobileMenu">
  <a href="../index.php">Home</a>
  <a href="explore.php">Explore</a>
  <a href="profile.php">Profile</a>
  <a href="settings.php">Settings</a>
  <a href="../php/Utils/Logout.php">Logout</a>
</div>

<div class="settings-wrap">

  <div class="settings-header">
    <h1 class="settings-title">Settings</h1>
    <button class="theme-toggle-btn" id="themeToggle">
      <span class="theme-icon" id="themeIcon">☾</span>
      <span id="themeLabel">Dark</span>
    </button>
  </div>

  <?php if ($message): ?>
    <div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- ── Account ── -->
  <div class="settings-group">
    <div class="settings-group-label">Account</div>
    <div class="settings-card">
      <div class="card-inner">
        <div class="account-identity">
          <?php
            $initials = strtoupper(substr($userObj->username ?? 'U', 0, 2));
          ?>
          <div class="avatar"><?= htmlspecialchars($initials) ?></div>
          <div>
            <div class="account-name"><?= htmlspecialchars($userObj->username) ?></div>
            <div class="account-since">Member since <?= date('F Y') ?></div>
          </div>
        </div>
        <div class="sf-grid">
          <div class="sf-row">
            <label class="sf-label">Username</label>
            <input class="sf-input" type="text" value="@<?= htmlspecialchars($userObj->username) ?>" readonly>
          </div>
          <div class="sf-row">
            <label class="sf-label">Email</label>
            <input class="sf-input" type="email" value="<?= htmlspecialchars($userObj->email) ?>" readonly>
          </div>
        </div>
        <p class="sf-sublabel" style="margin-top:14px;">To update your username or email, contact <a href="mailto:support@reventa.com" style="color:var(--text);">support@reventa.com</a>.</p>
      </div>
    </div>
  </div>

  <!-- ── Password ── -->
  <div class="settings-group">
    <div class="settings-group-label">Security</div>
    <div class="settings-card">
      <div class="card-inner">
        <div class="settings-card-title">Change Password</div>
        <form method="POST" action="settings.php">
          <input type="hidden" name="action" value="change_password">
          <div class="sf-row">
            <label class="sf-label" for="current_password">Current Password</label>
            <input class="sf-input" type="password" id="current_password" name="current_password" required autocomplete="current-password">
          </div>
          <div class="sf-grid">
            <div class="sf-row">
              <label class="sf-label" for="new_password">New Password</label>
              <input class="sf-input" type="password" id="new_password" name="new_password" required autocomplete="new-password">
            </div>
            <div class="sf-row">
              <label class="sf-label" for="confirm_password">Confirm Password</label>
              <input class="sf-input" type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
            </div>
          </div>
          <div class="btn-row">
            <button type="submit" class="sf-btn">Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ── Shopping Preferences ── -->
  <div class="settings-group">
    <div class="settings-group-label">Shopping Preferences</div>
    <div class="settings-card">
      <div class="card-inner">
        <div class="settings-card-title">Sizing</div>
        <div class="sf-row">
          <label class="sf-label">Tops &amp; Outerwear</label>
          <div class="size-grid" id="sizesTops">
            <?php foreach (['XS','S','M','L','XL','XXL'] as $s): ?>
              <button class="size-chip <?= $s === 'M' ? 'active' : '' ?>" data-size="<?= $s ?>"><?= $s ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="sf-row" style="margin-top:16px;">
          <label class="sf-label">Bottoms</label>
          <div class="size-grid" id="sizesBottoms">
            <?php foreach (['28','30','32','34','36','38','40'] as $s): ?>
              <button class="size-chip <?= $s === '32' ? 'active' : '' ?>" data-size="<?= $s ?>"><?= $s ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="sf-row" style="margin-top:16px;">
          <label class="sf-label">Shoes (EU)</label>
          <div class="size-grid" id="sizesShoes">
            <?php foreach (['38','39','40','41','42','43','44','45'] as $s): ?>
              <button class="size-chip" data-size="<?= $s ?>"><?= $s ?></button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="card-divider"></div>
      <div class="card-inner" style="padding-top:20px;">
        <div class="settings-card-title">Style &amp; Browse</div>
        <div class="sf-grid">
          <div class="sf-row">
            <label class="sf-label" for="defaultCategory">Default category</label>
            <select class="sf-select" id="defaultCategory">
              <option>All</option>
              <option>Men</option>
              <option>Women</option>
              <option>Kids</option>
            </select>
          </div>
          <div class="sf-row">
            <label class="sf-label" for="currency">Currency</label>
            <select class="sf-select" id="currency">
              <option>USD — $</option>
              <option>EUR — €</option>
              <option>GBP — £</option>
              <option>CAD — C$</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Notifications ── -->
  <div class="settings-group">
    <div class="settings-group-label">Notifications</div>
    <div class="settings-card">
      <div class="toggle-row">
        <div class="toggle-text">
          <div class="toggle-title">Order updates</div>
          <div class="toggle-desc">Shipping confirmations and delivery notices</div>
        </div>
        <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
      </div>
      <div class="toggle-row">
        <div class="toggle-text">
          <div class="toggle-title">New listings in my size</div>
          <div class="toggle-desc">Alerts when items matching your saved sizes are listed</div>
        </div>
        <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
      </div>
      <div class="toggle-row">
        <div class="toggle-text">
          <div class="toggle-title">Price drops on saved items</div>
          <div class="toggle-desc">Notify when a wishlist item drops in price</div>
        </div>
        <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
      </div>
      <div class="toggle-row">
        <div class="toggle-text">
          <div class="toggle-title">Seller messages</div>
          <div class="toggle-desc">Direct messages about your listings or purchases</div>
        </div>
        <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
      </div>
      <div class="toggle-row">
        <div class="toggle-text">
          <div class="toggle-title">Promotional emails</div>
          <div class="toggle-desc">Sales, drops, and curated edits from ReVènta</div>
        </div>
        <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
      </div>
    </div>
  </div>

  <!-- ── Selling ── -->
  <div class="settings-group">
    <div class="settings-group-label">Selling</div>
    <div class="settings-card">
      <div class="toggle-row">
        <div class="toggle-text">
          <div class="toggle-title">Auto-accept offers within 10% of asking price</div>
          <div class="toggle-desc">Automatically accept close offers on your listings</div>
        </div>
        <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
      </div>
      <div class="toggle-row">
        <div class="toggle-text">
          <div class="toggle-title">Show "Open to Offers" badge</div>
          <div class="toggle-desc">Visible on all your active listings</div>
        </div>
        <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
      </div>
      <div class="card-inner" style="border-top: 1px solid var(--border); padding-top:20px;">
        <div class="sf-row">
          <label class="sf-label" for="payoutMethod">Payout method</label>
          <select class="sf-select" id="payoutMethod">
            <option>Bank transfer</option>
            <option>PayPal</option>
            <option>Store credit (+5% bonus)</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Appearance ── -->
  <div class="settings-group">
    <div class="settings-group-label">Appearance</div>
    <div class="settings-card">
      <div class="toggle-row">
        <div class="toggle-text">
          <div class="toggle-title">Dark mode</div>
          <div class="toggle-desc">Switch to a dark colour scheme</div>
        </div>
        <label class="toggle">
          <input type="checkbox" id="darkModeToggle">
          <span class="toggle-slider"></span>
        </label>
      </div>
      <div class="toggle-row">
        <div class="toggle-text">
          <div class="toggle-title">Compact listing view</div>
          <div class="toggle-desc">Show more items per row when browsing</div>
        </div>
        <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
      </div>
    </div>
  </div>

  <!-- ── Privacy ── -->
  <div class="settings-group">
    <div class="settings-group-label">Privacy</div>
    <div class="settings-card">
      <div class="toggle-row">
        <div class="toggle-text">
          <div class="toggle-title">Public profile</div>
          <div class="toggle-desc">Other users can view your profile and listings</div>
        </div>
        <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
      </div>
      <div class="toggle-row">
        <div class="toggle-text">
          <div class="toggle-title">Show purchase history</div>
          <div class="toggle-desc">Display items you've bought on your profile</div>
        </div>
        <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
      </div>
      <div class="toggle-row">
        <div class="toggle-text">
          <div class="toggle-title">Personalised recommendations</div>
          <div class="toggle-desc">Use my browsing data to tailor suggestions</div>
        </div>
        <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
      </div>
    </div>
  </div>

  <!-- ── Account actions ── -->
  <div class="settings-group">
    <div class="settings-group-label">Account actions</div>
    <div class="settings-card">
      <div class="card-inner">
        <div class="btn-row">
          <a href="../php/Utils/Logout.php" class="sf-btn sf-btn-ghost">Logout</a>
          <button class="sf-btn sf-btn-danger" onclick="return confirm('Are you sure you want to delete your account? This cannot be undone.')">Delete Account</button>
        </div>
      </div>
    </div>
  </div>

</div><!-- /settings-wrap -->

</body>
</html>