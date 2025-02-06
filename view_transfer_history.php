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

$query = "SELECT t.id, 
                COALESCE(u1.firstname, 'Bank') AS sender_firstname, 
                COALESCE(u1.lastname, '') AS sender_lastname, 
                u2.firstname AS recipient_firstname, u2.lastname AS recipient_lastname, 
                t.amount, t.transfer_date, t.description
        FROM transactions t
        LEFT JOIN users u1 ON t.sender_id = u1.id
        JOIN account_numbers a ON t.recipient_account_number = a.account_number
        JOIN users u2 ON a.user_id = u2.id
        WHERE t.sender_id = ? OR a.user_id = ? OR t.sender_id IS NULL
        ORDER BY t.transfer_date DESC";

$stmt = $connection->prepare($query);
$stmt->bind_param('ii', $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer History</title>
</head>
<body>
    <h2>Your Transfer History</h2>
    <?php
    if ($result->num_rows > 0) {
        echo '<table border="1">';
        echo '<tr><th>Sender</th><th>Recipient</th><th>Amount</th><th>Date</th><th>Description</th></tr>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['sender_firstname']) . ' ' . htmlspecialchars($row['sender_lastname']) . '</td>';
            echo '<td>' . htmlspecialchars($row['recipient_firstname']) . ' ' . htmlspecialchars($row['recipient_lastname']) . '</td>';
            echo '<td>' . number_format($row['amount'], 2) . '</td>';
            echo '<td>' . htmlspecialchars($row['transfer_date']) . '</td>';
            echo '<td>' . htmlspecialchars($row['description']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>No transfer history available.</p>';
    }
    ?>
</body>
</html>
