<?php
require_once __DIR__ . '/../objects/Chat.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['messages' => []]);
    exit;
}

$chatId = (int)($_GET['chat_id'] ?? 0);
$lastId = (int)($_GET['last_id'] ?? 0);

if (!$chatId) { echo json_encode(['messages' => []]); exit; }

$chat = new Chat();
$chatData = $chat->getChatById($chatId, (int)$_SESSION['user_id']);
if (!$chatData) { echo json_encode(['messages' => []]); exit; }

$messages = $chat->getMessagesSince($chatId, $lastId);
echo json_encode(['messages' => $messages]);
