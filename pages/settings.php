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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

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
            $changed = ($newUsername !== $user->username || $newEmail !== $user->email);
            if ($changed && User::exists($newEmail, $newUsername)) {
                $error = 'That username or email is already taken.';
            } else {
                $user->username = $newUsername;
                $user->email    = $newEmail;
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
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/styles.css">
<style>
/* ── Variables ───────────────────────────── */
:root {
  --black:     #0D0D0D;
  --charcoal:  #2C2C2C;
  --mid:       #6B6B6B;
  --light:     #C0C0C0;
  --border:    #E8E8E8;
  --bg:        #F5F5F5;
  --white:     #FFFFFF;
  --success:   #1A7A40;
  --success-bg:#EDF7F2;
  --danger:    #C0392B;
  --danger-bg: #FDF2F1;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

html, body { height: 100%; }

body {
  background: var(--bg);
  font-family: 'Barlow', sans-serif;
  color: var(--black);
  -webkit-font-smoothing: antialiased;
}

/* ══════════════════════════════════════════
   MOBILE-FIRST BASE
══════════════════════════════════════════ */

.settings-wrap {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* ── Top Header ─────────────────────────── */
.settings-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  background: var(--white);
  border-bottom: 2px solid var(--black);
  position: sticky;
  top: 0;
  z-index: 30;
}

.settings-back {
  width: 36px; height: 36px;
  border: 2px solid var(--black);
  background: var(--white);
  display: flex; align-items: center; justify-content: center;
  text-decoration: none;
  color: var(--black);
  flex-shrink: 0;
  transition: background .12s, color .12s;
}
.settings-back:hover { background: var(--black); color: var(--white); }

.settings-header h1 {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 26px;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--black);
  flex: 1;
}

/* ── Identity Strip ─────────────────────── */
.settings-identity {
  display: flex;
  align-items: center;
  background: var(--white);
  overflow: hidden;
}

.identity-bar {
  width: 5px;
  background: var(--charcoal);
  align-self: stretch;
  flex-shrink: 0;
}

.identity-content {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 20px;
  flex: 1;
}

.settings-avatar {
  width: 46px; height: 46px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
  border: 2px solid var(--charcoal);
}

.settings-identity-name {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 17px;
  font-weight: 700;
  letter-spacing: 0.04em;
  color:black var(--white);
  text-transform: uppercase;
}

.settings-identity-email {
  font-size: 12px;
  color: var(--light);
  margin-top: 2px;
  font-weight: 300;
}

/* ── Mobile Nav ─────────────────────────── */
.settings-nav {
  display: flex;
  padding: 0 16px;
  overflow-x: auto;
  scrollbar-width: none;
  background: var(--white);
  border-bottom: 1px solid var(--border);
  gap: 0;
}
.settings-nav::-webkit-scrollbar { display: none; }

.settings-nav a {
  flex-shrink: 0;
  padding: 12px 16px;
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  text-decoration: none;
  color: var(--mid);
  border-bottom: 3px solid transparent;
  transition: all .15s;
  white-space: nowrap;
  margin-bottom: -1px;
}
.settings-nav a.active  { color: var(--black); border-bottom-color: var(--black); }
.settings-nav a:hover:not(.active) { color: var(--charcoal); }

/* ── Mobile Main ─────────────────────────── */
.desktop-layout {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.desktop-sidebar { display: none; }

.desktop-main {
  flex: 1;
  padding: 16px 16px 100px;
  max-width: auto;
}

/* ── Mobile Logout (fixed bottom) ─────────── */
.logout-wrap {
  position: fixed;
  bottom: 60px; /* above bottom-nav */
  left: 0; right: 0;
  background: var(--white);
  border-top: 1px solid var(--border);
  padding: 10px 16px;
  z-index: 25;
}

/* ══════════════════════════════════════════
   DESKTOP (≥ 768px)
══════════════════════════════════════════ */
@media (min-width: 768px) {

  /* Kill mobile bottom nav & fixed logout */
  #bottom-nav   { display: none !important; }
  .logout-wrap  { display: none; }

  .settings-header {
    position: sticky;
    top: 0;
    padding: 18px 40px;
  }
  .settings-header h1 { font-size: 30px; }

  .settings-identity .identity-content { padding: 16px 40px; }

  /* Hide mobile scrolling nav — sidebar takes over */
  .settings-nav { display: none; }

  /* Two-column body */
  .desktop-layout {
    flex-direction: row;
    align-items: stretch;
    flex: 1;
  }

  /* ── Sidebar ── */
  .desktop-sidebar {
    display: flex;
    flex-direction: column;
    width: 240px;
    min-width: 240px;
    background: var(--white);
    border-right: 1.5px solid var(--border);
    position: sticky;
    top: 83px; /* header height */
    height: calc(100vh - 83px);
    overflow: hidden;
  }

  .sidebar-nav {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 20px 0;
    overflow-y: auto;
  }

  .sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 24px;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    text-decoration: none;
    color: var(--mid);
    border-left: 3px solid transparent;
    transition: all .12s;
  }
  .sidebar-nav a:hover:not(.active) {
    background: var(--bg);
    color: var(--charcoal);
    border-left-color: var(--light);
  }
  .sidebar-nav a.active {
    background: var(--bg);
    color: var(--black);
    border-left-color: var(--black);
  }

  .sidebar-nav .nav-icon {
    font-size: 16px;
    width: 20px;
    text-align: center;
    flex-shrink: 0;
  }

  /* Logout locked to sidebar bottom */
  .sidebar-logout {
    border-top: 1px solid var(--border);
    padding: 16px;
  }

  .btn-logout {
    width: 100%;
    padding: 12px 16px;
    background: var(--white);
    color: var(--charcoal);
    border: 1.5px solid var(--border);
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    text-decoration: none;
    transition: all .15s;
  }
  .btn-logout:hover {
    background: var(--black);
    color: var(--white);
    border-color: var(--black);
  }

  /* ── Main content ── */
  .desktop-main {
    flex: 1;
    padding: 36px 48px;
    max-width: 760px;
    overflow-y: auto;
  }
}

/* ══════════════════════════════════════════
   SHARED COMPONENTS
══════════════════════════════════════════ */

/* ── Alerts ── */
.s-alert {
  margin-bottom: 16px;
  padding: 12px 16px;
  font-size: 13px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 10px;
  border-left: 4px solid;
}
.s-alert-success { background: var(--success-bg); color: var(--success); border-color: var(--success); }
.s-alert-error   { background: var(--danger-bg);  color: var(--danger);  border-color: var(--danger); }

/* ── Section Card ── */
.settings-section {
  background: var(--white);
  border: 1.5px solid var(--border);
  border-top: 3px solid var(--black);
  overflow: hidden;
  margin-bottom: 16px;
}

.settings-section-title {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--mid);
  padding: 14px 20px 8px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.settings-section-title::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--border);
}

