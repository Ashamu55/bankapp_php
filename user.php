<?php
require_once 'Database.php';
require_once 'account.php';

class User {
    private $connection;
    private $account;

    public function __construct() {
        // Initialize Database connection using Database class
        $database = new Database();
        $this->connection = $database->getConnection();
        $this->account = new Account(); // Create instance of Account class
    }

    // Create a new user
    public function createUser($firstname, $lastname, $email, $password, $phone_number, $gender, $address) {
        // Check if email already exists
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->connection->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return [
                'status' => false,
                'message' => 'Email already exists'
            ];
        }

        // Hash the password
        $hashp = password_hash($password, PASSWORD_DEFAULT);
        $created_at = date("Y-m-d H:i:s");

        // Insert the new user into the users table
        $query = "INSERT INTO users (firstname, lastname, email, phone_number, gender, address, password, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->connection->prepare($query);
        $stmt->bind_param('ssssssss', $firstname, $lastname, $email, $phone_number, $gender, $address, $hashp, $created_at);

        if ($stmt->execute()) {
            // Get the ID of the newly created user
            $user_id = $this->connection->insert_id;

            // Create account number for the user
            $account_number = $this->account->createAccount($user_id);

            if ($account_number) {
                return [
                    'status' => true,
                    'message' => 'User created successfully with account number: ' . $account_number,
                    'account_number' => $account_number
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Failed to create account number'
                ];
            }
        } else {
            return [
                'status' => false,
                'message' => 'Error occurred during user creation'
            ];
        }
    }

    // Login user
    public function loginUser($email, $password) {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->connection->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];

                // Retrieve account number
                $query = "SELECT account_number FROM account_numbers WHERE user_id = ?";
                $stmt = $this->connection->prepare($query);
                $stmt->bind_param('i', $user['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $account = $result->fetch_assoc();

                if ($account) {
                    $_SESSION['account_number'] = $account['account_number'];
                }

                return [
                    'status' => true,
                    'message' => 'Login successful'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Invalid password'
                ];
            }
        } else {
            return [
                'status' => false,
                'message' => 'Email not found'
            ];
        }
    }
}