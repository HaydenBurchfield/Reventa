<?php
require_once __DIR__ . '/../php/objects/User.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$message = "";
$messageType = "";

function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $username        = !empty($_POST['username'])         ? sanitizeInput($_POST['username'])         : "";
    $email           = !empty($_POST['email'])            ? sanitizeInput($_POST['email'])            : "";
    $password        = !empty($_POST['password'])         ? sanitizeInput($_POST['password'])         : "";
    $confirmPassword = !empty($_POST['confirm_password']) ? sanitizeInput($_POST['confirm_password']) : "";
    $full_name       = !empty($_POST['full_name'])        ? sanitizeInput($_POST['full_name'])        : "";
    $birthday        = !empty($_POST['birthday'])         ? sanitizeInput($_POST['birthday'])         : "";
    $state           = !empty($_POST['state'])            ? sanitizeInput($_POST['state'])            : "";
    $gender          = !empty($_POST['gender'])           ? sanitizeInput($_POST['gender'])           : "";
    $address         = !empty($_POST['address'])          ? sanitizeInput($_POST['address'])          : "";
    $phone_number    = !empty($_POST['phone_number'])     ? sanitizeInput($_POST['phone_number'])     : "";

    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "error";
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $message = "Username must be between 3 and 20 characters.";
        $messageType = "error";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
        $messageType = "error";
    } else {
        $user               = new User();
        $user->username     = $username;
        $user->email        = $email;
        $user->password     = $password;
        $user->state        = $state;
        $user->gender       = $gender;
        $user->adress       = $address;
        $user->phone_number = $phone_number;
        $user->full_name    = $full_name;
        $user->birthday     = $birthday;

        if ($user->insert()) {
            $_SESSION['user_id']  = $user->id;
            $_SESSION['email']    = $user->email;
            $_SESSION['username'] = $user->username;
            header("Location: ../index.php");
            exit;
        } else {
            $message = "Email or username already exists.";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Sign Up — ReVènta</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/pages.css">
</head>
<body class="auth-body">

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
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="profile.php">Profile</a>
      <a href="messages.php">Messages</a>
    <?php else: ?>
      <a href="login.php">Login</a>
      <a href="signup.php" class="active">Sign Up</a>
    <?php endif; ?>
  </div>
  <button class="nav-hamburger" id="navHamburger"><span></span><span></span><span></span></button>
</nav>
<div class="nav-mobile-menu" id="navMobileMenu">
  <a href="../index.php">Home</a>
  <a href="explore.php">Explore</a>
  <a href="mens.php">Men</a>
  <a href="womens.php">Women</a>
  <a href="sell.php">Sell+</a>
  <a href="login.php">Login</a>
  <a href="signup.php">Sign Up</a>
</div>

<div class="auth-card">
  <div class="auth-logo">Re<span>V</span>ènta</div>

  <?php if (!empty($message)): ?>
    <div class="auth-message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="POST" action="signup.php">
    <div class="auth-field">
      <label for="full_name">Full Name</label>
      <input type="text" id="full_name" name="full_name" autocomplete="name"
             value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
    </div>

    <div class="auth-field">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" autocomplete="username"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
    </div>

    <div class="auth-field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" autocomplete="email"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    </div>

    <div class="auth-field">
      <label for="birthday">Birth Date</label>
      <input type="date" id="birthday" name="birthday"
             value="<?= htmlspecialchars($_POST['birthday'] ?? '') ?>" required>
    </div>

    <div class="auth-field">
      <label for="state">State</label>
      <select id="state" name="state">
        <option value="">— Select —</option>
        <?php foreach (User::getStates() as $st): ?>
          <option value="<?= $st['id'] ?>" <?= (($_POST['state'] ?? '') == $st['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($st['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="auth-field">
      <label for="gender">Gender</label>
      <select id="gender" name="gender">
        <option value="">— Select —</option>
        <option value="Male"   <?= (($_POST['gender'] ?? '') === 'Male')   ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
        <option value="Other"  <?= (($_POST['gender'] ?? '') === 'Other')  ? 'selected' : '' ?>>Other</option>
      </select>
    </div>

    <div class="auth-field">
      <label for="address">Address</label>
      <input type="text" id="address" name="address"
             value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
    </div>

    <div class="auth-field">
      <label for="phone_number">Phone Number</label>
      <input type="text" id="phone_number" name="phone_number"
             value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>">
    </div>

    <div class="auth-field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="new-password" required>
    </div>

    <div class="auth-field">
      <label for="confirm_password">Confirm Password</label>
      <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
    </div>

    <div class="auth-links" style="margin-top:8px;">
      <span></span>
      <a href="login.php" class="auth-link">Already have an account?</a>
    </div>

    <button type="submit" class="btn-auth">Create Account</button>
  </form>
</div>

<script>
const ham = document.getElementById('navHamburger');
const menu = document.getElementById('navMobileMenu');
ham.addEventListener('click', () => { ham.classList.toggle('open'); menu.classList.toggle('open'); });
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>