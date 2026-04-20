<?php
session_start();
session_unset();
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}
?>
<!DOCTYPE html>
<html>
<head>
    <script>
        localStorage.removeItem('rv_theme'); // ← your exact key
        window.location.href = "../../index.php?loggedout=1";
    </script>
</head>
</html>