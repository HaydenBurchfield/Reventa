<?php
require_once __DIR__ . '/../objects/Chat.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

$chatId  = (int)($_POST['chat_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if (!$chatId || !$content) {
    echo json_encode(['success' => false, 'error' => 'missing_fields']);
    exit;
}

$chat = new Chat();
// Verify user is participant
$chatData = $chat->getChatById($chatId, (int)$_SESSION['user_id']);
if (!$chatData) {
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

$msgId = $chat->sendMessage($chatId, (int)$_SESSION['user_id'], $content);
echo json_encode(['success' => (bool)$msgId, 'id' => $msgId]);
