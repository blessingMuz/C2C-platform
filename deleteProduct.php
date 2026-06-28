<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"login"]);
    exit();
}

$id        = (int)$_POST['id'];
$seller_id = $_SESSION['user_id'];

if(mysqli_query($conn, "DELETE FROM products WHERE id=$id AND seller_id=$seller_id")){
    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error"]);
}
?>
