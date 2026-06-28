<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Restrict access to authorized roles only
if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], [ 'Support_Admin', 'Operations_Admin', 'Super_Admin'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthenticated administrative session context."]);
    exit();
}

if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
    // Local Setup
    $db_host = 'localhost';
    $db_name = 'c2c_platform';
    $db_user = 'root';
    $db_pass = '';
} else {
    // Live InfinityFree Setup
    $db_host = 'sql300.infinityfree.com';
    $db_user = 'if0_42151694';
    $db_pass = 'eOQ4iKLlaOj6'; 
    $db_name = 'if0_42151694_c2c_platform';
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

     //fecths platform stats
    $totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalOrders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    
    // Sums revenue up safely from uncancelled transaction records
    $totalRevenue  = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();

    $recentUsersStmt = $pdo->query("SELECT id, name, email, role FROM users ORDER BY id DESC LIMIT 6");
    $recentUsers = $recentUsersStmt->fetchAll();

    echo json_encode([
        "success"        => true,
        "total_users"    => number_format($totalUsers),
        "total_products" => number_format($totalProducts),
        "total_orders"   => number_format($totalOrders),
        "revenue"        => "R" . number_format($totalRevenue, 0, ',', ' '),
        "recent_users"   => $recentUsers
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Telemetry gathering failure: " . $e->getMessage()]);
}
?>