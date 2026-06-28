<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"login"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$name    = mysqli_real_escape_string($conn, $_POST['name']);
$phone   = mysqli_real_escape_string($conn, $_POST['phone']);
$pass    = $_POST['password'];

if(!empty($pass)){
    $hashed = password_hash($pass, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET name='$name', phone='$phone', password='$hashed' WHERE id=$user_id";
} else {
    $sql = "UPDATE users SET name='$name', phone='$phone' WHERE id=$user_id";
}

if(mysqli_query($conn, $sql)){
    $_SESSION['user_name'] = $name;
    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error"]);
}
?>
