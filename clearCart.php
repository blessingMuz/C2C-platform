<?php
// clearCart
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status" => "login"]);
    exit();
}

mysqli_query($conn, "DELETE FROM cart WHERE user_id = " . $_SESSION['user_id']);
echo json_encode(["status" => "success"]);
?>
