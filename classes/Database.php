<?php
class Database {
    public $conn;

    public function __construct() {
        $this->conn = new mysqli("localhost", "root", "","movieticketing");
        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
    }
}
?>
