<?php
session_start();
session_unset();
$_SESSION = array();

setcookie("PHPSSID", time()-3600);
session_destroy();
header("Location: ../../index.php");
exit;
?>