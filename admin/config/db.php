<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'crmprojects';
    private $conn;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        // Fix for line 15
        $this->conn->query("SET NAMES utf8mb4");
    }

    public function getConnection() {
        return $this->conn;
    }

    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }

    public function escapeString($string) {
        return $this->conn->real_escape_string($string);
    }
}
?>