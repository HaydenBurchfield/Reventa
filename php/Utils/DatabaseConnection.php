<?php
class DatabaseConnection {
    private $host = "localhost";
    private $username = "root";
    private $password = "password";
    private $database = "reventa";

    private $connection;

    public function __construct() {
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);

        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8mb4");
    }

    public function connect() {
        return $this->connection;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
?>