/* ── Form Groups ── */
.sf-group { padding: 8px 20px 16px; }
.sf-group + .sf-group { border-top: 1px solid var(--bg); padding-top: 14px; }

.sf-label {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--charcoal);
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.sf-input {
  width: 100%;
  padding: 11px 14px;
  border: 1.5px solid var(--border);
  font-size: 14px;
  font-family: 'Barlow', sans-serif;
  font-weight: 400;
  color: var(--black);
  background: var(--bg);
  outline: none;
  transition: border-color .15s, background .15s;
  border-radius: 0;
}
.sf-input:focus {
  border-color: var(--black);
  background: var(--white);
  box-shadow: 0 0 0 3px rgba(13,13,13,0.07);
}
.sf-input::placeholder { color: var(--light); }

.sf-hint { font-size: 11px; color: var(--light); margin-top: 6px; }

/* ── Password strength ── */
.pw-strength-bar { height: 3px; background: var(--border); margin-top: 8px; overflow: hidden; }
.pw-strength-fill { height: 100%; width: 0; transition: width .3s, background .3s; }

/* ── Row items ── */
.settings-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  cursor: pointer;
  transition: background .12s;
  text-decoration: none;
  color: inherit;
}
.settings-row:not(:last-child) { border-bottom: 1px solid var(--bg); }
.settings-row:hover  { background: var(--bg); }
.settings-row:active { background: #eeeeee; }

.sr-left { display: flex; align-items: center; gap: 14px; }

.sr-icon {
  width: 34px; height: 34px;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; flex-shrink: 0;
  background: var(--bg);
  border: 1.5px solid var(--border);
}
.sr-icon.dark { background: var(--black); border-color: var(--black); }

.sr-text { font-size: 14px; font-weight: 500; color: var(--black); }
.sr-sub  { font-size: 11px; color: var(--mid); margin-top: 2px; }
.sr-arrow { color: var(--light); font-size: 20px; line-height: 1; }

/* ── Save button ── */
.sf-footer { padding: 0 20px 18px; }

.btn-save-settings {
  width: 100%;
  padding: 13px;
  background: var(--black);
  color: var(--white);
  border: 2px solid var(--black);
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 15px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  cursor: pointer;
  transition: opacity .15s;
  border-radius: 0;
}
.btn-save-settings:hover  { opacity: .82; }
.btn-save-settings:active { opacity: .7; }

/* ── Danger button ── */
.btn-danger {
  width: 100%;
  padding: 13px;
  background: var(--white);
  color: var(--danger);
  border: 2px solid var(--danger);
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 15px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  cursor: pointer;
  transition: all .15s;
  border-radius: 0;
}
.btn-danger:hover { background: var(--danger); color: var(--white); }

/* ── Deactivate button ── */
.btn-deactivate {
  padding: 7px 16px;
  border: 1.5px solid var(--border);
  background: var(--white);
  color: var(--charcoal);
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  cursor: pointer;
  border-radius: 0;
  transition: all .15s;
}
.btn-deactivate:hover { background: var(--black); color: var(--white); border-color: var(--black); }

/* ── Mobile logout button ── */
.btn-logout-mobile {
  width: 100%;
  padding: 12px;
  background: var(--white);
  color: var(--charcoal);
  border: 1.5px solid var(--border);
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  text-decoration: none;
  transition: all .15s;
  border-radius: 0;
}
.btn-logout-mobile:hover { background: var(--black); color: var(--white); border-color: var(--black); }

/* ── Divider ── */
.s-divider { height: 1px; background: var(--bg); }

/* ── Delete confirm ── */
.delete-confirm-wrap { display: none; padding: 0 20px 18px; }
.delete-confirm-wrap.open { display: block; }

/* ── Toggle switch ── */
.toggle { position: relative; display: inline-block; width: 44px; height: 24px; }
.toggle input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
  position: absolute; inset: 0; cursor: pointer;
  background: var(--border); transition: .2s; border-radius: 0;
}
.toggle-slider:before {
  content: ""; position: absolute;
  height: 18px; width: 18px; left: 3px; bottom: 3px;
  background: var(--white); transition: .2s; border-radius: 0;
  box-shadow: 0 1px 3px rgba(0,0,0,.15);
}
.toggle input:checked + .toggle-slider { background: var(--black); }
.toggle input:checked + .toggle-slider:before { transform: translateX(20px); }

