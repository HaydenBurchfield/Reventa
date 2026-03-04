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
    $username = !empty($_POST['username']) ? sanitizeInput($_POST['username']) : "";
    $email = !empty($_POST['email']) ? sanitizeInput($_POST['email']) : "";
    $password = !empty($_POST['password']) ? sanitizeInput($_POST['password']) : "";
    $confirmPassword = !empty($_POST['confirm_password']) ? sanitizeInput($_POST['confirm_password']) : "";
    $state = !empty($_POST['state']) ? sanitizeInput($_POST['state']) : "";
    $gender = !empty($_POST['gender']) ? sanitizeInput($_POST['gender']) : "";
    $address = !empty($_POST['address']) ? sanitizeInput($_POST['address']) : "";
    $phone_number = !empty($_POST['phone_number']) ? sanitizeInput($_POST['phone_number']) : "";
    $full_name = !empty($_POST['full_name']) ? sanitizeInput($_POST['full_name']) : "";
    $birthday = !empty($_POST['birthday']) ? sanitizeInput($_POST['birthday']) : "";
    

    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $message = "Please fill in all fields.";
        $messageType = "error";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match!";
        $messageType = "error";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $messageType = "error";
    } else {
        $user = new User();
        $user->username = $username;
        $user->email = $email;
        $user->password = $password;
        $user->state = $state;
        $user->gender = $gender;
        $user->adress = $address;
        $user->phone_number = $phone_number;
        $user->full_name = $full_name;
        $user->birthday = $birthday;

        if ($user->insert()) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['email'] = $user->email;
            $_SESSION['username'] = $user->username;

            header("Location: index.php");
            exit;
        } else {
            $message = "Email or username already exists!";
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
<title>ReVenta — Profile</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/styles.css">
<link rel="stylesheet" href="../assets/css/login.css">
</head>
<body class="auth-page">
    <nav id="top-nav">
      <a href="index.php" class="nav-logo">ReVenta<span>.</span></a>
      <div class="nav-search"><input type="text" id="search-input" placeholder="Search items, brands, sellers..."></div>
      <div class="nav-links">
        <a href="../index.php" class="nav-tab-link">Home</a>
        <a href="../pages/explore.php" class="nav-tab-link active">Explore</a>
        <a href="../pages/messages.php" class="nav-tab-link">Messages</a>
        <a href="../pages/profile.php" class="nav-tab-link">Profile</a>
      </div>
      <a href="../pages/sell.php"><button class="btn-sell">+ Sell</button></a>
    </nav>
    <div class="auth-container" id="signup_auth-container">
        <div class="auth-card">
            <h1>Create Account</h1>
            <p class="subtitle">Join our community today</p>
            <br>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form action="signup.php" method="post" class="auth-form">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>

                <div class="form-group">
                    <label for="birthday">Birthday</label>
                    <input type="date" id="birthday" name="birthday" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" required>
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number" required>
                </div>

                <div class="form-group">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" required>
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <input type="text" id="gender" name="gender" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary" id="signup">Sign Up</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Log in</a></p>
            </div>
        </div>
    </div>
</body>
</html>