<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"login"]);
    exit();
}

$user_id = $_SESSION['user_id'];

$orders = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM orders WHERE user_id=$user_id"));

$spent = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(total) as total FROM orders WHERE user_id=$user_id AND status='delivered'"));

$cart = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(quantity) as total FROM cart WHERE user_id=$user_id"));

$pending = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM orders WHERE user_id=$user_id AND status='pending'"));

$joined = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT created_at FROM users WHERE id=$user_id"));

header('Content-Type: application/json');
echo json_encode([
    "orders"  => (int)$orders['total'],
    "spent"   => round((float)($spent['total']??0), 2),
    "cart"    => (int)($cart['total']??0),
    "pending" => (int)$pending['total'],
    "joined"  => $joined['created_at']??null
]);
?>
