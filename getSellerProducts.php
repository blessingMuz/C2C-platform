<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"login"]);
    exit();
}

$seller_id = $_SESSION['user_id'];
$result    = mysqli_query($conn,
    "SELECT * FROM products WHERE seller_id=$seller_id ORDER BY created_at DESC");

$products = [];
while($row = mysqli_fetch_assoc($result)) $products[] = $row;

header('Content-Type: application/json');
echo json_encode($products);
?>
