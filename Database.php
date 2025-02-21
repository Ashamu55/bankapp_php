<?php
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $db = 'bankapp';
    private $connection;

    public function __construct() {
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->db);
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
    }

    public function getConnection() {
        return $this->connection;
    }
}
?>