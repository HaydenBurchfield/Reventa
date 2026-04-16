<?php
session_start();
session_unset();
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

header("Location: ../../index.php?loggedout=1");
exit;
