<?php
session_start();
include("config/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Check if this user is a seller by checking if they own any products
$role_check = "SELECT id FROM products WHERE seller_id = $user_id LIMIT 1";
$role_result = mysqli_query($conn, $role_check);

$is_seller = (mysqli_num_rows($role_result) > 0);

$conversations = [];

if ($is_seller) {
    // Fetch all distinct buyers who messaged this seller
    $sql = "SELECT DISTINCT 
                m.product_id,
                p.name AS product_name,
                IF(m.sender_id = $user_id, m.receiver_id, m.sender_id) AS buyer_id,
                u.name AS buyer_name
            FROM messages m
            JOIN products p ON m.product_id = p.id
            JOIN users u ON u.id = IF(m.sender_id = $user_id, m.receiver_id, m.sender_id)
            WHERE p.seller_id = $user_id
            ORDER BY m.id DESC";
} else {
    //  Fetch all distinct sellers this buyer has messaged
    $sql = "SELECT DISTINCT 
                m.product_id,
                p.name AS product_name,
                u.id AS partner_id,
                u.name AS partner_name
            FROM messages m
            JOIN products p ON m.product_id = p.id
            JOIN users u ON p.seller_id = u.id
            WHERE (m.sender_id = $user_id OR m.receiver_id = $user_id)
            ORDER BY m.id DESC";
}

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
    exit();
}

while ($row = mysqli_fetch_assoc($result)) {
    $conversations[] = $row;
}

echo json_encode($conversations);
exit();
?>