/* ── Active badge ── */
.badge-active {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 4px 10px;
  background: var(--black);
  color: var(--white);
}

/* ── Version ── */
.version-tag {
  text-align: center;
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--light);
  padding: 14px 0 4px;
}

/* ── Fade-up entry ── */
.settings-section { animation: fadeUp .2s ease both; }
.settings-section:nth-child(2) { animation-delay: .04s; }
.settings-section:nth-child(3) { animation-delay: .08s; }
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
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

  <!-- ── Top Header ── -->
  <div class="settings-header">
    <a class="settings-back" href="profile.php" aria-label="Back">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <h1>Settings</h1>
  </div>

  <!-- ── Identity Strip ── -->
  <div class="settings-identity">
    <div class="identity-bar"></div>
    <div class="identity-content">
      <img class="settings-avatar"
           src="<?php
             $pic = $user->profile_picture ?? null;
             echo $pic ? htmlspecialchars($pic)
                       : 'https://ui-avatars.com/api/?name=' . urlencode($user->username ?: 'U') . '&background=2C2C2C&color=fff&size=96';
           ?>" alt="avatar">
      <div>
        <p class="settings-identity-name">@<?= htmlspecialchars($user->username ?? '') ?></p>
        <p class="settings-identity-email"><?= htmlspecialchars($user->email ?? '') ?></p>
      </div>
    </div>
  </div>

  <!-- ── Mobile Tab Nav ── -->
  <nav class="settings-nav">
    <a href="?section=account"  class="<?= $section==='account'  ? 'active' : '' ?>">Account</a>
    <a href="?section=security" class="<?= $section==='security' ? 'active' : '' ?>">Security</a>
    <a href="?section=prefs"    class="<?= $section==='prefs'    ? 'active' : '' ?>">Preferences</a>
    <a href="?section=danger"   class="<?= $section==='danger'   ? 'active' : '' ?>">Danger Zone</a>
  </nav>

  <!-- ── Main Area ── -->
  <div class="desktop-layout">

    <!-- ── Desktop Sidebar ── -->
    <aside class="desktop-sidebar">
      <nav class="sidebar-nav">
        <a href="?section=account"  class="<?= $section==='account'  ? 'active' : '' ?>">
          <span class="nav-icon">👤</span> Account
        </a>
        <a href="?section=security" class="<?= $section==='security' ? 'active' : '' ?>">
          <span class="nav-icon">🔒</span> Security
        </a>
        <a href="?section=prefs"    class="<?= $section==='prefs'    ? 'active' : '' ?>">
          <span class="nav-icon">⚙️</span> Preferences
        </a>
        <a href="?section=danger"   class="<?= $section==='danger'   ? 'active' : '' ?>">
          <span class="nav-icon">⚠️</span> Danger Zone
        </a>
      </nav>
      <!-- Logout locked to sidebar bottom -->
      <div class="sidebar-logout">
        <a class="btn-logout" href="../logout.php">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Log out
        </a>
      </div>
    </aside>

    <!-- ── Content ── -->
    <div class="desktop-main">

      <!-- Alerts -->
      <?php if ($success): ?>
        <div class="s-alert s-alert-success">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="s-alert s-alert-error">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- ═══ ACCOUNT ═══ -->
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
        <a class="settings-row" href="profile.php">
          <div class="sr-left">
            <div class="sr-icon">👤</div>
            <div>
              <div class="sr-text">Edit profile</div>
              <div class="sr-sub">Name, bio, photo, address</div>
            </div>
          </div>
          <span class="sr-arrow">›</span>
        </a>
      </div>

      <!-- ═══ SECURITY ═══ -->
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
              <span id="pw-strength-label" style="font-size:11px;color:var(--mid);font-weight:400;text-transform:none;letter-spacing:0"></span>
            </div>
            <input class="sf-input" type="password" name="new_password"
                   id="new_password" autocomplete="new-password" placeholder="Min. 8 characters">
            <div class="pw-strength-bar"><div class="pw-strength-fill" id="pw-bar"></div></div>
          </div>
          <div class="sf-group">
            <div class="sf-label">Confirm new password</div>
            <input class="sf-input" type="password" name="confirm_password"
                   id="confirm_password" autocomplete="new-password" placeholder="Repeat password">
            <p class="sf-hint" id="pw-match-hint"></p>
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
            <div class="sr-icon dark">🔒</div>
            <div>
              <div class="sr-text">Current session</div>
              <div class="sr-sub">Logged in as <?= htmlspecialchars($user->username ?? '') ?></div>
            </div>
          </div>
          <span class="badge-active">Active</span>
        </div>
      </div>

      <!-- ═══ PREFERENCES ═══ -->
      <?php elseif ($section === 'prefs'): ?>

      <div class="settings-section">
        <p class="settings-section-title">Notifications</p>
        <div class="settings-row">
          <div class="sr-left">
            <div class="sr-icon">🔔</div>
            <div>
              <div class="sr-text">New messages</div>
              <div class="sr-sub">When someone messages you</div>
            </div>
          </div>
          <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
        </div>
        <div class="settings-row">
          <div class="sr-left">
            <div class="sr-icon">❤️</div>
            <div>
              <div class="sr-text">Likes on listings</div>
              <div class="sr-sub">When someone likes your item</div>
            </div>
          </div>
          <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
        </div>
        <div class="settings-row">
          <div class="sr-left">
            <div class="sr-icon">🛒</div>
            <div>
              <div class="sr-text">Sale completed</div>
              <div class="sr-sub">When your item sells</div>
            </div>
          </div>
          <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
        </div>
        <div class="settings-row">
          <div class="sr-left">
            <div class="sr-icon">📧</div>
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
            <div class="sr-icon">🌙</div>
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
        <a class="settings-row" href="#">
          <div class="sr-left">
            <div class="sr-icon">📄</div>
            <div><div class="sr-text">Privacy policy</div></div>
          </div>
          <span class="sr-arrow">›</span>
        </a>
        <a class="settings-row" href="#">
          <div class="sr-left">
            <div class="sr-icon">📋</div>
            <div><div class="sr-text">Terms of service</div></div>
          </div>
          <span class="sr-arrow">›</span>
        </a>
        <a class="settings-row" href="#">
          <div class="sr-left">
            <div class="sr-icon">💬</div>
            <div><div class="sr-text">Send feedback</div></div>
          </div>
          <span class="sr-arrow">›</span>
        </a>
      </div>
      <p class="version-tag">ReVenta · v1.0</p>

      <!-- ═══ DANGER ZONE ═══ -->
      <?php elseif ($section === 'danger'): ?>

      <div class="settings-section">
        <p class="settings-section-title">Danger Zone</p>

        <div class="settings-row" style="cursor:default">
          <div class="sr-left">
            <div class="sr-icon">⏸️</div>
            <div>
              <div class="sr-text">Deactivate account</div>
              <div class="sr-sub">Hide your profile temporarily</div>
            </div>
          </div>
          <button class="btn-deactivate" onclick="alert('Contact support to deactivate your account.')">
            Pause
          </button>
        </div>

        <div class="s-divider"></div>

        <div class="settings-row" id="delete-toggle-row" onclick="toggleDelete()" style="border-bottom:none">
          <div class="sr-left">
            <div class="sr-icon dark">🗑️</div>
            <div>
              <div class="sr-text" style="color:var(--danger);font-weight:600">Delete account</div>
              <div class="sr-sub">Permanent — cannot be undone</div>
            </div>
          </div>
          <span class="sr-arrow" id="delete-arrow">›</span>
        </div>

        <div class="delete-confirm-wrap" id="delete-confirm">
          <form method="POST" action="settings.php?section=danger">
            <input type="hidden" name="action" value="delete">
            <p style="font-size:13px;color:var(--mid);margin:0 0 12px;line-height:1.6">
              This will permanently delete your account, all listings, and all data.
              Type <strong style="color:var(--black)">DELETE</strong> to confirm.
            </p>
            <input class="sf-input" type="text" name="confirm_delete"
                   placeholder="Type DELETE here" style="margin-bottom:12px"
                   autocomplete="off" spellcheck="false">
            <button type="submit" class="btn-danger">Yes, delete my account forever</button>
          </form>
        </div>
      </div>

      <?php endif; ?>

    </div><!-- /desktop-main -->
  </div><!-- /desktop-layout -->

  <!-- Mobile: logout fixed above bottom nav -->
  <div class="logout-wrap">
    <a class="btn-logout-mobile" href="../logout.php">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Log out
    </a>
  </div>

