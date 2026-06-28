<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status" => "login"]);
    exit();
}

$cart_id = (int)$_POST['cart_id'];
$user_id = $_SESSION['user_id'];

mysqli_query($conn, "DELETE FROM cart WHERE id=$cart_id AND user_id=$user_id");
echo json_encode(["status" => "success"]);
?>