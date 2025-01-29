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

$query = "SELECT th.id, u1.firstname AS sender_firstname, u1.lastname AS sender_lastname,
          u2.firstname AS recipient_firstname, u2.lastname AS recipient_lastname, 
          th.amount, th.transfer_date, th.description
          FROM transfer_history th
          JOIN users u1 ON th.sender_id = u1.id
          JOIN users u2 ON th.recipient_id = u2.id
          WHERE th.sender_id = ? OR th.recipient_id = ?
          ORDER BY th.transfer_date DESC";
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
