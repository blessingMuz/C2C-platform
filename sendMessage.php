<?php
session_start();
include("config/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

$sender_id   = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$product_id  = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$message     = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($receiver_id === 0 || $message === '') {
    echo json_encode(["status" => "error", "message" => "Missing required data"]);
    exit();
}

// Security safeguard against SQL Injection
$message = mysqli_real_escape_string($conn, $message);

$sql = "INSERT INTO messages (sender_id, receiver_id, product_id, message) 
        VALUES ($sender_id, $receiver_id, $product_id, '$message')";

if (mysqli_query($conn, $sql)) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
}
exit();
?>