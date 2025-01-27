<?php
require_once 'Database.php';
require_once 'User.php';
session_start();

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $user = new User();

    $response = $user->loginUser($email, $password);
    $_SESSION['msg'] = $response['message'];

    if ($response['status']) {
        header('Location: dashboard.php');
    } else {
        header('Location: login.php');
    }
}
?>
