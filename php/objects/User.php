<?php
require_once __DIR__ . '/../Utils/DatabaseConnection.php';

class User {
    public $id;
    public $username;
    public $full_name;
    public $phone_number;
    public $adress;
    public $email;
    public $birthday;
    public $password;
    public $gender;
    public $state;
    public $profile_picture;
    public $bio;
    private $db;

    public function __construct() {
        $this->db = new DatabaseConnection();
    }

    public function insert() {
        if (self::exists($this->email, $this->username)) return false;
        if (strlen($this->username) > 50 || strlen($this->email) > 100) return false;

        $conn = $this->db->connect();
        $sql  = "INSERT INTO user (username, password, email, state_id, birthday, full_name, phone_number, address, gender)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { error_log("Prepare failed: " . $conn->error); return false; }

        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
        $stateId = (int)$this->state;
        $stmt->bind_param("sssisssss",
            $this->username, $hashedPassword, $this->email,
            $stateId, $this->birthday, $this->full_name,
            $this->phone_number, $this->adress, $this->gender
        );
        $success = $stmt->execute();
        if ($success) $this->id = $stmt->insert_id;
        $stmt->close();
        return $success;
    }

    public function getUserById($id) {
        $conn  = $this->db->connect();
        $stmt  = $conn->prepare("SELECT * FROM user WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $user;
    }

    public function populate($id) {
        $user = $this->getUserById($id);
        if (!$user) return false;
        $this->id              = $user['id'];
        $this->username        = $user['username'];
        $this->email           = $user['email'];
        $this->password        = $user['password'];
        $this->state           = $user['state_id']       ?? null;
        $this->full_name       = $user['full_name']       ?? '';
        $this->phone_number    = $user['phone_number']    ?? '';
        $this->adress          = $user['address']         ?? '';
        $this->gender          = $user['gender']          ?? '';
        $this->birthday        = $user['birthday']        ?? '';
        $this->profile_picture = $user['profile_picture'] ?? null;
        $this->bio             = $user['bio']             ?? '';
        return true;
    }

    // Update profile info + picture. Call AFTER setting all fields.
    public function updateProfile() {
        if (!$this->id) return false;
        $conn = $this->db->connect();
        $sql  = "UPDATE user SET full_name=?, bio=?, phone_number=?, address=?, profile_picture=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("updateProfile prepare failed: " . $conn->error);
            return false;
        }
        $stmt->bind_param("sssssi",
            $this->full_name,
            $this->bio,
            $this->phone_number,
            $this->adress,
            $this->profile_picture,
            $this->id
        );
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function update() {
        if (!$this->id) return false;
        $conn           = $this->db->connect();
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
        $sql  = "UPDATE user SET username=?, email=?, password=?, state_id=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $this->username, $this->email, $hashedPassword, $this->state, $this->id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public static function exists($email, $username) {
        $db   = new DatabaseConnection();
        $conn = $db->connect();
        $stmt = $conn->prepare("SELECT id FROM user WHERE email=? OR username=?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $exists = ($stmt->get_result()->num_rows > 0);
        $stmt->close();
        $db->closeConnection();
        return $exists;
    }

    public function validateUser($email, $password) {
        $conn  = $this->db->connect();
        $stmt  = $conn->prepare("SELECT * FROM user WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $stmt->close();
                return $user;
            }
        }
        $stmt->close();
        return false;
    }

    public function getAlluser() {
        $conn   = $this->db->connect();
        $result = $conn->query("SELECT id, username, state_id FROM user ORDER BY id DESC");
        $users  = [];
        while ($row = $result->fetch_assoc()) $users[] = $row;
        return $users;
    }

    public static function searchuser($searchTerm) {
        $db   = new DatabaseConnection();
        $conn = $db->connect();
        $stmt = $conn->prepare("SELECT * FROM user WHERE username LIKE ? OR email LIKE ?");
        $pat  = "%$searchTerm%";
        $stmt->bind_param("ss", $pat, $pat);
        $stmt->execute();
        $result = $stmt->get_result();
        $users  = [];
        while ($row = $result->fetch_assoc()) $users[] = $row;
        $stmt->close();
        $db->closeConnection();
        return $users;
    }
}
?>