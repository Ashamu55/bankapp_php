<?php
require 'config.php'; // Include your database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Check if email exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "No user found with this email.";
        exit;
    }

    // Generate a token
    $token = bin2hex(random_bytes(50));

    // Store token in the database
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
    $stmt->execute([$email, $token]);

    // Send reset link via email
    $reset_link = "http://yourdomain.com/reset_password.php?token=" . $token;
    $subject = "Password Reset Request";
    $message = "Click the link below to reset your password: \n\n" . $reset_link;
    $headers = "From: no-reply@yourdomain.com";

    if (mail($email, $subject, $message, $headers)) {
        echo "A password reset link has been sent to your email.";
    } else {
        echo "Failed to send email.";
    }
}
?>
