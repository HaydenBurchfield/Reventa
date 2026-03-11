<?php
require_once __DIR__ . '/../php/objects/User.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$message = "";
$messageType = "";

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $email    = !empty($_POST['email'])    ? sanitizeInput($_POST['email'])    : "";
    $password = !empty($_POST['password']) ? sanitizeInput($_POST['password']) : "";

    if (empty($email) || empty($password)) {
        $message     = "Please enter both email and password.";
        $messageType = "error";
    } else {
        $user       = new User();                          // ← instantiate FIRST
        $userRecord = $user->validateUser($email, $password); // ← then call method

        if ($userRecord !== false) {
            $_SESSION['user_id']  = $userRecord['id'];
            $_SESSION['email']    = $userRecord['email'];
            $_SESSION['username'] = $userRecord['username'];

            header("Location: ../index.php");
            exit;
        } else {
            $message     = "Invalid email or password!";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>ReVenta — Login</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/styles.css">
<link rel="stylesheet" href="../assets/css/login.css">
</head>
<body class="auth-page">
    <nav id="top-nav">
      <a href="../index.php" class="nav-logo">ReVenta<span>.</span></a>
      <div class="nav-search"><input type="text" id="search-input" placeholder="Search items, brands, sellers..."></div>
      <div class="nav-links">
        <a href="../index.php" class="nav-tab-link">Home</a>
        <a href="../pages/explore.php" class="nav-tab-link active">Explore</a>
        <a href="../pages/messages.php" class="nav-tab-link">Messages</a>
        <a href="../pages/profile.php" class="nav-tab-link">Profile</a>
      </div>
      <a href="../pages/sell.php"><button class="btn-sell">+ Sell</button></a>
    </nav>
    <div class="auth-container">
        <div class="auth-card">
            <h1>Welcome Back</h1>
            <br>
            <p class="subtitle">Login to your account</p>
            <br>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <br>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <small><a href="forgot-password.php" style="color: #ff0000; text-decoration: none; font-weight: 600; float: right; margin-top: 5px;">Forgot your password?</a></small>
                </div>
                <br>

                <button type="submit" class="btn btn-primary" id="login">Log In</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="signup.php">Sign up</a></p>
            </div>
        </div>
    </div>
</body>
</html>