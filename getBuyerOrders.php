<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"login"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$limit   = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;

$sql = "SELECT orders.*, products.name as product_name,
        products.image, products.seller_id, products.id as product_id
        FROM orders
        JOIN products ON orders.product_id = products.id
        WHERE orders.user_id = $user_id
        ORDER BY orders.created_at DESC
        LIMIT $limit";

$result = mysqli_query($conn, $sql);
$orders = [];
while($row = mysqli_fetch_assoc($result)) $orders[] = $row;

header('Content-Type: application/json');
echo json_encode($orders);
?>
