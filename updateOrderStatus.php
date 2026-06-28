<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"login"]);
    exit();
}

$order_id = (int)$_POST['order_id'];
$status   = mysqli_real_escape_string($conn, $_POST['status']);
$allowed  = ['pending','shipped','delivered','cancelled'];

if(!in_array($status, $allowed)){
    echo json_encode(["status"=>"error"]);
    exit();
}

if(mysqli_query($conn, "UPDATE orders SET status='$status' WHERE id=$order_id")){
    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error"]);
}
?>
