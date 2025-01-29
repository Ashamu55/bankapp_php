<?php
session_start();
require_once 'Database.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: signup.html');
    exit;
}

$user_id = $_SESSION['user_id']; 


if (isset($_POST['upload'])) {
    if (!empty($_FILES['profile_pic']['name'])) {
        $file = $_FILES['profile_pic'];

    
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        $fileType = $file['type'];

        
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));


        if (in_array($fileExt, $allowed)) {
            if ($fileError === 0) {
                if ($fileSize <= 2000000) { 
                    $newFileName = uniqid('profile_', true) . '.' . $fileExt;
                    $uploadDir = 'uploads/profile_pics/';
                    $uploadPath = $uploadDir . $newFileName;

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    if (move_uploaded_file($fileTmpName, $uploadPath)) {
                        $database = new Database();
                        $connection = $database->getConnection();
                        $query = "UPDATE users SET profile_pic = ? WHERE id = ?";
                        $stmt = $connection->prepare($query);
                        $stmt->bind_param('si', $uploadPath, $user_id);

                        if ($stmt->execute()) {
                            $_SESSION['profile_pic'] = $uploadPath; 
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
