<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: loginForm.php');
    exit;
}

require_once 'Database.php';
$database = new Database();
$connection = $database->getConnection();

$recipient_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $transfer_pin = $_POST['transfer_pin']; 
    $recipient_account_number = $_POST['recipient_account_number'];
    $amount = (float) $_POST['amount'];
    $description = trim($_POST['description']);

    $query = "SELECT transfer_pin, balance FROM users INNER JOIN account_numbers ON users.id = account_numbers.user_id WHERE users.id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $sender_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sender = $result->fetch_assoc();

    if (!$sender || $sender['transfer_pin'] !== $transfer_pin) {
        $_SESSION['msg'] = 'Invalid transfer pin.';
        header('Location: transfer_money.php');
        exit;
    }

    if ($sender['balance'] < $amount) {
        $_SESSION['msg'] = 'Insufficient funds.';
        header('Location: transfer_money.php');
        exit;
    }

    $query = "SELECT user_id, firstname, lastname FROM account_numbers INNER JOIN users ON account_numbers.user_id = users.id WHERE account_numbers.account_number = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('s', $recipient_account_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $recipient = $result->fetch_assoc();

    if (!$recipient) {
        $_SESSION['msg'] = 'Recipient account not found.';
        header('Location: transfer_money.php');
        exit;
    }

    $recipient_id = $recipient['user_id'];
    $recipient_name = $recipient['firstname'] . ' ' . $recipient['lastname'];

    $connection->begin_transaction();

    try {
        $query = "UPDATE account_numbers SET balance = balance - ? WHERE user_id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param('di', $amount, $sender_id);
        $stmt->execute();

        $query = "UPDATE account_numbers SET balance = balance + ? WHERE user_id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param('di', $amount, $recipient_id);
        $stmt->execute();

        $query = "INSERT INTO transfer_history (sender_id, recipient_id, amount, description) VALUES (?, ?, ?, ?)";
        $stmt = $connection->prepare($query);
        $stmt->bind_param('iids', $sender_id, $recipient_id, $amount, $description);
        $stmt->execute();
        $connection->commit();

        $_SESSION['msg'] = 'Transfer successful!';
    } catch (Exception $e) {
        $connection->rollback();
        $_SESSION['msg'] = 'Transfer failed. Please try again.';
    }

    header('Location: transfer_money.php');
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Money</title>
    <script>
        function fetchRecipientName() {
            var accountNumber = document.getElementById('recipient_account_number').value;
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
        echo '<p>' . htmlspecialchars($_SESSION['msg']) . '</p>';
        unset($_SESSION['msg']);
    }
    ?>
    <form action="transfer_money.php" method="POST">
        <label for="recipient_account_number">Recipient Account Number:</label><br>
        <input type="text" id="recipient_account_number" name="recipient_account_number" required onkeyup="fetchRecipientName()"><br><br>

        <div id="recipient_name" style="font-weight: bold;"></div><br>

        <label for="amount">Amount:</label><br>
        <input type="number" id="amount" name="amount" step="0.01" min="0.01" required><br><br>

        <label for="description">Description (optional):</label><br>
        <textarea id="description" name="description"></textarea><br><br>

        <label for="transfer_pin">Transfer Pin:</label><br>
        <input type="password" id="transfer_pin" name="transfer_pin" maxlength="4" required><br><br>

        <button type="submit">Transfer</button>
    </form>
</body>
</html>
