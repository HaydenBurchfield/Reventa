<?php 
require_once __DIR__ . '/../objects/Condition.php';

class Category {
    public $id;
    public $name;
    private $db;

    public function __construct() {
        $this->db = new DatabaseConnection();
    }

    public function getAllCategories() {
        $conn = $this->db->connect();
        $query = "SELECT * FROM `category`";
        $result = $conn->query($query);
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $category = new Category();
            $category->id = $row['id'];
            $category->name = $row['name'];
            $categories[] = $category;
        }
        return $categories;
    }
}










?>