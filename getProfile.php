<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"login"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$result  = mysqli_query($conn, "SELECT id,name,email,phone,role,created_at FROM users WHERE id=$user_id");
$user    = mysqli_fetch_assoc($result);

header('Content-Type: application/json');
echo json_encode($user);
?>
