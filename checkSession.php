<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "success",
        "user_id" => $_SESSION['user_id'],
        "user_name" => $_SESSION['user_name'] ?? 'Buyer'
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in"
    ]);
}
exit();
?>