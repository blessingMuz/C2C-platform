<?php
// updateProductStatus.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// SECURITY GATEKEEPER: Only allow Operations Admins and the platform owner (Super_Admin)
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Operations_Admin', 'Super_Admin'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Access denied. Requires Operations Clearance."]);
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'c2c_platform');

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database offline."]);
    exit();
}

if (!isset($_POST['product_id']) || !isset($_POST['action'])) {
    echo json_encode(["success" => false, "error" => "Incomplete request transmission parameters."]);
    exit();
}

$productId = (int)$_POST['product_id'];
$action = strtolower(trim($_POST['action']));

if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE products SET status = 'Approved' WHERE id = ?");
} elseif ($action === 'decline') {
    $stmt = $conn->prepare("UPDATE products SET status = 'Declined' WHERE id = ?");
} else {
    echo json_encode(["success" => false, "error" => "Invalid system execution action."]);
    exit();
}

$stmt->bind_param("i", $productId);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Product status updated successfully."]);
} else {
    echo json_encode(["success" => false, "error" => "Database update failed."]);
}

$stmt->close();
$conn->close();
?>