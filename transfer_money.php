<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: loginForm.php');
    exit;
}

require_once 'Database.php';
$database = new Database();
$connection = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['msg'] = 'Invalid CSRF token.';
        header('Location: transfer_money.php');
        exit;
    }


    $user_id = $_SESSION['user_id'];
    $transfer_pin = trim($_POST['transfer_pin']);
    $amount = trim($_POST['amount']);
    $recipient_account_number = trim($_POST['recipient_id']);
    $description = trim($_POST['description']); 

    if (empty($transfer_pin) || empty($amount) || empty($recipient_account_number)) {
        $_SESSION['msg'] = 'All fields are required.';
        header('Location: transfer_money.php');
        exit;
    }

    if (!is_numeric($amount) || $amount <= 0) {
        $_SESSION['msg'] = 'Invalid amount. Please enter a positive number.';
        header('Location: transfer_money.php');
        exit;
    }

    if (!preg_match('/^\d{4}$/', $transfer_pin)) {
        $_SESSION['msg'] = 'Transfer PIN must be exactly 4 digits.';
        header('Location: transfer_money.php');
        exit;
    }

    $query = "SELECT transfer_pin FROM users WHERE id = ?";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        $_SESSION['msg'] = 'Database error. Please try again.';
        header('Location: transfer_money.php');
        exit;
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || empty($user['transfer_pin'])) {
        $_SESSION['msg'] = 'You have not set a Transfer PIN. Please create one.';
        header('Location: create_transfer_pin.php');
        exit;
    }

    if (!password_verify($transfer_pin, $user['transfer_pin'])) {
        $_SESSION['msg'] = 'Invalid Transfer PIN!';
        header('Location: transfer_money.php');
        exit;
    }

    $query = "SELECT balance, account_number FROM account_numbers WHERE user_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sender_account = $result->fetch_assoc();

    if (!$sender_account) {
        $_SESSION['msg'] = 'Account not found.';
        header('Location: transfer_money.php');
        exit;
    }

    $sender_balance = $sender_account['balance'];
    $sender_account_number = $sender_account['account_number'];

    if ($sender_balance < $amount) {
        $_SESSION['msg'] = 'Insufficient balance.';
        header('Location: transfer_money.php');
        exit;
    }

    $query = "SELECT user_id, balance FROM account_numbers WHERE account_number = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('s', $recipient_account_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $recipient_account = $result->fetch_assoc();

    if (!$recipient_account) {
        $_SESSION['msg'] = 'Recipient account not found.';
        header('Location: transfer_money.php');
        exit;
    }

    $recipient_id = $recipient_account['user_id'];
    $recipient_balance = $recipient_account['balance'];

    $new_sender_balance = $sender_balance - $amount;
    $query = "UPDATE account_numbers SET balance = ? WHERE user_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('di', $new_sender_balance, $user_id);
    if (!$stmt->execute()) {
        $_SESSION['msg'] = 'Failed to update sender balance.';
        header('Location: transfer_money.php');
        exit;
    }

    $new_recipient_balance = $recipient_balance + $amount;
    $query = "UPDATE account_numbers SET balance = ? WHERE user_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('di', $new_recipient_balance, $recipient_id);
    if (!$stmt->execute()) {
        $_SESSION['msg'] = 'Failed to update recipient balance.';
        header('Location: transfer_money.php');
        exit;
    }

    $query = "INSERT INTO transactions (sender_id, recipient_account_number, amount, transfer_date, description) 
              VALUES (?, ?, ?, NOW(), ?)";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        $_SESSION['msg'] = 'Database error. Please try again.';
        header('Location: transfer_money.php');
        exit;
    }
    $stmt->bind_param('isds', $user_id, $recipient_account_number, $amount, $description);
    if (!$stmt->execute()) {
        $_SESSION['msg'] = 'Failed to log transaction.';
        header('Location: transfer_money.php');
        exit;
    }

    $_SESSION['msg'] = 'Transfer successful!';
    header('Location: dashboard.php');
    exit;
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Money</title>
    <script>
        function fetchRecipientName() {
            var accountNumber = document.getElementById('recipient_id').value;
            if (accountNumber.length >= 5) {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'fetch_recipient_name.php?account_number=' + accountNumber, true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        document.getElementById('recipient_name').innerText = xhr.responseText;
                    }
                };
                xhr.send();
            } else {
                document.getElementById('recipient_name').innerText = '';
            }
        }
    </script>
</head>
<body>
    <h2>Transfer Money</h2>
    <?php
    if (isset($_SESSION['msg'])) {
        echo '<p style="color: red;">' . htmlspecialchars($_SESSION['msg']) . '</p>';
        unset($_SESSION['msg']);
    }
    ?>
    <form action="transfer_money.php" method="POST">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <label for="recipient_id">Recipient Account Number:</label><br>
        <input type="text" id="recipient_id" name="recipient_id" required onkeyup="fetchRecipientName()"><br><br>

        <div id="recipient_name" style="font-weight: bold;"></div><br>

        <label for="amount">Amount:</label><br>
        <input type="number" id="amount" name="amount" step="0.01" min="0.01" required><br><br>

        <label for="description">Description (optional):</label><br>
        <textarea id="description" name="description"></textarea><br><br>

        <label for="transfer_pin">Transfer PIN:</label><br>
        <input type="password" id="transfer_pin" name="transfer_pin" maxlength="4" required><br><br>

        <button type="submit">Transfer</button>
    </form>
</body>
</html>