<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

$conn = new mysqli('localhost', 'root', '', 'c2c_platform');

if ($conn->connect_error) {
    echo json_encode([
        'total_users' => 0,
        'active_listings' => 0,
        'pending_verifications' => 0,
        'recent_users' => [],
        'error' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit();
}

// Fetch Total Registered Users Safely
$totalUsers = 0;
$userCountQuery = $conn->query("SELECT COUNT(*) as total FROM users");
if ($userCountQuery) {
    $userRow = $userCountQuery->fetch_assoc();
    $totalUsers = intval($userRow['total']);
} else {
    echo json_encode(['error' => 'Users table query failed: ' . $conn->error]);
    $conn->close();
    exit();
}

// Fetch Active Market Listings Safely with explicit error tracking
$activeListings = 0;
$productCountQuery = $conn->query("SELECT COUNT(*) as total FROM products");

if ($productCountQuery !== false) {
    $productRow = $productCountQuery->fetch_assoc();
    $activeListings = intval($productRow['total']);
} else {
    // If it fails here, it will tell your dashboard EXACTLY what is wrong with the products table!
    echo json_encode([
        'error' => 'Your products table exists but the query failed! Database Error: ' . $conn->error . '. Please check if the table name is spelled exactly "products" or "product" in your database.'
    ]);
    $conn->close();
    exit();
}


$pendingVerifications = 0;

// Pull Recent Users Safely
$getUsersQuery = $conn->query("SELECT id, name, email FROM users ORDER BY id DESC LIMIT 5");
$recentUsers = [];

if ($getUsersQuery && $getUsersQuery->num_rows > 0) {
    while ($row = $getUsersQuery->fetch_assoc()) {
        $recentUsers[] = [
            'id'       => intval($row['id']),
            'username' => !empty($row['name']) ? $row['name'] : 'Unnamed User', 
            'email'    => !empty($row['email']) ? $row['email'] : 'No Email Provided',
            'status'   => 'Active'
        ];
    }
}

// Build response package if everything is successful
$outputData = [
    'total_users'           => $totalUsers,
    'active_listings'       => $activeListings,
    'pending_verifications' => $pendingVerifications,
    'recent_users'          => $recentUsers
];

echo json_encode($outputData);
$conn->close();
exit();
?>