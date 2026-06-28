<?php
session_start();
include('config/db.php');

$isValid = false;
$errorMessage = "";

if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    $currentTime = date("Y-m-d H:i:s");

    // Check if token exists and hasn't expired yet
    $query = "SELECT email FROM users WHERE reset_token = ? AND token_expires > ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $token, $currentTime);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $isValid = true;
        // Seed the session parameter your reset-password.php requires!
        $_SESSION['reset_email'] = $row['email'];
    } else {
        $errorMessage = "This password reset token is invalid or has expired.";
    }
} else {
    $errorMessage = "No secret access verification token provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password | Ubuntu Market</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <nav class="navbar">
    <h1 class="logo">Ubuntu Market</h1>
    <ul class="nav-links">
      <li><a href="productPage.html">Home</a></li>
      <li><a href="login.html">Login</a></li>
    </ul>
  </nav>
</header>

<main>
<div class="form-container">
  <h2>Reset Password</h2>

  <?php if ($isValid): ?>
    <form action="reset-password.php" method="POST">
      <input type="password" name="new_password" placeholder="New Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      <button type="submit">Reset Password</button>
    </form>
  <?php else: ?>
    <div style="background:#fee2e2; color:#dc2626; padding:15px; border-radius:8px; text-align:center; margin-bottom:15px; font-weight:600;">
        <?php echo $errorMessage; ?>
    </div>
    <p><a href="forgetpass.html" style="color:#000; font-weight:700;">Request a new link</a></p>
  <?php endif; ?>
</div>
</main>

<footer>
  <p>&copy; 2026 Ubuntu Market</p>
</footer>

<script>
  const params = new URLSearchParams(window.location.search);
  if (params.get('error')) { alert(params.get('error')); }
</script>
</body>
</html>