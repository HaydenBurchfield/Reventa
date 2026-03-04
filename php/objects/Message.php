<?php
require_once 'DatabaseConnection.php';

class Message {
    private $db;
    private $id;
    private $chatId;
    private $senderId;
    private $content;
    private $createdAt;

    public function __construct() {
        $this->db = new DatabaseConnection();
    }

    public function sendMessage($chatId, $senderId, $content) {
        $conn = $this->db->connect();
        $query = "INSERT INTO messages (chat_id, sender_id, content, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iis", $chatId, $senderId, $content);
        
        return $stmt->execute();
    }

    public function getChatMessages($chatId) {
        $conn = $this->db->connect();
        $query = "SELECT m.*, u.username, u.profile_image 
                 FROM messages m
                 INNER JOIN users u ON m.sender_id = u.id
                 WHERE m.chat_id = ?
                 ORDER BY m.created_at ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $chatId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();
        return $messages;
    }
}