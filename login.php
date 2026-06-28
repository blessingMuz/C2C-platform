<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("config/db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql    = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // Verify hashed password security constraint
        if (password_verify($password, $user['password'])) {

            // Save essential user variables to the session state (Forcing lowercase here)
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = strtolower($user['role']); // Converts "Buyer" to "buyer" automatically

            // Case-insensitive dispatch router
            $currentRole = strtolower($user['role']);

            if ($currentRole == 'seller') {
                header("Location: sellerDash.html");
                exit();
            } elseif ($currentRole == 'buyer') {
                header("Location: BuyerDash.php");
                exit();
            } else {
                header("Location: index.html");
                exit();
            }

        } else {
            header("Location: login.html?error=Incorrect+password");
            exit();
        }
    } else {
        header("Location: login.html?error=Email+not+found");
        exit();
    }
}
?>