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
    $email    = !empty($_POST['email'])    ? sanitizeInput($_POST['email'])    : "";
    $password = !empty($_POST['password']) ? sanitizeInput($_POST['password']) : "";

    if (empty($email) || empty($password)) {
        $message = "Please enter both email and password.";
        $messageType = "error";
    } else {
        $user = new User();
        $userRecord = $user->validateUser($email, $password);
        if ($userRecord !== false) {
            $_SESSION['user_id']  = $userRecord['id'];
            $_SESSION['email']    = $userRecord['email'];
            $_SESSION['username'] = $userRecord['username'];
            header("Location: ../index.php");
            exit;
        } else {
            $message = "Invalid email or password.";
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
<title>Login — ReVènta</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/pages.css">
</head>
<body class="auth-body">

<nav>
  <div class="nav-left">
    <a href="explore.php">Explore</a>
    <a href="mens.php">Men</a>
    <a href="womens.php">Women</a>
  </div>
  <a href="../index.php" class="nav-logo">Re<span id="theV">V</span>è<span>nta</span></a>
  <div class="nav-right">
    <a href="login.php" class="active">Login</a>
    <a href="signup.php">Sign Up</a>
    <a href="sell.php" class="nav-sell">Sell+</a>
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
  <div class="auth-avatar">
    <svg viewBox="0 0 88 88" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="44" cy="44" r="44" fill="#0a0a0a"/>
      <circle cx="44" cy="34" r="13" fill="white"/>
      <ellipse cx="44" cy="72" rx="22" ry="14" fill="white"/>
    </svg>
  </div>

  <?php if (!empty($message)): ?>
    <div class="auth-message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <div class="auth-field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" autocomplete="email"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    </div>

    <div class="auth-field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="current-password" required>
    </div>

    <div class="auth-links">
      <span></span>
      <a href="#" class="auth-link">Forgot Password</a>
    </div>

    <button type="submit" class="btn-auth">Login</button>
  </form>

  <div class="auth-footer">
    Don't have an account? <a href="signup.php">Sign up</a>
  </div>
</div>

<script>
const ham = document.getElementById('navHamburger');
const menu = document.getElementById('navMobileMenu');
ham.addEventListener('click', () => { ham.classList.toggle('open'); menu.classList.toggle('open'); });
</script>
</body>
</html>
