<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status" => "login"]);
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT cart.id, cart.quantity, products.name, 
        products.price, products.image, products.id as product_id
        FROM cart
        JOIN products ON cart.product_id = products.id
        WHERE cart.user_id = $user_id";

$result = mysqli_query($conn, $sql);
$items  = [];

while($row = mysqli_fetch_assoc($result)){
    $items[] = $row;
}

echo json_encode($items);
?>