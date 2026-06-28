<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status" => "login"]);
    exit();
}

$cart_id  = (int)$_POST['cart_id'];
$quantity = (int)$_POST['quantity'];
$user_id  = $_SESSION['user_id'];

mysqli_query($conn, "UPDATE cart SET quantity=$quantity 
                     WHERE id=$cart_id AND user_id=$user_id");
echo json_encode(["status" => "success"]);
?>