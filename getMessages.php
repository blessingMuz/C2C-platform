<?php
session_start();
include("config/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

// Convert session to integer to ensure matching types
$current_user = (int)$_SESSION['user_id'];
$chat_partner = isset($_GET['chat_partner']) ? (int)$_GET['chat_partner'] : 0;
$product_id   = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Fetch all messages where current user is either sender or receiver
$sql = "SELECT * FROM messages 
        WHERE product_id = $product_id 
        AND ((sender_id = $current_user AND receiver_id = $chat_partner) 
          OR (sender_id = $chat_partner AND receiver_id = $current_user))
        ORDER BY created_at ASC";

$result = mysqli_query($conn, $sql);
$chat_history = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        //  cast database values to integers for accurate strict comparison
        $row['is_mine'] = ((int)$row['sender_id'] === $current_user);
        $chat_history[] = $row;
    }
}

echo json_encode($chat_history);
exit();
?>