<?php
require_once 'User.php';
session_start();

if (isset($_POST['submit'])) {
    // Collect form data
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone_number = $_POST['phone_number'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];

    // Create User object
    $user = new User();

    // Create new user and get the response
    $response = $user->createUser($firstname, $lastname, $email, $password, $phone_number, $gender, $address);

    // Set session message
    $_SESSION['msg'] = $response['message'];

    // Redirect based on the result
    if ($response['status']) {
        header('Location: loginForm.php');
    } else {
        header('Location: signup.php');
    }
} else {
    header('Location: signup.php');
}
?>