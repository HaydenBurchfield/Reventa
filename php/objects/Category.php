<?php
require_once __DIR__ . '/../Utils/DatabaseConnection.php';

class Category {
    public $id;
    public $name;
    private $db;

    public function __construct() {
        $this->db = new DatabaseConnection();
    }

    public function getAllCategories() {
        $conn = $this->db->connect();
        $query = "SELECT * FROM `category` ORDER BY name ASC";
        $result = $conn->query($query);
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $cat = new Category();
            $cat->id   = $row['id'];
            $cat->name = $row['name'];
            $categories[] = $cat;
        }
        return $categories;
    }
}
