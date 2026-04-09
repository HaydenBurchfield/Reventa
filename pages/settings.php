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
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Settings — ReVènta</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/pages.css">
<style>
.settings-body { padding-top:68px; min-height:100vh; background:var(--off-white); }
.settings-wrap { max-width:680px; margin:0 auto; padding:56px 24px 96px; }
.settings-title { font-family:var(--serif); font-size:36px; font-weight:300; margin-bottom:40px; }
.settings-card { background:var(--white); border:1px solid rgba(0,0,0,.07); padding:32px; margin-bottom:16px; }
.settings-card-title { font-size:10px; font-weight:500; letter-spacing:.2em; text-transform:uppercase; color:var(--mid); margin-bottom:20px; border-bottom:1px solid var(--light); padding-bottom:12px; }
.sf-label { font-size:9px; font-weight:500; letter-spacing:.16em; text-transform:uppercase; color:var(--black); margin-bottom:8px; display:block; margin-top:16px; }
.sf-label:first-of-type { margin-top:0; }
.sf-input { width:100%; font-family:var(--sans); font-size:13px; font-weight:300; color:var(--black); border:1px solid #d0d0d0; padding:11px 14px; outline:none; border-radius:0; transition:border-color .2s; background:var(--white); }
.sf-input:focus { border-color:var(--black); }
.sf-btn { font-family:var(--sans); font-size:10px; font-weight:400; letter-spacing:.22em; text-transform:uppercase; color:var(--white); background:var(--black); border:none; padding:13px 28px; cursor:pointer; transition:opacity .2s; margin-top:20px; }
.sf-btn:hover { opacity:.75; }
.sf-btn-danger { background:var(--white); color:#c0392b; border:1px solid #c0392b; }
.sf-btn-danger:hover { background:#c0392b; color:var(--white); opacity:1; }
.alert { padding:10px 14px; font-size:12px; letter-spacing:.06em; border:1px solid; margin-bottom:20px; }
.alert.error   { color:#c0392b; border-color:#c0392b; background:#fdf2f1; }
.alert.success { color:#1a7a40; border-color:#1a7a40; background:#edf7f2; }
@media(max-width:767px){ .settings-body{padding-top:60px;} .settings-wrap{padding:32px 16px 56px;} }
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
  <h1 class="settings-title">Settings</h1>

  <?php if ($message): ?>
    <div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- Account Info -->
  <div class="settings-card">
    <div class="settings-card-title">Account</div>
    <label class="sf-label">Username</label>
    <input class="sf-input" type="text" value="@<?= htmlspecialchars($userObj->username) ?>" readonly>
    <label class="sf-label">Email</label>
    <input class="sf-input" type="email" value="<?= htmlspecialchars($userObj->email) ?>" readonly>
    <p style="font-size:11px;color:var(--mid);margin-top:12px;letter-spacing:.06em;">
      To change your username or email, contact support.
    </p>
  </div>

  <!-- Change Password -->
  <div class="settings-card">
    <div class="settings-card-title">Change Password</div>
    <form method="POST" action="settings.php">
      <input type="hidden" name="action" value="change_password">
      <label class="sf-label" for="current_password">Current Password</label>
      <input class="sf-input" type="password" id="current_password" name="current_password" required>
      <label class="sf-label" for="new_password">New Password</label>
      <input class="sf-input" type="password" id="new_password" name="new_password" required>
      <label class="sf-label" for="confirm_password">Confirm New Password</label>
      <input class="sf-input" type="password" id="confirm_password" name="confirm_password" required>
      <button type="submit" class="sf-btn">Update Password</button>
    </form>
  </div>

  <!-- Danger Zone -->
  <div class="settings-card">
    <div class="settings-card-title">Account Actions</div>
    <a href="../php/Utils/Logout.php" class="sf-btn" style="display:inline-block;text-decoration:none;margin-bottom:12px;">
      Logout
    </a>
  </div>
</div>

<script>
const ham = document.getElementById('navHamburger');
const menu = document.getElementById('navMobileMenu');
ham.addEventListener('click', () => { ham.classList.toggle('open'); menu.classList.toggle('open'); });
</script>
</body>
</html>
