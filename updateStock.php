<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"login"]);
    exit();
}

$product_id = (int)$_POST['product_id'];
$stock      = (int)$_POST['stock'];
$seller_id  = $_SESSION['user_id'];

if(mysqli_query($conn, "UPDATE products SET stock=$stock WHERE id=$product_id AND seller_id=$seller_id")){
    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error"]);
}
?>
