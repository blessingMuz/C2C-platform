<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status" => "login"]);
    exit();
}

$seller_id = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;

// Joins the users table to fetch the buyer's name, and standardizes total price
$sql = "SELECT 
            orders.id, 
            orders.created_at, 
            orders.status, 
            orders.total AS total, 
            users.name AS buyer_name, 
            products.name AS product_name
        FROM orders
        JOIN products ON orders.product_id = products.id
        JOIN users ON orders.user_id = users.id
        WHERE products.seller_id = $seller_id
        ORDER BY orders.created_at DESC
        LIMIT $limit";

$result = mysqli_query($conn, $sql);
$orders = [];

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($orders);
exit();
?>