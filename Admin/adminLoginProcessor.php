<?php
//this is the engine
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect anyone trying to type this filename into the URL bar without filling out the form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: AdminLogin.html");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
        // Local XAMPP Setup
        $hostname = 'localhost';
        $username = 'root';
        $password = '';
        $database = 'c2c_platform';
    } else {
        // Live InfinityFree Setup
        $hostname = 'sql300.infinityfree.com';
        $username = 'if0_42151694';
        $password = 'eOQ4iKLlaOj6'; 
        $database = 'if0_42151694_c2c_platform';
    }
}
// Database Connection
$conn = new mysqli($hostname, $username, $password, $database);

if ($conn->connect_error) {
    header("Location: AdminLogin.html?error=" . urlencode("Database link failure."));
    exit();
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($email) || empty($password)) {
    header("Location: AdminLogin.html?error=" . urlencode("Please complete all fields."));
    exit();
}

$stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: AdminLogin.html?error=" . urlencode("Invalid email or password access credentials."));
    exit();
}

$user = $result->fetch_assoc();

// Password Check 
$passwordMatches = ($password === $user['password'] || password_verify($password, $user['password']));

if (!$passwordMatches) {
    header("Location: AdminLogin.html?error=" . urlencode("Invalid email or password access credentials."));
    exit();
}

// Security Check authorized staff roles
$allowedRoles = [ 'Support_Admin', 'Operations_Admin', 'Super_Admin'];
if (!in_array($user['role'], $allowedRoles)) {
    header("Location: AdminLogin.html?error=" . urlencode("Access denied. Standard account profiles restricted."));
    exit();
}

// Guard Checks Suspended admin profiles
if (isset($user['status']) && strtoupper($user['status']) === 'BLOCKED') {
    header("Location: AdminLogin.html?error=" . urlencode("This administrative account has been suspended."));
    exit();
}

//  Saves data safely into server session ecosystem
$_SESSION['user_logged_in'] = true;
$_SESSION['user_id']        = $user['id'];
$_SESSION['user_name']      = $user['name'];
$_SESSION['user_role']      = $user['role'];

// Move user seamlessly forward into dashboard interface
header("Location: AdminDash.php");
$stmt->close();
$conn->close();
exit();
?>