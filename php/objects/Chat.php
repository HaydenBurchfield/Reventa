<?php
require_once __DIR__ . '/../Utils/DatabaseConnection.php';

class Chat {
    private $db;
    private $conn;

    public function __construct() {
        $this->db   = new DatabaseConnection();
        $this->conn = $this->db->getConnection();
    }

    // Find existing chat or create one
    public function getOrCreateChat($listingId, $buyerId, $sellerId) {
        // Can't message yourself
        if ($buyerId === $sellerId) return null;

        $stmt = $this->conn->prepare(
            "SELECT id FROM chat WHERE listing_id=? AND buyer_id=? AND seller_id=? LIMIT 1"
        );
        $stmt->bind_param("iii", $listingId, $buyerId, $sellerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) return $row['id'];

        $stmt = $this->conn->prepare(
            "INSERT INTO chat (listing_id, buyer_id, seller_id) VALUES (?,?,?)"
        );
        $stmt->bind_param("iii", $listingId, $buyerId, $sellerId);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    // Get all chats for a user (as buyer or seller)
    public function getChatsForUser($userId) {
        $sql = "
            SELECT ch.id, ch.created_at,
                   l.id AS listing_id, l.name AS listing_name, l.price AS listing_price,
                   (SELECT lp.photo_url FROM listing_photo lp
                    WHERE lp.listing_id = l.id ORDER BY lp.sort_order LIMIT 1) AS listing_photo,
                   buyer.id AS buyer_id, buyer.username AS buyer_username,
                   buyer.profile_picture AS buyer_avatar,
                   seller.id AS seller_id, seller.username AS seller_username,
                   seller.profile_picture AS seller_avatar,
                   (SELECT m.content FROM messages m WHERE m.chat_id=ch.id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
                   (SELECT m.created_at FROM messages m WHERE m.chat_id=ch.id ORDER BY m.created_at DESC LIMIT 1) AS last_message_at
            FROM chat ch
            LEFT JOIN listing l    ON l.id     = ch.listing_id
            JOIN user buyer        ON buyer.id  = ch.buyer_id
            JOIN user seller       ON seller.id = ch.seller_id
            WHERE ch.buyer_id=? OR ch.seller_id=?
            ORDER BY last_message_at DESC, ch.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $chats  = [];
        while ($row = $result->fetch_assoc()) $chats[] = $row;
        $stmt->close();
        return $chats;
    }

    // Get a single chat by ID (verifies user is participant)
    public function getChatById($chatId, $userId) {
        $sql = "
            SELECT ch.*,
                   l.name AS listing_name, l.price AS listing_price, l.is_sold AS listing_is_sold,
                   (SELECT lp.photo_url FROM listing_photo lp
                    WHERE lp.listing_id = l.id ORDER BY lp.sort_order LIMIT 1) AS listing_photo,
                   buyer.id AS buyer_id, buyer.username AS buyer_username,
                   buyer.profile_picture AS buyer_avatar,
                   seller.id AS seller_id, seller.username AS seller_username,
                   seller.profile_picture AS seller_avatar
            FROM chat ch
            LEFT JOIN listing l    ON l.id     = ch.listing_id
            JOIN user buyer        ON buyer.id  = ch.buyer_id
            JOIN user seller       ON seller.id = ch.seller_id
            WHERE ch.id=? AND (ch.buyer_id=? OR ch.seller_id=?)
            LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $chatId, $userId, $userId);
        $stmt->execute();
        $chat = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $chat;
    }

    // Get all messages in a chat
    public function getMessages($chatId) {
        $sql = "
            SELECT m.*, u.username, u.profile_picture
            FROM messages m
            JOIN user u ON u.id = m.sender_id
            WHERE m.chat_id=?
            ORDER BY m.created_at ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $chatId);
        $stmt->execute();
        $result   = $stmt->get_result();
        $messages = [];
        while ($row = $result->fetch_assoc()) $messages[] = $row;
        $stmt->close();
        return $messages;
    }

    // Send a message
    public function sendMessage($chatId, $senderId, $content) {
        $content = trim($content);
        if (!$content) return false;

        $stmt = $this->conn->prepare(
            "INSERT INTO messages (chat_id, sender_id, content) VALUES (?,?,?)"
        );
        $stmt->bind_param("iis", $chatId, $senderId, $content);
        $ok = $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $ok ? $id : false;
    }

    // Get latest messages after a given message ID (for polling)
    public function getMessagesSince($chatId, $lastId) {
        $sql = "
            SELECT m.*, u.username, u.profile_picture
            FROM messages m
            JOIN user u ON u.id = m.sender_id
            WHERE m.chat_id=? AND m.id > ?
            ORDER BY m.created_at ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $chatId, $lastId);
        $stmt->execute();
        $result   = $stmt->get_result();
        $messages = [];
        while ($row = $result->fetch_assoc()) $messages[] = $row;
        $stmt->close();
        return $messages;
    }
}
?>