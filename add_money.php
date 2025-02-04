<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: loginForm.php');
    exit;
}

require_once 'Database.php';
$database = new Database();
$connection = $database->getConnection();

$user_id = $_SESSION['user_id'];

// Fetch current balance from account_unmber table
$query = "SELECT balance FROM account_numbers WHERE user_id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_account = $result->fetch_assoc();
$current_balance = $user_account['balance'] ?? 0;

// Fetch user's hashed transfer PIN from users table
$query = "SELECT transfer_pin FROM users WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stored_pin = $user['transfer_pin'] ?? '';

// Handle deposit request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = trim($_POST['amount']);
    $transfer_pin = trim($_POST['transfer_pin']);

    // Validate amount
    if (!is_numeric($amount) || $amount <= 0) {
        $_SESSION['msg'] = 'Invalid amount. Please enter a valid number.';
        header('Location: add_money.php');
        exit;
    }

    // Validate Transfer PIN (must be exactly 4 digits)
    if (!preg_match('/^\d{4}$/', $transfer_pin)) {
        $_SESSION['msg'] = 'Transfer PIN must be exactly 4 digits.';
        header('Location: add_money.php');
        exit;
    }

    // Check if transfer PIN is set
    if (empty($stored_pin)) {
        $_SESSION['msg'] = 'You have not set a Transfer PIN. Please create one.';
        header('Location: create_transfer_pin.php');
        exit;
    }

    // Verify transfer PIN
    if (!password_verify($transfer_pin, $stored_pin)) {
        $_SESSION['msg'] = 'Invalid Transfer PIN!';
        header('Location: add_money.php');
        exit;
    }

    // Update balance in account_unmber table
    $new_balance = $current_balance + $amount;
    $query = "UPDATE account_numbers SET balance = ? WHERE user_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('di', $new_balance, $user_id);
    if (!$stmt->execute()) {
        $_SESSION['msg'] = 'Failed to add money. Try again.';
        header('Location: add_money.php');
        exit;
    }

    // Insert deposit record in transactions table
    $description = "Deposit";
    $query = "INSERT INTO transactions (sender_id, recipient_account_number, amount, transfer_date, description) 
              VALUES (?, NULL, ?, NOW(), ?)";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('ids', $user_id, $amount, $description);
    if (!$stmt->execute()) {
        $_SESSION['msg'] = 'Deposit recorded failed.';
        header('Location: add_money.php');
        exit;
    }

    $_SESSION['msg'] = 'Money added successfully!';
    header('Location: add_money.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Money</title>
</head>
<body>
    <h2>Add Money to Balance</h2>
    <?php
    if (isset($_SESSION['msg'])) {
        echo '<p style="color: red;">' . htmlspecialchars($_SESSION['msg']) . '</p>';
        unset($_SESSION['msg']);
    }
    ?>
    <p>Current Balance: <strong>$<?php echo number_format($current_balance, 2); ?></strong></p>

    <form action="add_money.php" method="POST">
        <label for="amount">Enter Amount:</label><br>
        <input type="number" id="amount" name="amount" step="0.01" min="0.01" required><br><br>

        <label for="transfer_pin">Enter Transfer PIN:</label><br>
        <input type="password" id="transfer_pin" name="transfer_pin" maxlength="4" required><br><br>

        <button type="submit">Add Money</button>
    </form>

    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>
