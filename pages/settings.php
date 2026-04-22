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

    if ($_POST['action'] === 'edit_profile') {
        $userObj->full_name    = trim($_POST['full_name']    ?? '');
        $userObj->bio          = trim($_POST['bio']          ?? '');
        $userObj->phone_number = trim($_POST['phone_number'] ?? '');
        $userObj->adress       = trim($_POST['address']      ?? '');

        if (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mime    = mime_content_type($_FILES['avatar']['tmp_name']);
            if (in_array($mime, $allowed)) {
                $uploadDir = '../uploads/avatars/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext      = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $_SESSION['user_id'] . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $filename)) {
                    $userObj->profile_picture = 'uploads/avatars/' . $filename;
                }
            }
        }

        if ($userObj->updateProfile()) {
            $message = "Profile updated successfully.";
            $messageType = "success";
            $_SESSION['username'] = $userObj->username;
        } else {
            $message = "Failed to update profile.";
            $messageType = "error";
        }
    }

    if ($_POST['action'] === 'edit_account') {
        $newUsername = trim($_POST['username'] ?? '');
        $newEmail    = trim($_POST['email']    ?? '');

        if (empty($newUsername) || empty($newEmail)) {
            $message = "Username and email cannot be empty.";
            $messageType = "error";
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $messageType = "error";
        } else {
            $taken = $userObj->checkUsernameEmailTaken($newUsername, $newEmail, $_SESSION['user_id']);
            if ($taken === 'username') {
                $message = "That username is already taken.";
                $messageType = "error";
            } elseif ($taken === 'email') {
                $message = "That email is already in use.";
                $messageType = "error";
            } else {
                $userObj->username = $newUsername;
                $userObj->email    = $newEmail;
                if ($userObj->update()) {
                    $_SESSION['username'] = $newUsername;
                    $message = "Account updated successfully.";
                    $messageType = "success";
                } else {
                    $message = "Failed to update account.";
                    $messageType = "error";
                }
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
        <form method="POST" action="settings.php">
          <input type="hidden" name="action" value="edit_account">
          <div class="sf-grid">
            <div class="sf-row">
              <label class="sf-label" for="username">Username</label>
              <input class="sf-input" type="text" id="username" name="username"
                     value="<?= htmlspecialchars($userObj->username) ?>" required>
            </div>
            <div class="sf-row">
              <label class="sf-label" for="email">Email</label>
              <input class="sf-input" type="email" id="email" name="email"
                     value="<?= htmlspecialchars($userObj->email) ?>" required>
            </div>
          </div>
          <div class="btn-row" style="margin-top:16px;">
            <button type="submit" class="sf-btn">Save Account</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ── Edit Profile ── -->
  <div class="settings-group">
    <div class="settings-group-label">Profile</div>
    <div class="settings-card">
      <div class="card-inner">
        <form method="POST" action="settings.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="edit_profile">
          <div class="sf-grid" style="margin-bottom:16px;">
            <div class="sf-row">
              <label class="sf-label" for="full_name">Full Name</label>
              <input class="sf-input" type="text" id="full_name" name="full_name"
                     value="<?= htmlspecialchars($userObj->full_name ?? '') ?>">
            </div>
            <div class="sf-row">
              <label class="sf-label" for="phone_number">Phone</label>
              <input class="sf-input" type="text" id="phone_number" name="phone_number"
                     value="<?= htmlspecialchars($userObj->phone_number ?? '') ?>">
            </div>
          </div>
          <div class="sf-row" style="margin-bottom:16px;">
            <label class="sf-label" for="address">Address</label>
            <input class="sf-input" type="text" id="address" name="address"
                   value="<?= htmlspecialchars($userObj->adress ?? '') ?>">
          </div>
          <div class="sf-row" style="margin-bottom:16px;">
            <label class="sf-label" for="bio">Bio</label>
            <textarea class="sf-input" id="bio" name="bio"
                      style="resize:vertical;min-height:80px;font-family:inherit;"
            ><?= htmlspecialchars($userObj->bio ?? '') ?></textarea>
          </div>
          <div class="sf-row" style="margin-bottom:20px;">
            <label class="sf-label" for="avatar">Profile Photo</label>
            <input class="sf-input" type="file" id="avatar" name="avatar" accept="image/*"
                   style="padding:8px 14px;">
          </div>
          <div class="btn-row">
            <button type="submit" class="sf-btn">Save Changes</button>
          </div>
        </form>
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