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
  <link rel="stylesheet" href="../assets/css/settings.css">
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
          <?php $initials = strtoupper(substr($userObj->username ?? 'U', 0, 2)); ?>
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
        <p class="sf-sublabel" style="margin-top:14px;">
          To update your username or email, contact
          <a href="mailto:support@reventa.com" style="color:var(--text);">support@reventa.com</a>.
        </p>
      </div>
    </div>
  </div>

  <!-- ── Security ── -->
  <div class="settings-group">
    <div class="settings-group-label">Security</div>
    <div class="settings-card">
      <div class="card-inner">
        <div class="btn-row">
          <button class="sf-btn sf-btn-ghost" id="change_password_btn" onclick="
            document.getElementById('change_password_form').style.display='block';
            this.style.display='none';
          ">Change Password</button>
        </div>
        <div id="change_password_form" style="display:none;">
          <div class="settings-card-title" style="margin-top:20px;">Change Password</div>
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
              <button type="button" class="sf-btn sf-btn-ghost" onclick="
                document.getElementById('change_password_form').style.display='none';
                document.getElementById('change_password_btn').style.display='inline-block';
              ">Cancel</button>
            </div>
          </form>
        </div>
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

  <!-- ── Account Actions ── -->
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

<script src="../assets/js/settings.js"></script>
</body>
</html>