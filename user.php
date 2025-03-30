<?php
// require_once 'Database.php';
// require_once 'account.php';

// class User {
//     private $connection;
//     private $account;

//     public function __construct() {
//         $database = new Database();
//         $this->connection = $database->getConnection();
//         $this->account = new Account(); 
//     }

//     // Create a new user
//     public function createUser($firstname, $lastname, $email, $password, $phone_number, $gender, $address) {
//         $query = "SELECT * FROM users WHERE email = ?";
//         $stmt = $this->connection->prepare($query);
//         $stmt->bind_param('s', $email);
//         $stmt->execute();
//         $result = $stmt->get_result();

//         if ($result->num_rows > 0) {
//             return [
//                 'status' => false,
//                 'message' => 'Email already exists'
//             ];
//         }


//         $hashp = password_hash($password, PASSWORD_DEFAULT);
//         $created_at = date("Y-m-d H:i:s");
//         $query = "INSERT INTO users (firstname, lastname, email, phone_number, gender, address, password, created_at) 
//                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
//         $stmt = $this->connection->prepare($query);
//         $stmt->bind_param('ssssssss', $firstname, $lastname, $email, $phone_number, $gender, $address, $hashp, $created_at);

//         if ($stmt->execute()) {
//             $user_id = $this->connection->insert_id;
//             $account_number = $this->account->createAccount($user_id);

//             if ($account_number) {
//                 return [
//                     'status' => true,
//                     'message' => 'User created successfully with account number: ' . $account_number,
//                     'account_number' => $account_number
//                 ];
//             } else {
//                 return [
//                     'status' => false,
//                     'message' => 'Failed to create account number'
//                 ];
//             }
//         } else {
//             return [
//                 'status' => false,
//                 'message' => 'Error occurred during user creation'
//             ];
//         }
//     }

//     // Login user
//     public function loginUser($email, $password) {
//         $query = "SELECT * FROM users WHERE email = ?";
//         $stmt = $this->connection->prepare($query);
//         $stmt->bind_param('s', $email);
//         $stmt->execute();
//         $result = $stmt->get_result();

//         if ($result->num_rows > 0) {
//             $user = $result->fetch_assoc();

//             if (password_verify($password, $user['password'])) {
//                 session_start();
//                 $_SESSION['user_id'] = $user['id'];
//                 $_SESSION['user_email'] = $user['email'];
//                 $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];

//                 $query = "SELECT account_number FROM account_numbers WHERE user_id = ?";
//                 $stmt = $this->connection->prepare($query);
//                 $stmt->bind_param('i', $user['id']);
//                 $stmt->execute();
//                 $result = $stmt->get_result();
//                 $account = $result->fetch_assoc();

//                 if ($account) {
//                     $_SESSION['account_number'] = $account['account_number'];
//                 }

//                 return [
//                     'status' => true,
//                     'message' => 'Login successful'
//                 ];
//             } else {
//                 return [
//                     'status' => false,
//                     'message' => 'Invalid password'
//                 ];
//             }
//         } else {
//             return [
//                 'status' => false,
//                 'message' => 'Email not found'
//             ];
//         }
//     }
// }

require_once 'Database.php';
require_once 'account.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class User {
    private $connection;
    private $account;

    public function __construct() {
        $database = new Database();
        $this->connection = $database->getConnection();
        $this->account = new Account();
    }

    // Create a new user
    public function createUser($firstname, $lastname, $email, $password, $phone_number, $gender, $address) {
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

        $hashp = password_hash($password, PASSWORD_DEFAULT);
        $created_at = date("Y-m-d H:i:s");

        $query = "INSERT INTO users (firstname, lastname, email, phone_number, gender, address, password, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->connection->prepare($query);
        $stmt->bind_param('ssssssss', $firstname, $lastname, $email, $phone_number, $gender, $address, $hashp, $created_at);

        if ($stmt->execute()) {
            $user_id = $this->connection->insert_id;
            $account_number = $this->account->createAccount($user_id);

            if ($account_number) {
                // Send Welcome Email
                if ($this->sendWelcomeEmail($firstname, $lastname, $email, $account_number)) {
                    return [
                        'status' => true,
                        'message' => 'User created successfully, and email sent.',
                        'account_number' => $account_number
                    ];
                } else {
                    return [
                        'status' => false,
                        'message' => 'User created, but email could not be sent.'
                    ];
                }
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

    private function sendWelcomeEmail($firstname, $lastname, $email, $account_number) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'timex5949@gmail.com'; 
            $mail->Password = 'kxqx dzgf jufi eqzt'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('your-email@gmail.com', 'SwiftPay Bank');
            $mail->addAddress($email);
            $mail->Subject = 'Welcome to SwiftPay Bank!';
            $mail->isHTML(true);
            $mail->Body = "
                <h3>Welcome to SwiftPay Bank...</h3>
                <h1>$firstname $lastname!</h1>
                <p>Your account has been successfully created.</p>
                <p><strong>Account Number:</strong> $account_number</p>
                <p>You can use this account number for all transactions..</p>
                <br>
                <p>Best Regards,</p>
                <p><strong>SwiftPay Bank Team</strong></p>
            ";

            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }
    
    
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
?>
