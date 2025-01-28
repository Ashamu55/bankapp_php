<?php
session_start();
require_once 'Database.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signup.html');
    exit;
}

$user_id = $_SESSION['user_id']; // User ID from session

// Check if the upload form was submitted
if (isset($_POST['upload'])) {
    // Check if a file was uploaded
    if (!empty($_FILES['profile_pic']['name'])) {
        $file = $_FILES['profile_pic'];

        // File properties
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        $fileType = $file['type'];

        // Allowed file types
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Check if the uploaded file is allowed
        if (in_array($fileExt, $allowed)) {
            if ($fileError === 0) {
                if ($fileSize <= 2000000) { // Limit file size to 2MB
                    // Generate a unique file name
                    $newFileName = uniqid('profile_', true) . '.' . $fileExt;
                    $uploadDir = 'uploads/profile_pics/';
                    $uploadPath = $uploadDir . $newFileName;

                    // Move the file to the uploads directory
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    if (move_uploaded_file($fileTmpName, $uploadPath)) {
                        // Update the user's profile picture in the database
                        $database = new Database();
                        $connection = $database->getConnection();
                        $query = "UPDATE users SET profile_pic = ? WHERE id = ?";
                        $stmt = $connection->prepare($query);
                        $stmt->bind_param('si', $uploadPath, $user_id);

                        if ($stmt->execute()) {
                            $_SESSION['profile_pic'] = $uploadPath; // Update the session variable
                            header('Location: dashboard.php');
                            exit;
                        } else {
                            echo "Failed to update profile picture in database.";
                        }
                    } else {
                        echo "Failed to upload the file.";
                    }
                } else {
                    echo "File size exceeds the limit of 2MB.";
                }
            } else {
                echo "There was an error uploading the file.";
            }
        } else {
            echo "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    } else {
        echo "No file was uploaded.";
    }
} else {
    header('Location: dashboard.php');
}
?>
