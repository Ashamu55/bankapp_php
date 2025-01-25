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
        /* Add your styling here */
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
