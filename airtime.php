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

// Fetch current balance from account_numbers table
$query = "SELECT account_number, balance FROM account_numbers WHERE user_id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_account = $result->fetch_assoc();
$stmt->close();

$account_number = $user_account['account_number'] ?? null;
$current_balance = $user_account['balance'] ?? 0;

if (!$account_number) {
    $_SESSION['msg'] = 'Account not found!';
    header('Location: dashboard.php');
    exit;
}

// Fetch user's hashed transfer PIN
$query = "SELECT transfer_pin FROM users WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$stored_pin = $user['transfer_pin'] ?? '';

// Handle airtime recharge
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone_number = trim($_POST['phone_number']);
    $amount = trim($_POST['amount']);
    $transfer_pin = trim($_POST['transfer_pin']);

    // Validate phone number (must be 10-15 digits)
    if (!preg_match('/^\d{10,15}$/', $phone_number)) {
        $_SESSION['msg'] = 'Invalid phone number format.';
        header('Location: airtime.php');
        exit;
    }

    // Validate amount
    if (!is_numeric($amount) || $amount <= 0) {
        $_SESSION['msg'] = 'Invalid amount. Please enter a valid number.';
        header('Location: airtime.php');
        exit;
    }

    // Check balance
    if ($amount > $current_balance) {
        $_SESSION['msg'] = 'Insufficient balance.';
        header('Location: airtime.php');
        exit;
    }

    // Validate Transfer PIN (must be exactly 4 digits)
    if (!preg_match('/^\d{4}$/', $transfer_pin)) {
        $_SESSION['msg'] = 'Transfer PIN must be exactly 4 digits.';
        header('Location: airtime.php');
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
        header('Location: airtime.php');
        exit;
    }

    // Deduct airtime amount from balance
    $new_balance = $current_balance - $amount;
    $description = "Airtime Recharge to $phone_number";

    // Start transaction
    $connection->begin_transaction();

    try {
        // Update balance in account_numbers table
        $query = "UPDATE account_numbers SET balance = ? WHERE user_id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param('di', $new_balance, $user_id);
        $stmt->execute();
        $stmt->close();

        // Insert transaction record
        $query = "INSERT INTO transactions (sender_id, recipient_account_number, amount, transfer_date, description) 
                  VALUES (?, ?, ?, NOW(), ?)";
        $stmt = $connection->prepare($query);
        $stmt->bind_param('iids', $user_id, $account_number, $amount, $description);
        $stmt->execute();
        $stmt->close();

        // Commit transaction
        $connection->commit();

        $_SESSION['msg'] = 'Airtime recharge successful!';
    } catch (Exception $e) {
        $connection->rollback();
        $_SESSION['msg'] = 'Airtime recharge failed. Try again.';
    }

    header('Location: airtime.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Airtime Recharge</title>
</head>
<body>
    <h2>Recharge Airtime</h2>
    <?php
    if (isset($_SESSION['msg'])) {
        echo '<p style="color: red;">' . htmlspecialchars($_SESSION['msg']) . '</p>';
        unset($_SESSION['msg']);
    }
    ?>
    <p>Current Balance: <strong>$<?php echo number_format($current_balance, 2); ?></strong></p>

    <!-- Airtime Recharge Form -->
    <form action="airtime.php" method="POST">
        <label for="phone_number">Enter Phone Number:</label><br>
        <input type="text" id="phone_number" name="phone_number" pattern="\d{10,15}" required><br><br>

        <label for="amount">Enter Amount:</label><br>
        <input type="number" id="amount" name="amount" step="0.01" min="0.01" required><br><br>

        <label for="transfer_pin">Enter Transfer PIN:</label><br>
        <input type="password" id="transfer_pin" name="transfer_pin" maxlength="4" required><br><br>

        <button type="submit">Recharge Airtime</button>
    </form>

    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>
