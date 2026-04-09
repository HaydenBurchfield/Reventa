<?php
require_once __DIR__ . '/../objects/Listing.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../pages/explore.php'); exit;
}

$listingId = (int)($_POST['listing_id'] ?? 0);
if (!$listingId) { header('Location: ../../pages/explore.php'); exit; }

$listingObj = new Listing();
$item = $listingObj->getListingById($listingId);

if (!$item || $item['seller_id'] != $_SESSION['user_id']) {
    header('Location: ../../pages/listing.php?id=' . $listingId); exit;
}

$listingObj->delete($listingId);
header('Location: ../../pages/profile.php');
exit;
