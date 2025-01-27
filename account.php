<?php
require_once 'Database.php';

class Account {
    private $connection;

    public function __construct() {
        $database = new Database();
        $this->connection = $database->getConnection();
    }


    public function generateAccountNumber() {
        do {
            $account_number = rand(1000000000, 9999999999);
            $query = "SELECT * FROM account_numbers WHERE account_number = ?";
            $stmt = $this->connection->prepare($query);
            $stmt->bind_param('s', $account_number);
            $stmt->execute();
            $result = $stmt->get_result();
        } while ($result->num_rows > 0); 

        return $account_number;
    }

    public function createAccount($user_id) {
        $account_number = $this->generateAccountNumber();
        $initial_balance = 10000;  
        $query = "INSERT INTO account_numbers (user_id, account_number, balance, created_at) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->connection->prepare($query);
        $created_at = date("Y-m-d H:i:s");
        $stmt->bind_param('isds', $user_id, $account_number, $initial_balance, $created_at);

        if ($stmt->execute()) {
            return $account_number;
        } else {
            return false;
        }
    }
}
