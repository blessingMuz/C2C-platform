<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"login"]);
    exit();
}

$seller_id = $_SESSION['user_id'];

$products = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM products WHERE seller_id=$seller_id"));

$orders = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM orders 
     JOIN products ON orders.product_id = products.id
     WHERE products.seller_id=$seller_id"));

$revenue = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(orders.total) as total FROM orders 
     JOIN products ON orders.product_id = products.id
     WHERE products.seller_id=$seller_id AND orders.status='delivered'"));

$pending = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM orders 
     JOIN products ON orders.product_id = products.id
     WHERE products.seller_id=$seller_id AND orders.status='pending'"));

$rating = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT AVG(reviews.rating) as avg FROM reviews
     JOIN products ON reviews.product_id = products.id
     WHERE products.seller_id=$seller_id"));

header('Content-Type: application/json');
echo json_encode([
    "products" => (int)$products['total'],
    "sales"   => (int)$orders['total'],
    "revenue"  => round((float)($revenue['total'] ?? 0), 2),
    "pending"  => (int)$pending['total'],
    "rating"   => $rating['avg'] ? round($rating['avg'], 1) : null
]);
?>