<?php
header('Content-Type: application/json');

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'c2c_platform_db';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database link failure"]);
    exit();
}

// Validate that mandatory post parameters are present
if (!isset($_POST['user_id']) || !isset($_POST['action'])) {
    echo json_encode(["success" => false, "error" => "Incomplete request transmission variables"]);
    exit();
}

$userId = (int)$_POST['user_id'];
$action = strtolower(trim($_POST['action']));

// Process requests safely via SQL prepared parameter streams
if ($action === 'block') {
    $stmt = $conn->prepare("UPDATE users SET status = 'Blocked' WHERE id = ?");
    $stmt->bind_param("i", $userId);
} elseif ($action === 'unblock') {
    $stmt = $conn->prepare("UPDATE users SET status = 'Active' WHERE id = ?");
    $stmt->bind_param("i", $userId);
} elseif ($action === 'delete') {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
} else {
    echo json_encode(["success" => false, "error" => "Invalid system execution action intent"]);
    exit();
}

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Database record structural mutation failed"]);
}

$stmt->close();
$conn->close();
?>