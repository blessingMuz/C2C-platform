<?php
header('Content-Type: application/json');


if (file_exists("config/db.php")) {
    include("config/db.php");
} else {
    echo json_encode(["error" => "Database configuration file not found inside config/ folder."]);
    exit;
}

if (!isset($conn)) {
    echo json_encode(["error" => "Database connection variable is missing."]);
    exit;
}

$search   = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';

$sql = "SELECT products.*, users.name as seller_name 
        FROM products 
        LEFT JOIN users ON products.seller_id = users.id 
        WHERE 1=1";

if ($search != '') {
    $sql .= " AND products.name LIKE '%$search%'";
}

if ($category != '') {
    $sql .= " AND products.category = '$category'";
}

$sql .= " ORDER BY products.created_at DESC";

$products = [];
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
} else {
    echo json_encode(["error" => "SQL Error: " . mysqli_error($conn)]);
    exit;
}

echo json_encode($products);
?>