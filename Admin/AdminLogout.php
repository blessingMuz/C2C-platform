<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clears all session variables in server memory
$_SESSION = array();

// Clears the session cookie from the user's web browser completely
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy the session on the host side
session_destroy();

// Safely redirect back to the login page
header("Location: AdminLogin.php");
exit();
?>