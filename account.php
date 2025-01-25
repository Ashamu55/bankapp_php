<?php
require_once 'Database.php';

class Account {
    private $connection;

    public function __construct() {
        // Initialize Database connection using Database class
        $database = new Database();
        $this->connection = $database->getConnection();
    }

    // Generate a unique 10-digit account number
    public function generateAccountNumber() {
        do {
            $account_number = rand(1000000000, 9999999999);

            // Check if the account number already exists
            $query = "SELECT * FROM account_numbers WHERE account_number = ?";
            $stmt = $this->connection->prepare($query);
            $stmt->bind_param('s', $account_number);
            $stmt->execute();
            $result = $stmt->get_result();
        } while ($result->num_rows > 0); // Ensure uniqueness

        return $account_number;
    }

    // Create account number for a user and set an initial balance of $10,000
    public function createAccount($user_id) {
        // Generate account number
        $account_number = $this->generateAccountNumber();
        $initial_balance = 10000;  // Set the initial balance to $10,000

        // Insert the account number and initial balance into the account_numbers table
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