</div><!-- /settings-wrap -->

<!-- Mobile bottom nav -->
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
    if (v.length >= 8)           score++;
    if (/[A-Z]/.test(v))         score++;
    if (/[0-9]/.test(v))         score++;
    if (/[^A-Za-z0-9]/.test(v))  score++;
    const widths = ['0%','25%','50%','75%','100%'];
    const colors = ['#eee','#999','#777','#444','#0D0D0D'];
    const labels = ['','Weak','Fair','Good','Strong'];
    if (bar)   { bar.style.width = widths[score]; bar.style.background = colors[score]; }
    if (label)   label.textContent = labels[score];
  });
}
if (confPw) {
  confPw.addEventListener('input', function() {
    if (!hint || !newPw) return;
    if (this.value === '') { hint.textContent = ''; return; }
    if (this.value === newPw.value) {
      hint.textContent = '✓ Passwords match'; hint.style.color = '#1A7A40';
    } else {
      hint.textContent = '✗ Passwords do not match'; hint.style.color = '#C0392B';
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

// Dark mode toggle (UI-only)
const darkToggle = document.getElementById('dark-toggle');
if (darkToggle) {
  darkToggle.addEventListener('change', function() {
    const r = document.documentElement;
    if (this.checked) {
      r.style.setProperty('--bg',     '#161616');
      r.style.setProperty('--white',  '#1E1E1E');
      r.style.setProperty('--border', '#2E2E2E');
      r.style.setProperty('--black',  '#F5F5F5');
      r.style.setProperty('--charcoal','#CCCCCC');
      r.style.setProperty('--mid',    '#888888');
      document.body.style.background = '#111';
    } else {
      r.style.setProperty('--bg',     '#F5F5F5');
      r.style.setProperty('--white',  '#FFFFFF');
      r.style.setProperty('--border', '#E8E8E8');
      r.style.setProperty('--black',  '#0D0D0D');
      r.style.setProperty('--charcoal','#2C2C2C');
      r.style.setProperty('--mid',    '#6B6B6B');
      document.body.style.background = '';
    }
  });
}
</script>
</body>
</html>