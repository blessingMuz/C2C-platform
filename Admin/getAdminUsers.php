<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
    // Local XAMPP Parameters
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'c2c_platform'; 
} else {
    // Live InfinityFree Parameters
    $db_host = 'sql300.infinityfree.com';
    $db_user = 'if0_42151694';
    $db_pass = 'eOQ4iKLlaOj6'; 
    $db_name = 'if0_42151694_c2c_platform';
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database link failure: " . $conn->connect_error]);
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {
    // Escapes search parameters securely to eliminate SQL injection openings
    $escapedSearch = $conn->real_escape_string($search);
    $query = "SELECT id, name, email, role FROM users 
              WHERE name LIKE '%$escapedSearch%' OR email LIKE '%$escapedSearch%' 
              ORDER BY id DESC";
} else {
    $query = "SELECT id, name, email, role FROM users ORDER BY id DESC";
}

$result = $conn->query($query);
$responseUsers = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
    
        $displayStatus = isset($row['status']) ? $row['status'] : ($row['role'] ?? 'Active');

        $responseUsers[] = [
            "raw_id" => (int)$row['id'],
            "id"     => "#" . str_pad($row['id'], 3, '0', STR_PAD_LEFT), 
            "name"   => htmlspecialchars($row['name']),
            "email"  => htmlspecialchars($row['email']),
            "status" => ucfirst(strtolower($displayStatus)) 
        ];
    }
    echo json_encode(["success" => true, "users" => $responseUsers]);
} else {
    echo json_encode(["success" => false, "error" => "Query failure: " . $conn->error]);
}

$conn->close();
?>