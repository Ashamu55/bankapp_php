<?php
require_once 'Database.php';
require_once 'User.php';
session_start();

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Create User object
    $user = new User();

    // Attempt to log the user in
    $response = $user->loginUser($email, $password);

    // Set session message
    $_SESSION['msg'] = $response['message'];

    // Redirect based on the result
    if ($response['status']) {
        header('Location: dashboard.php'); // Redirect to dashboard
    } else {
        header('Location: login.php'); // Redirect back to login
    }
}
?>