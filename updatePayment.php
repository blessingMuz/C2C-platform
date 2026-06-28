<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status" => "login"]);
    exit();
}

$order_id   = (int)$_POST['order_id'];
$payment_id = mysqli_real_escape_string($conn, $_POST['payment_id']);
$status     = 'paid';

mysqli_query($conn,
    "UPDATE checkout_orders 
     SET payment_status='$status', payment_method='paypal'
     WHERE id=$order_id AND user_id=" . $_SESSION['user_id']);

echo json_encode(["status" => "success"]);
?>
