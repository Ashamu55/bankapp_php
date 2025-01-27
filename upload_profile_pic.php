<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: loginForm.php');
    exit;
}


require_once 'Database.php';
$database = new Database();
$connection = $database->getConnection();


$user_id = $_SESSION['user_id'];
if (isset($_POST['submit']) && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
    $upload_dir = 'uploads/profile_pics/';
    $file_name = basename($_FILES['profile_pic']['name']);
    $file_tmp = $_FILES['profile_pic']['tmp_name'];
    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array(strtolower($file_ext), $allowed_exts)) {
        $new_file_name = uniqid() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_file_name;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (move_uploaded_file($file_tmp, $upload_path)) {
            $query = "UPDATE users SET profile_pic = ? WHERE id = ?";
            $stmt = $connection->prepare($query);
            $stmt->bind_param('si', $upload_path, $user_id);

            if ($stmt->execute()) {
                $_SESSION['msg'] = 'Profile picture updated successfully!';
            } else {
                $_SESSION['msg'] = 'Failed to update profile picture in database.';
            }
        } else {
            $_SESSION['msg'] = 'Failed to upload profile picture.';
        }
    } else {
        $_SESSION['msg'] = 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.';
    }
} else {
    $_SESSION['msg'] = 'No file uploaded or an error occurred.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Profile Picture</title>
</head>
<body>
    <h2>Upload New Profile Picture</h2>
    <form action="upload_profile_pic.php" method="POST" enctype="multipart/form-data">
        <label for="profile_pic">Select Profile Picture:</label>
        <input type="file" name="profile_pic" id="profile_pic" accept="image/*" required>
        <button type="submit" name="submit">Upload Picture</button>
    </form>

    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>
