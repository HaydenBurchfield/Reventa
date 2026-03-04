<?php
require_once __DIR__ . '/../Utils/DatabaseConnection.php';

class User {
    public $id;
    public $username;
    public $full_name;
    public $phone_number;
    public $adress;
    public $email;
    public $password;
    public $state;
    private $db;
    
    public function __construct() {
        $this->db = new DatabaseConnection();
    }

    public function insert() {
        if (self::exists($this->email, $this->username)) {
            return false;
        }
        if (strlen($this->username) > 50 || strlen($this->email) > 100) {
            return false;
        }
        $conn = $this->db->connect();
        $sql = "INSERT INTO users (username, password, email, state, create_date) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
        $stmt->bind_param("ssssi", $this->username, $hashedPassword, $this->email, $this->state);
        $success = $stmt->execute();
        if ($success) $this->id = $stmt->insert_id;
        $stmt->close();
        return $success;
    }

    public function getUserById($id) {
        $conn = $this->db->connect();
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    public function populate($id) {
        $user = $this->getUserById($id);
        if ($user) {
            $this->id = $user['id'];
            $this->username = $user['username'];
            $this->email = $user['email'];
            $this->password = $user['password'];
            $this->state = $user['state'];
            return true;
        }
        return false;
    }

    public function update() {
        if (!$this->id) return false;
        
        $conn = $this->db->connect();
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET username = ?, email = ?, password = ?, state = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $this->username, $this->email, $hashedPassword, $this->state, $this->id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function getAllUsers() {
        $conn = $this->db->connect();
        $query = "SELECT id, username, state FROM users ORDER BY id DESC";
        $result = $conn->query($query);
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }

    public static function exists($email, $username) {
        $db = new DatabaseConnection();
        $conn = $db->connect();
        $sql = "SELECT id FROM users WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = ($result->num_rows > 0);
        $stmt->close();
        $db->closeConnection();
        return $exists;
    }

    public function validateUser($email, $password) {
        $conn = $this->db->connect();
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    }

    public static function searchUsers($searchTerm) {
        $db = new DatabaseConnection();
        $conn = $db->connect();
        $sql = "SELECT * FROM users WHERE username LIKE ? OR email LIKE ?";
        $stmt = $conn->prepare($sql);
        $searchPattern = "%$searchTerm%";
        $stmt->bind_param("ss", $searchPattern, $searchPattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();
        $db->closeConnection();
        return $users;
    }
}
?>