<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to signup page if not logged in
    header('Location: signup.html');
    exit;
}

// Include the database connection
require_once 'Database.php';

// Create a new database connection
$database = new Database();
$connection = $database->getConnection();

// Retrieve account number from session
$account_number = $_SESSION['account_number'];

// Query to get the account balance based on the account number
$query = "SELECT balance FROM account_numbers WHERE account_number = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param('s', $account_number);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the balance
$balance = 0; // Default balance if no result found
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $balance = $row['balance'];
}

// Fetch the user's profile picture from the database
$user_id = $_SESSION['user_id'];
$query = "SELECT profile_pic FROM users WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile_pic = 'default-avatar.png'; // Default profile picture
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (!empty($row['profile_pic'])) {
        $profile_pic = $row['profile_pic'];
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $target_dir = "uploads/";
    $file_name = basename($_FILES['profile_pic']['name']);
    $target_file = $target_dir . uniqid() . '_' . $file_name;
    $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate image type
    $valid_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($image_file_type, $valid_extensions)) {
        // Move uploaded file to the target directory
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
            // Update the database with the new profile picture path
            $query = "UPDATE users SET profile_pic = ? WHERE id = ?";
            $stmt = $connection->prepare($query);
            $stmt->bind_param('si', $target_file, $user_id);
            if ($stmt->execute()) {
                // Update the session with the new profile picture
                $profile_pic = $target_file;
                $_SESSION['msg'] = 'Profile picture updated successfully!';
            } else {
                $_SESSION['msg'] = 'Failed to update profile picture in database.';
            }
        } else {
            $_SESSION['msg'] = 'Failed to upload the profile picture.';
        }
    } else {
        $_SESSION['msg'] = 'Invalid file type. Please upload a JPG, JPEG, PNG, or GIF image.';
    }
}

// Display the dashboard
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
        }

        .dashboard {
            width: 100%;
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .profile-pic-container {
            position: relative;
            display: inline-block;
            width: 120px;
            height: 120px;
            margin: 0 auto;
            border-radius: 50%;
            overflow: hidden;
            background-color: #ddd;
            cursor: pointer;
        }

        .profile-pic-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-pic-container input[type="file"] {
            display: none;
        }

        .profile-pic-container:hover {
            opacity: 0.8;
        }

        h2 {
            color: #333;
            font-size: 24px;
            margin-top: 20px;
        }

        p {
            font-size: 18px;
            color: #555;
            margin: 10px 0;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        a:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="profile-pic-container">
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" id="profile-pic">
                <input type="file" name="profile_pic" id="upload-pic" onchange="this.form.submit()">
            </div>
        </form>
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2> // last most
        <p>Your email: <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
        <p>Your Account Number: <?php echo htmlspecialchars($_SESSION['account_number']); ?></p>
        <p>Your Account Balance: $<?php echo number_format($balance, 2); ?></p>
        <a href="logout.php">Logout</a>
        <p><?php echo isset($_SESSION['msg']) ? htmlspecialchars($_SESSION['msg']) : ''; ?></p>
    </div>
    <script>
        document.querySelector('.profile-pic-container').addEventListener('click', function() {
            document.getElementById('upload-pic').click();
        });
    </script>
</body>
</html>
