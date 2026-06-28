<?php
include("config/db.php");

$sellers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='seller'"));
$buyers  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='buyer'"));

header('Content-Type: application/json');
echo json_encode([
    "sellers" => (int)$sellers['total'],
    "buyers"  => (int)$buyers['total']
]);
?>