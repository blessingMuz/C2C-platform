<?php
// config/db.php - CENTRAL DATABASE CONNECTION LAYER
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// AUTOMATIC ENVIRONMENT ROUTING (Local vs InfinityFree Production)
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
    // Your local development credentials
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'c2c_platform'; 
} else {
    // Your live InfinityFree production credentials
    $db_host = 'sql300.infinityfree.com';
    $db_user = 'if0_42151694';
    $db_pass = 'eOQ4iKLlaOj6';
    $db_name = 'if0_42151694_c2c_platform';
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Fallback logic to protect the JSON API stream if the database drops
if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode(["error" => "Database link down: " . $conn->connect_error]));
}
?>