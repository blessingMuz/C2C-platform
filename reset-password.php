<?php
session_start();
include('config/db.php'); // Ensure this matches your path to your database configuration helper

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Grab form submissions
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    //  Double check that passwords match — Redirecting back to the FORM page
    if ($new_password !== $confirm_password) {
        header("Location: resetpassw.php?error=" . urlencode("Passwords do not match."));
        exit();
    }

    //  Enforce security rules — Redirecting back to the FORM page
    $pattern = '/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\\[\\]{};\':"\\\\\\\\|,.<>\\/?~`]).{8,15}$/';
    if (!preg_match($pattern, $new_password)) {
        header("Location: resetpassw.php?error=" . urlencode("Password must be 8-15 characters with 1 uppercase, 1 number, and 1 symbol."));
        exit();
    }

    // Validate identity context — Redirecting back to the FORM page
    if (!isset($_SESSION['reset_email'])) {
        header("Location: resetpassw.php?error=" . urlencode("Session expired or invalid request. Please request a new link."));
        exit();
    }
    
    $email = $_SESSION['reset_email'];

    //  Securely hash the new password using Bcrypt
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Update the database securely using a prepared statement
    $query = "UPDATE `users` SET `password` = ?, `reset_token` = NULL, `token_expires` = NULL WHERE `email` = ?";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            // Password changed successfully! Clear the temporary session validation parameters
            unset($_SESSION['reset_email']);
            
            // Send them home cleanly with a success parameter to show on login form
            header("Location: login.html?success=" . urlencode("Password updated successfully! Please log in."));
            exit();
        } else {
            header("Location: resetpassw.php?error=" . urlencode("Failed to update database. Try again."));
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}
?>