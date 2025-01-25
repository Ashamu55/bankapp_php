<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to signup page if not logged in
    header('Location: signup.html');
    exit;
}

// Include the database connection
require_once 'Database.php';

// Create a new database connection
$database = new Database();
$connection = $database->getConnection();

// Retrieve account number from session
$account_number = $_SESSION['account_number'];

// Query to get the account balance based on the account number
$query = "SELECT balance FROM account_numbers WHERE account_number = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param('s', $account_number);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the balance
$balance = 0; // Default balance if no result found
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $balance = $row['balance'];
}

// Display the dashboard
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <style>
          body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
        }

        /* Dashboard Container */
        .dashboard {
            width: 100%;
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .dashboard h2 {
            text-align: center;
            color: #333;
            font-size: 24px;
        }

        .dashboard p {
            font-size: 18px;
            color: #555;
            margin: 10px 0;
        }

        .dashboard a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
            transition: background-color 0.3s ease;
        }

        .dashboard a:hover {
            background-color: #2980b9;
        }

        .account-info {
            background-color: #ecf0f1;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }

        .account-info p {
            font-size: 16px;
            color: #2c3e50;
        }

        .balance {
            font-weight: bold;
            color: #27ae60;
            font-size: 20px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
        <p>Your email: <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
        <p>Your Account Number: <?php echo htmlspecialchars($_SESSION['account_number']); ?></p>
        <p>Your Account Balance: $<?php echo number_format($balance, 2); ?></p> <!-- Display balance formatted as currency -->
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>
