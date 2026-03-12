<?php
require_once __DIR__ . '/../Utils/DatabaseConnection.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'not_logged_in', 'liked' => false]);
    exit;
}

$userId    = (int)$_SESSION['user_id'];
$listingId = (int)($_POST['listing_id'] ?? 0);

if (!$listingId) {
    echo json_encode(['error' => 'missing listing_id']);
    exit;
}

$db   = new DatabaseConnection();
$conn = $db->getConnection();

// Check if already liked
$stmt = $conn->prepare("SELECT 1 FROM listing_like WHERE user_id=? AND listing_id=?");
$stmt->bind_param("ii", $userId, $listingId);
$stmt->execute();
$isLiked = $stmt->get_result()->num_rows > 0;
$stmt->close();

// Toggle
if ($isLiked) {
    $stmt = $conn->prepare("DELETE FROM listing_like WHERE user_id=? AND listing_id=?");
    $stmt->bind_param("ii", $userId, $listingId);
    $stmt->execute();
    $stmt->close();
    $liked = false;
} else {
    $stmt = $conn->prepare("INSERT INTO listing_like (user_id, listing_id) VALUES (?,?)");
    $stmt->bind_param("ii", $userId, $listingId);
    $stmt->execute();
    $stmt->close();
    $liked = true;
}

// Get updated count
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM listing_like WHERE listing_id=?");
$stmt->bind_param("i", $listingId);
$stmt->execute();
$count = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

echo json_encode(['liked' => $liked, 'count' => $count]);