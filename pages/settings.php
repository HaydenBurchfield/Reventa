<?php
require_once '../config.php';
require_once '../php/objects/User.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user = new User();
$user->populate($_SESSION['user_id']);

$error   = '';
$success = '';
$section = $_GET['section'] ?? 'account';

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Account: username + email
    if ($action === 'account') {
        $newUsername = trim($_POST['username'] ?? '');
        $newEmail    = trim($_POST['email']    ?? '');

        if (empty($newUsername) || empty($newEmail)) {
            $error = 'Username and email are required.';
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($newUsername) > 50) {
            $error = 'Username must be 50 characters or fewer.';
        } else {
            // Only check uniqueness if values changed
            $changed = ($newUsername !== $user->username || $newEmail !== $user->email);
            if ($changed && User::exists($newEmail, $newUsername)) {
                $error = 'That username or email is already taken.';
            } else {
                $user->username = $newUsername;
                $user->email    = $newEmail;
                // Keep existing password hash (update() re-hashes, so pass the stored hash)
                // To avoid double-hashing, we use a raw query approach via the model's update():
                // update() calls password_hash() again — so we need to store plaintext temporarily.
                // Since we're not changing password here, skip update() and do a targeted query.
                $conn = (new DatabaseConnection())->connect();
                $stmt = $conn->prepare("UPDATE user SET username=?, email=? WHERE id=?");
                $stmt->bind_param("ssi", $newUsername, $newEmail, $user->id);
                if ($stmt->execute()) {
                    $_SESSION['username'] = $newUsername;
                    $success = 'Account updated successfully.';
                    $user->populate($user->id);
                } else {
                    $error = 'Failed to update. Please try again.';
                }
                $stmt->close();
            }
        }
        $section = 'account';
    }

    // Password change
    if ($action === 'password') {
        $current  = $_POST['current_password']  ?? '';
        $newPass  = $_POST['new_password']       ?? '';
        $confirm  = $_POST['confirm_password']   ?? '';

        $dbUser = $user->validateUser($user->email, $current);
        if (!$dbUser) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($newPass !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $conn = (new DatabaseConnection())->connect();
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE user SET password=? WHERE id=?");
            $stmt->bind_param("si", $hash, $user->id);
            $success = $stmt->execute() ? 'Password changed successfully.' : 'Failed to update password.';
            $stmt->close();
        }
        $section = 'security';
    }

    // Delete account
    if ($action === 'delete') {
        $confirmWord = trim($_POST['confirm_delete'] ?? '');
        if ($confirmWord !== 'DELETE') {
            $error = 'Type DELETE exactly to confirm.';
            $section = 'danger';
        } else {
            $conn = (new DatabaseConnection())->connect();
            $stmt = $conn->prepare("DELETE FROM user WHERE id=?");
            $stmt->bind_param("i", $user->id);
            if ($stmt->execute()) {
                session_destroy();
                header('Location: ../index.php?deleted=1');
                exit();
            } else {
                $error = 'Could not delete account. Please try again.';
                $section = 'danger';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>ReVenta — Settings</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/styles.css">
<style>
/* ── Settings Layout ─────────────────────────────── */
* { box-sizing: border-box; }

.settings-wrap {
  max-width: 480px;
  margin: 0 auto;
  padding: 0 0 100px;
  font-family: 'DM Sans', sans-serif;
}

/* Page header */
.settings-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 16px 8px;
  position: sticky;
  top: 0;
  background: #fff;
  z-index: 10;
  border-bottom: 1px solid #f0f0f0;
}
.settings-back {
  width: 36px; height: 36px;
  border-radius: 50%;
  border: 1.5px solid #e8e8e8;
  background: #fff;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  text-decoration: none;
  color: #111;
  flex-shrink: 0;
  transition: background .15s, border-color .15s;
}
.settings-back:hover { background: #f5f5f5; border-color: #ccc; }
.settings-header h1 {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 26px;
  letter-spacing: 0.04em;
  margin: 0;
  color: #111;
}

/* User identity strip */
.settings-identity {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px 14px;
  margin: 12px 16px;
  background: #f7f7f7;
  border-radius: 14px;
}
.settings-avatar {
  width: 48px; height: 48px; border-radius: 50%;
  object-fit: cover; flex-shrink: 0; border: 2px solid #e0e0e0;
}
.settings-identity-name { font-size: 15px; font-weight: 500; margin: 0 0 2px; color: #111; }
.settings-identity-email { font-size: 12px; color: #888; margin: 0; }

/* Nav pills */
.settings-nav {
  display: flex;
  gap: 8px;
  padding: 4px 16px 12px;
  overflow-x: auto;
  scrollbar-width: none;
}
.settings-nav::-webkit-scrollbar { display: none; }
.settings-nav a {
  flex-shrink: 0;
  padding: 7px 16px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  color: #555;
  background: #f2f2f2;
  transition: all .15s;
  white-space: nowrap;
}
.settings-nav a.active { background: #111; color: #fff; }
.settings-nav a:hover:not(.active) { background: #e5e5e5; }

/* Section card */
.settings-section {
  margin: 0 16px 16px;
  background: #fff;
  border: 1px solid #ebebeb;
  border-radius: 16px;
  overflow: hidden;
}
.settings-section-title {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #999;
  padding: 16px 18px 6px;
}

/* Form elements */
.sf-group {
  padding: 6px 18px 14px;
}
.sf-group + .sf-group {
  border-top: 1px solid #f5f5f5;
  padding-top: 14px;
}
.sf-label {
  font-size: 12px;
  font-weight: 500;
  color: #888;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.sf-input {
  width: 100%;
  padding: 10px 13px;
  border: 1.5px solid #e8e8e8;
  border-radius: 10px;
  font-size: 14px;
  font-family: 'DM Sans', sans-serif;
  color: #111;
  background: #fafafa;
  outline: none;
  transition: border-color .15s, background .15s;
}
.sf-input:focus { border-color: #111; background: #fff; }
.sf-input::placeholder { color: #bbb; }
.sf-hint {
  font-size: 11px;
  color: #bbb;
  margin-top: 5px;
}

/* Password strength bar */
.pw-strength-bar {
  height: 3px;
  border-radius: 2px;
  background: #eee;
  margin-top: 6px;
  overflow: hidden;
}
.pw-strength-fill {
  height: 100%;
  border-radius: 2px;
  width: 0;
  transition: width .3s, background .3s;
}

/* Row item (toggle-style) */
.settings-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px;
  cursor: pointer;
  transition: background .12s;
}
.settings-row:not(:last-child) { border-bottom: 1px solid #f5f5f5; }
.settings-row:hover { background: #fafafa; }
.settings-row:active { background: #f2f2f2; }
.sr-left { display: flex; align-items: center; gap: 12px; }
.sr-icon {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; flex-shrink: 0;
}
.sr-icon.gray   { background: #f2f2f2; }
.sr-icon.red    { background: #fff0f0; }
.sr-icon.blue   { background: #eff4ff; }
.sr-icon.green  { background: #effff6; }
.sr-icon.amber  { background: #fffbee; }
.sr-text { font-size: 14px; font-weight: 400; color: #111; }
.sr-sub  { font-size: 11px; color: #aaa; margin-top: 1px; }
.sr-arrow { color: #ccc; font-size: 18px; line-height: 1; }

/* Save button */
.btn-save-settings {
  width: 100%;
  padding: 13px;
  background: #111;
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 500;
  font-family: 'DM Sans', sans-serif;
  cursor: pointer;
  transition: opacity .15s, transform .1s;
  margin: 4px 0 0;
}
.btn-save-settings:hover  { opacity: .88; }
.btn-save-settings:active { transform: scale(.98); }

/* Danger button */
.btn-danger {
  width: 100%;
  padding: 13px;
  background: #fff;
  color: #d94040;
  border: 1.5px solid #f5c5c5;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 500;
  font-family: 'DM Sans', sans-serif;
  cursor: pointer;
  transition: all .15s;
  margin: 4px 0 0;
}
.btn-danger:hover { background: #fff5f5; border-color: #e88; }

/* Alerts */
.s-alert {
  margin: 0 16px 12px;
  padding: 11px 14px;
  border-radius: 11px;
  font-size: 13px;
  font-weight: 400;
  display: flex;
  align-items: center;
  gap: 8px;
}
.s-alert-success { background: #f0fdf4; color: #1a7a40; border: 1px solid #bbf7d0; }
.s-alert-error   { background: #fff5f5; color: #c0392b; border: 1px solid #fecaca; }

/* Section footer (inside card) */
.sf-footer {
  padding: 0 18px 16px;
}

/* Divider */
.s-divider {
  height: 1px;
  background: #f0f0f0;
  margin: 0 18px;
}

/* Delete confirm field */
.delete-confirm-wrap {
  display: none;
  padding: 0 18px 16px;
}
.delete-confirm-wrap.open { display: block; }

/* Logout */
.btn-logout {
  width: calc(100% - 32px);
  margin: 0 16px;
  padding: 13px;
  background: #fff;
  color: #555;
  border: 1.5px solid #e8e8e8;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 400;
  font-family: 'DM Sans', sans-serif;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-decoration: none;
  transition: background .15s;
}
.btn-logout:hover { background: #f7f7f7; }

/* Toggle switch */
.toggle { position: relative; display: inline-block; width: 44px; height: 24px; }
.toggle input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
  position: absolute; inset: 0; cursor: pointer;
  background: #ddd; border-radius: 24px; transition: .2s;
}
.toggle-slider:before {
  content: ""; position: absolute;
  height: 18px; width: 18px; left: 3px; bottom: 3px;
  background: white; border-radius: 50%; transition: .2s;
}
.toggle input:checked + .toggle-slider { background: #111; }
.toggle input:checked + .toggle-slider:before { transform: translateX(20px); }

/* Version tag */
.version-tag {
  text-align: center;
  font-size: 11px;
  color: #ccc;
  padding: 12px 0 4px;
}
</style>
</head>
<body>
<nav id="top-nav">
      <div class="nav-logo"><a href="../index.php"><img src="../assets/img/logo.png" alt="ReVenta Logo" id="logo"></a></div>
      <div class="nav-search"><input type="text" id="search-input" placeholder="Search items, brands, sellers..."></div>
      <div class="nav-links">
        <a href="../index.php" class="nav-tab-link">Home</a>
        <a href="../pages/explore.php" class="nav-tab-link">Explore</a>
        <a href="../pages/messages.php" class="nav-tab-link">Messages</a>
        <a href="../pages/profile.php" class="nav-tab-link">Profile</a>
        <a href="../pages/likes.php" class="nav-tab-link">My Likes</a>
      </div>
      <a href="../pages/sell.php"><button class="btn-sell">+ Sell</button></a>
    </nav>
<div class="settings-wrap">

  <!-- Header -->
  <div class="settings-header">
    <a class="settings-back" href="profile.php" aria-label="Back">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <h1>Settings</h1>
  </div>

  <!-- Identity strip -->
  <div class="settings-identity">
    <img class="settings-avatar"
         src="<?php
           $pic = $user->profile_picture ?? null;
           echo $pic ? htmlspecialchars($pic)
                     : 'https://ui-avatars.com/api/?name=' . urlencode($user->username ?: 'U') . '&background=111&color=fff&size=96';
         ?>" alt="avatar">
    <div>
      <p class="settings-identity-name">@<?= htmlspecialchars($user->username ?? '') ?></p>
      <p class="settings-identity-email"><?= htmlspecialchars($user->email ?? '') ?></p>
    </div>
  </div>

  <!-- Nav -->
  <nav class="settings-nav">
    <a href="?section=account"   class="<?= $section==='account'  ?'active':'' ?>">Account</a>
    <a href="?section=security"  class="<?= $section==='security' ?'active':'' ?>">Security</a>
    <a href="?section=prefs"     class="<?= $section==='prefs'    ?'active':'' ?>">Preferences</a>
    <a href="?section=danger"    class="<?= $section==='danger'   ?'active':'' ?>">Danger Zone</a>
  </nav>

  <!-- Alerts -->
  <?php if ($success): ?>
    <div class="s-alert s-alert-success">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="s-alert s-alert-error">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <!-- ═══════════════ ACCOUNT ═══════════════ -->
  <?php if ($section === 'account'): ?>
  <form method="POST" action="settings.php?section=account">
    <input type="hidden" name="action" value="account">

    <div class="settings-section">
      <p class="settings-section-title">Login Info</p>

      <div class="sf-group">
        <div class="sf-label">Username</div>
        <input class="sf-input" type="text" name="username"
               value="<?= htmlspecialchars($user->username ?? '') ?>"
               maxlength="50" autocomplete="username" spellcheck="false">
        <p class="sf-hint">Only letters, numbers, underscores. Shown publicly.</p>
      </div>

      <div class="sf-group">
        <div class="sf-label">Email address</div>
        <input class="sf-input" type="email" name="email"
               value="<?= htmlspecialchars($user->email ?? '') ?>"
               maxlength="100" autocomplete="email">
      </div>

      <div class="sf-footer">
        <button type="submit" class="btn-save-settings">Save changes</button>
      </div>
    </div>
  </form>

  <div class="settings-section">
    <p class="settings-section-title">Profile</p>
    <a class="settings-row" href="profile.php" style="text-decoration:none">
      <div class="sr-left">
        <div class="sr-icon blue">👤</div>
        <div>
          <div class="sr-text">Edit profile</div>
          <div class="sr-sub">Name, bio, photo, address</div>
        </div>
      </div>
      <span class="sr-arrow">›</span>
    </a>
  </div>

  <!-- ═══════════════ SECURITY ═══════════════ -->
  <?php elseif ($section === 'security'): ?>
  <form method="POST" action="settings.php?section=security">
    <input type="hidden" name="action" value="password">

    <div class="settings-section">
      <p class="settings-section-title">Change Password</p>

      <div class="sf-group">
        <div class="sf-label">Current password</div>
        <input class="sf-input" type="password" name="current_password"
               autocomplete="current-password" placeholder="••••••••">
      </div>

      <div class="sf-group">
        <div class="sf-label">
          New password
          <span id="pw-strength-label" style="font-size:11px;color:#aaa;font-weight:400"></span>
        </div>
        <input class="sf-input" type="password" name="new_password"
               id="new_password" autocomplete="new-password" placeholder="Min. 8 characters">
        <div class="pw-strength-bar"><div class="pw-strength-fill" id="pw-bar"></div></div>
      </div>

      <div class="sf-group">
        <div class="sf-label">Confirm new password</div>
        <input class="sf-input" type="password" name="confirm_password"
               id="confirm_password" autocomplete="new-password" placeholder="Repeat password">
        <p class="sf-hint" id="pw-match-hint" style="color:#bbb"></p>
      </div>

      <div class="sf-footer">
        <button type="submit" class="btn-save-settings">Update password</button>
      </div>
    </div>
  </form>

  <div class="settings-section">
    <p class="settings-section-title">Sessions</p>
    <div class="settings-row" style="cursor:default">
      <div class="sr-left">
        <div class="sr-icon green">🔒</div>
        <div>
          <div class="sr-text">Current session</div>
          <div class="sr-sub">Logged in as <?= htmlspecialchars($user->username ?? '') ?></div>
        </div>
      </div>
      <span style="font-size:11px;padding:4px 10px;background:#f0fdf4;color:#1a7a40;border-radius:20px;font-weight:500">Active</span>
    </div>
  </div>

  <!-- ═══════════════ PREFERENCES ═══════════════ -->
  <?php elseif ($section === 'prefs'): ?>
  <div class="settings-section">
    <p class="settings-section-title">Notifications</p>
    <div class="settings-row">
      <div class="sr-left">
        <div class="sr-icon blue">🔔</div>
        <div>
          <div class="sr-text">New messages</div>
          <div class="sr-sub">When someone messages you</div>
        </div>
      </div>
      <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
    </div>
    <div class="settings-row">
      <div class="sr-left">
        <div class="sr-icon amber">❤️</div>
        <div>
          <div class="sr-text">Likes on listings</div>
          <div class="sr-sub">When someone likes your item</div>
        </div>
      </div>
      <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
    </div>
    <div class="settings-row">
      <div class="sr-left">
        <div class="sr-icon green">🛒</div>
        <div>
          <div class="sr-text">Sale completed</div>
          <div class="sr-sub">When your item sells</div>
        </div>
      </div>
      <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
    </div>
    <div class="settings-row" style="border-top:1px solid #f5f5f5">
      <div class="sr-left">
        <div class="sr-icon gray">📧</div>
        <div>
          <div class="sr-text">Email digest</div>
          <div class="sr-sub">Weekly summary of activity</div>
        </div>
      </div>
      <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
    </div>
  </div>

  <div class="settings-section">
    <p class="settings-section-title">Display</p>
    <div class="settings-row">
      <div class="sr-left">
        <div class="sr-icon gray">🌙</div>
        <div>
          <div class="sr-text">Dark mode</div>
          <div class="sr-sub">Easy on the eyes at night</div>
        </div>
      </div>
      <label class="toggle"><input type="checkbox" id="dark-toggle"><span class="toggle-slider"></span></label>
    </div>
  </div>

  <div class="settings-section">
    <p class="settings-section-title">About</p>
    <a class="settings-row" href="#" style="text-decoration:none">
      <div class="sr-left">
        <div class="sr-icon gray">📄</div>
        <div><div class="sr-text">Privacy policy</div></div>
      </div>
      <span class="sr-arrow">›</span>
    </a>
    <a class="settings-row" href="#" style="text-decoration:none">
      <div class="sr-left">
        <div class="sr-icon gray">📋</div>
        <div><div class="sr-text">Terms of service</div></div>
      </div>
      <span class="sr-arrow">›</span>
    </a>
    <a class="settings-row" href="#" style="text-decoration:none">
      <div class="sr-left">
        <div class="sr-icon gray">💬</div>
        <div><div class="sr-text">Send feedback</div></div>
      </div>
      <span class="sr-arrow">›</span>
    </a>
  </div>
  <p class="version-tag">ReVenta v1.0</p>

  <!-- ═══════════════ DANGER ZONE ═══════════════ -->
  <?php elseif ($section === 'danger'): ?>
  <div class="settings-section">
    <p class="settings-section-title">Danger Zone</p>

    <div class="settings-row" id="deactivate-row" style="cursor:default">
      <div class="sr-left">
        <div class="sr-icon amber">⏸️</div>
        <div>
          <div class="sr-text">Deactivate account</div>
          <div class="sr-sub">Hide your profile temporarily</div>
        </div>
      </div>
      <button onclick="alert('Contact support to deactivate your account.')"
              style="padding:6px 14px;border-radius:20px;border:1.5px solid #f5c5c5;background:#fff;color:#d94040;font-size:12px;font-weight:500;cursor:pointer;font-family:inherit;">
        Deactivate
      </button>
    </div>

    <div class="s-divider" style="margin:0"></div>

    <div class="settings-row" id="delete-toggle-row" onclick="toggleDelete()" style="border-bottom:none">
      <div class="sr-left">
        <div class="sr-icon red">🗑️</div>
        <div>
          <div class="sr-text" style="color:#d94040;font-weight:500">Delete account</div>
          <div class="sr-sub">Permanent — cannot be undone</div>
        </div>
      </div>
      <span class="sr-arrow" id="delete-arrow">›</span>
    </div>

    <div class="delete-confirm-wrap" id="delete-confirm">
      <form method="POST" action="settings.php?section=danger">
        <input type="hidden" name="action" value="delete">
        <p style="font-size:13px;color:#888;margin:0 0 10px;line-height:1.5">
          This will permanently delete your account, all listings, and all data.
          Type <strong style="color:#111">DELETE</strong> to confirm.
        </p>
        <input class="sf-input" type="text" name="confirm_delete"
               placeholder="Type DELETE here" style="margin-bottom:10px"
               autocomplete="off" spellcheck="false">
        <button type="submit" class="btn-danger">Yes, delete my account forever</button>
      </form>
    </div>
  </div>

  <?php endif; ?>

  <!-- Logout button (always visible) -->
  <div style="margin-top: 8px;">
    <a class="btn-logout" href="../logout.php">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Log out
    </a>
  </div>

</div><!-- /settings-wrap -->

<!-- Bottom nav (matching profile.php) -->
<nav id="bottom-nav">
  <a class="bottom-item" href="../index.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><div class="bottom-label">Home</div></a>
  <a class="bottom-item" href="explore.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div><div class="bottom-label">Explore</div></a>
  <a class="bottom-item sell-btn" href="sell.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div><div class="bottom-label">Sell</div></a>
  <a class="bottom-item" href="likes.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div><div class="bottom-label">Likes</div></a>
  <a class="bottom-item active" href="profile.php"><div class="bottom-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div class="bottom-label">Profile</div></a>
</nav>

<script>
// Password strength meter
const newPw  = document.getElementById('new_password');
const confPw = document.getElementById('confirm_password');
const bar    = document.getElementById('pw-bar');
const label  = document.getElementById('pw-strength-label');
const hint   = document.getElementById('pw-match-hint');

if (newPw) {
  newPw.addEventListener('input', function() {
    const v = this.value;
    let score = 0;
    if (v.length >= 8)  score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const widths = ['0%', '25%', '50%', '75%', '100%'];
    const colors = ['#eee','#e74c3c','#e67e22','#f1c40f','#2ecc71'];
    const labels = ['','Weak','Fair','Good','Strong'];
    if (bar) { bar.style.width = widths[score]; bar.style.background = colors[score]; }
    if (label) label.textContent = labels[score];
  });
}
if (confPw) {
  confPw.addEventListener('input', function() {
    if (!hint || !newPw) return;
    if (this.value === '') { hint.textContent = ''; return; }
    if (this.value === newPw.value) {
      hint.textContent = '✓ Passwords match'; hint.style.color = '#2ecc71';
    } else {
      hint.textContent = '✗ Passwords do not match'; hint.style.color = '#e74c3c';
    }
  });
}

// Delete confirm toggle
function toggleDelete() {
  const wrap  = document.getElementById('delete-confirm');
  const arrow = document.getElementById('delete-arrow');
  const open  = wrap.classList.toggle('open');
  if (arrow) arrow.textContent = open ? '⌄' : '›';
}

// Dark mode (UI-only toggle for preference section)
const darkToggle = document.getElementById('dark-toggle');
if (darkToggle) {
  darkToggle.addEventListener('change', function() {
    document.body.style.background = this.checked ? '#111' : '';
    document.body.style.color      = this.checked ? '#eee' : '';
  });
}
</script>
</body>
</html>