<?php
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: loginForm.php');
    exit;
}

require_once 'Database.php';
$database = new Database();
$connection = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $transfer_pin = trim($_POST['transfer_pin']);

    // Validate Transfer PIN
    if (empty($transfer_pin)) {
        $_SESSION['msg'] = 'Transfer PIN cannot be empty.';
        header('Location: create_transfer_pin.php');
        exit;
    }

    if (!preg_match('/^\d{4}$/', $transfer_pin)) {
        $_SESSION['msg'] = 'Transfer PIN must be exactly 4 digits.';
        header('Location: create_transfer_pin.php');
        exit;
    }

    // Check if the user already has a Transfer PIN
    $query = "SELECT transfer_pin FROM users WHERE id = ?";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        $_SESSION['msg'] = 'Database error. Please try again.';
        header('Location: create_transfer_pin.php');
        exit;
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && !empty($user['transfer_pin'])) {
        $_SESSION['msg'] = 'You already have a Transfer PIN. Update it if needed.';
        header('Location: dashboard.php');
        exit;
    }

    // Hash the Transfer PIN
    $hashed_pin = password_hash($transfer_pin, PASSWORD_DEFAULT);
    $query = "UPDATE users SET transfer_pin = ? WHERE id = ?";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        $_SESSION['msg'] = 'Database error. Please try again.';
        header('Location: create_transfer_pin.php');
        exit;
    }
    $stmt->bind_param('si', $hashed_pin, $user_id);

    if ($stmt->execute()) {
        $_SESSION['msg'] = 'Transfer PIN successfully created/updated!';
        header('Location: dashboard.php');
        exit;
    } else {
        $_SESSION['msg'] = 'An error occurred. Please try again.';
        header('Location: create_transfer_pin.php');
        exit;
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Transfer Pin</title>
</head>
<body>
    <h2>Create or Update Transfer Pin</h2>
    <?php
    if (isset($_SESSION['msg'])) {
        echo '<p>' . htmlspecialchars($_SESSION['msg']) . '</p>';
        unset($_SESSION['msg']);
    }
    ?>
    <form action="create_transfer_pin.php" method="POST">
        <label for="transfer_pin">Transfer Pin (4 digits):</label><br>
        <input type="password" id="transfer_pin" name="transfer_pin" maxlength="4" required><br><br>
        <button type="submit">Create/Update Pin</button>
    </form>
</body>
</html>
