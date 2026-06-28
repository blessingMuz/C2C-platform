<?php
session_start();
// Verify this path points correctly to your database connection file
include('config/db.php'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if user exists
    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        // Generate a highly secure unique token
        $token = bin2hex(random_bytes(32));
        // Set expiry date-time bound for 1 hour from now
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Save token to user profile row
        $update = "UPDATE users SET reset_token = ?, token_expires = ? WHERE email = ?";
        $up_stmt = mysqli_prepare($conn, $update);
        mysqli_stmt_bind_param($up_stmt, "sss", $token, $expires, $email);
        mysqli_stmt_execute($up_stmt);

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $subFolder = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $resetLink = $protocol . $_SERVER['HTTP_HOST'] . $subFolder . "/resetpassw.php?token=" . $token;

        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Simulation Mode</title>
            <link rel="stylesheet" href="style.css">
        </head>
        <body>
            <div style="font-family:sans-serif; max-width:500px; margin:40px auto; padding:20px; border:1px solid #ccc; border-radius:8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); background: #ffffff; text-align: center;">
                <h2 style="color:#16a34a; margin-top:0;">Simulation Mode (Email Sent)</h2>
                <p>In production, an email goes to <strong><?php echo htmlspecialchars($email); ?></strong>.</p>
                <p>Click the secure live link below to simulate opening the email:</p>
                <a href="<?php echo $resetLink; ?>" style="display:inline-block; padding:12px 24px; background:#16a34a; color:#fff; text-decoration:none; border-radius:6px; font-weight:600; margin-top:10px;">Reset My Password</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        header("Location: forgetpass.html?error=" . urlencode("No account found with that email address."));
        exit;
    }
}
?>