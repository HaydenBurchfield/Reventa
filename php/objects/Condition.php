<?php
require_once __DIR__ . '/../Utils/DatabaseConnection.php';
class Condition {
    public $id;
    public $name;
    private $db;

    public function __construct() {
        $this->db = new DatabaseConnection();
    }

    public  function getAllConditions() {
        $conn = $this->db->connect();
        $query = "SELECT * FROM `condition`";
        $result = $conn->query($query);
        $conditions = [];
        while ($row = $result->fetch_assoc()) {
            $condition = new Condition();
            $condition->id = $row['id'];
            $condition->name = $row['name'];
            $conditions[] = $condition;
        }
        return $conditions;
    }
}


?>