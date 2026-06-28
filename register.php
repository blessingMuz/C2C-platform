<?php
include("config/db.php");

if (isset($_POST['register'])) {

    $name     = mysqli_real_escape_string($conn, $_POST['name']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $phone    = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    
    // Automatically convert lowercase "buyer/seller" to capitalized "Buyer/Seller"
    $raw_role = mysqli_real_escape_string($conn, $_POST['role']);
    $role     = ucfirst($raw_role); 

    //  Password Matching Check
    if ($password !== $confirm) {
        die("<style>body{font-family:sans-serif;padding:40px;background:#fff5f5;color:#c53030;}</style>
             <h2>Registration Stopped</h2>
             <p><strong>Reason:</strong> Passwords do not match.</p>
             <a href='register.html'>Go back and try again</a>");
    }

    // Security Rules Checks (Length, Number, and Special Character)
    $hasNumber = preg_match('/[0-9]/', $password);
    
   //Clean trick that matches any special character without syntax confusion
    $hasSymbol = preg_match('/[^a-zA-Z0-9]/', $password); 
    
    $length    = strlen($password);

    if ($length < 8 || $length > 15 || !$hasNumber || !$hasSymbol) {
        die("<style>body{font-family:sans-serif;padding:40px;background:#fff5f5;color:#c53030;}</style>
             <h2>Registration Stopped</h2>
             <p><strong>Reason:</strong> Password does not meet security rules.</p>
             <p>It must be between 8 and 15 characters long, contain at least one number, and at least one special character.</p>
             <a href='register.html'>Go back and try again</a>");
    }

    // Encrypt the password 
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Duplicate Email Verification Check
    $checkEmail = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $checkEmail);

    if (mysqli_num_rows($result) > 0) {
        die("<style>body{font-family:sans-serif;padding:40px;background:#fff5f5;color:#c53030;}</style>
             <h2>Registration Stopped</h2>
             <p><strong>Reason:</strong> The email address <u>$email</u> is already registered.</p>
             <a href='register.html'>Go back and try again</a>");
    }

    //Database Insertion Attempt
    $sql = "INSERT INTO users(name, email, phone, password, role)
            VALUES('$name', '$email', '$phone', '$hashed_password', '$role')";

    if (mysqli_query($conn, $sql)) {
        // Success: Trigger browser alert box then redirect to login screen
        echo "<script>
                alert('Registration successful! Click OK to go to the login page.');
                window.location.href = 'login.html?registered=1';
              </script>";
        exit();
    } else {
        // If MySQL blocks it, dump the explicit technical error
        die("<style>body{font-family:sans-serif;padding:40px;background:#fff5f5;color:#c53030;}</style>
             <h2>MySQL Database Error Occurred</h2>
             <p><strong>The query failed with this message:</strong></p>
             <pre style='background:#fee2e2;padding:15px;border-radius:6px;border:1px solid #fca5a5;color:#991b1b;font-size:14px;'>Code: " . mysqli_error($conn) . "</pre>
             <p>Check your table columns or types based on the text above.</p>
             <a href='register.html'>Go back</a>");
    }
} else {
    header("Location: register.html");
    exit();
}
?>