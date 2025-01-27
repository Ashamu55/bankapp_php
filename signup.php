<?php
require_once 'User.php';
session_start();

if (isset($_POST['submit'])) {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone_number = $_POST['phone_number'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];


    $user = new User();
    $response = $user->createUser($firstname, $lastname, $email, $password, $phone_number, $gender, $address);
    $_SESSION['msg'] = $response['message'];

    if ($response['status']) {
        header('Location: loginForm.php');
    } else {
        header('Location: signup.php');
    }
} else {
    header('Location: signup.php');
}
?>
