<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"login"]);
    exit();
}

$order_id = (int)$_POST['order_id'];
$user_id  = $_SESSION['user_id'];

// Only cancel pending orders
$check = mysqli_query($conn,
    "SELECT id FROM orders WHERE id=$order_id AND user_id=$user_id AND status='pending'");

if(mysqli_num_rows($check) === 0){
    echo json_encode(["status"=>"error","message"=>"Cannot cancel this order"]);
    exit();
}

if(mysqli_query($conn, "UPDATE orders SET status='cancelled' WHERE id=$order_id AND user_id=$user_id")){
    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error"]);
}
?>
