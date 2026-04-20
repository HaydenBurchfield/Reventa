<?php
/**
 * rate_seller.php  (lives in pages/)
 * POST-only AJAX endpoint — returns JSON.
 *
 * Required POST fields:
 *   seller_id  int  – the user being rated
 *   stars      int  – 1–5
 */
require_once __DIR__ . '/../php/Utils/DatabaseConnection.php';
session_start();

header('Content-Type: application/json');

/* ── Auth ──────────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'message' => 'Not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid request method.']);
    exit;
}

/* ── Input validation ──────────────────────────────────────────── */
$sellerId = filter_input(INPUT_POST, 'seller_id', FILTER_VALIDATE_INT);
$stars    = filter_input(INPUT_POST, 'stars',     FILTER_VALIDATE_INT);
$raterId  = (int)$_SESSION['user_id'];

if (!$sellerId || $stars === false || $stars < 1 || $stars > 5) {
    echo json_encode(['ok' => false, 'message' => 'Invalid input.']);
    exit;
}

if ((int)$sellerId === $raterId) {
    echo json_encode(['ok' => false, 'message' => "You can't rate yourself."]);
    exit;
}

/* ── DB ────────────────────────────────────────────────────────── */
try {
    $db   = new DatabaseConnection();
    $conn = $db->getConnection();

    /* Upsert — one rating per (seller, rater) pair */
    $stmt = $conn->prepare("
        INSERT INTO seller_ratings (seller_id, rater_id, stars, created_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE stars = VALUES(stars), created_at = NOW()
    ");
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('iii', $sellerId, $raterId, $stars);
    $stmt->execute();
    $stmt->close();

    /* Return updated aggregate so the UI refreshes without a page reload */
    $agg = $conn->prepare("
        SELECT ROUND(AVG(stars), 1) AS avg_stars, COUNT(*) AS total
        FROM seller_ratings
        WHERE seller_id = ?
    ");
    if (!$agg) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }
    $agg->bind_param('i', $sellerId);
    $agg->execute();
    $row = $agg->get_result()->fetch_assoc();
    $agg->close();

    echo json_encode([
        'ok'      => true,
        'message' => 'Rating saved!',
        'avg'     => (float)$row['avg_stars'],
        'total'   => (int)$row['total'],
    ]);

} catch (Throwable $e) {
    error_log('rate_seller.php error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'message' => 'Server error — ' . $e->getMessage()]);
}