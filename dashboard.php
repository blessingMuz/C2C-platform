<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If the user is not logged in at all, send them straight to login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: login.html?auth=required");
    exit();
}

// Read the role securely from the session state
$currentRole = strtolower($_SESSION['user_role']);

// Dispatch the user to their respective workspace
if ($currentRole === 'buyer') {
    header("Location: BuyerDash.php");
    exit();
} elseif ($currentRole === 'seller') {
    header("Location: sellerDash.html"); 
    exit();
} else {
    header("Location: index.html");
    exit();
}
?>