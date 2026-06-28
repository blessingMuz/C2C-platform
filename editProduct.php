<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"login"]);
    exit();
}

$id          = (int)$_POST['id'];
$name        = mysqli_real_escape_string($conn, $_POST['name']);
$price       = (float)$_POST['price'];
$description = mysqli_real_escape_string($conn, $_POST['description']);
$category    = mysqli_real_escape_string($conn, $_POST['category']);
$seller_id   = $_SESSION['user_id'];

$sql = "UPDATE products SET name='$name', price=$price,
        description='$description', category='$category'
        WHERE id=$id AND seller_id=$seller_id";

if(mysqli_query($conn, $sql)){
    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error"]);
}
?>
