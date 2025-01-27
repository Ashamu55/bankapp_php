<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: signup.html');
    exit;
}
require_once 'Database.php';

$database = new Database();
$connection = $database->getConnection();

$user_id = $_SESSION['user_id'];
$query = "SELECT profile_pic FROM users WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$profile_pic = 'uploads/profile_pics/default.png';
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $profile_pic = !empty($row['profile_pic']) ? $row['profile_pic'] : $profile_pic;
}

$query_balance = "SELECT balance FROM account_numbers WHERE user_id = ?";
$stmt_balance = $connection->prepare($query_balance);
$stmt_balance->bind_param('i', $user_id);
$stmt_balance->execute();
$result_balance = $stmt_balance->get_result();

$balance = 0; 
if ($result_balance->num_rows > 0) {
    $row_balance = $result_balance->fetch_assoc();
    $balance = $row_balance['balance'];
}
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

        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 20px;
            object-fit: cover;
            border: 2px solid #ddd;
        }

        .dashboard h2 {
            color: #333;
            font-size: 24px;
        }

        .dashboard p {
            font-size: 18px;
            color: #555;
            margin: 10px 0;
        }

        .dashboard a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
            transition: background-color 0.3s ease;
        }

        .dashboard a:hover {
            background-color: #2980b9;
        }

        .balance {
            font-weight: bold;
            color: #27ae60;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <a href="upload_profile_pic.php">
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" class="profile-pic">
        </a>
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
        <p>Your email: <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
        <p>Your Account Number: <?php echo htmlspecialchars($_SESSION['account_number']); ?></p>
        <p>Your Account Balance: $<?php echo number_format($balance, 2); ?></p>

        <a href="logout.php">Logout</a>
    </div>
</body>
</html>
