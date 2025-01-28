<?php
require_once 'Database.php';
$database = new Database();
$connection = $database->getConnection();

if (isset($_GET['account_number'])) {
    $account_number = $_GET['account_number'];

    // Query to get the recipient's name based on the account number
    $query = "SELECT firstname, lastname FROM account_numbers INNER JOIN users ON account_numbers.user_id = users.id WHERE account_numbers.account_number = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('s', $account_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $recipient = $result->fetch_assoc();
        echo $recipient['firstname'] . ' ' . $recipient['lastname'];
    } else {
        echo 'Recipient not found';
    }
}
?>
