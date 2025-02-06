<php
<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: loginForm.php');
    exit;
}

require_once 'Database.php';
$database = new Database();
$connection = $database->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token.';
    } else {
        $user_id = $_SESSION['user_id'];
        $current_password = trim($_POST['current_password']);
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        // Validate inputs
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New password and confirmation do not match.';
        } elseif (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } else {
            // Get current password from database
            $query = "SELECT password FROM users WHERE id = ?";
            $stmt = $connection->prepare($query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if (!$user || !password_verify($current_password, $user['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password in database
                $query = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $connection->prepare($query);
                $stmt->bind_param('si', $hashed_password, $user_id);

                if ($stmt->execute()) {
                    $success = 'Password successfully changed!';
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            }
        }
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <style>
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h2>Change Password</h2>
    
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form action="change_password.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div>
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>
        
        <div>
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required 
                   minlength="8" title="Minimum 8 characters">
        </div>
        
        <div>
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <button type="submit">Change Password</button>
    </form>
    
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>