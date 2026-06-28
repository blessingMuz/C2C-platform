<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// checks if an admin is already logged in, bypass login and go straight to the dashboard
if (isset($_SESSION['user_logged_in']) && isset($_SESSION['user_role'])) {
    header("Location: AdminDash.php");
    exit();
}

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
        $hostname = 'localhost';
        $username = 'root';
        $password = '';
        $database = 'c2c_platform';
    } else {
        $hostname = 'sql300.infinityfree.com';
        $username = 'if0_42151694';
        $password = 'eOQ4iKLlaOj6'; 
        $database = 'if0_42151694_c2c_platform';
    }

    $conn = new mysqli($hostname, $username, $password, $database);

    if ($conn->connect_error) {
        $error_message = "Database link failure.";
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';

        if (empty($email) || empty($password)) {
            $error_message = "Please complete all fields.";
        } else {
          
            $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $error_message = "Invalid email or password access credentials.";
            } else {
                $user = $result->fetch_assoc();

                $passwordMatches = ($password === $user['password'] || password_verify($password, $user['password']));

                if (!$passwordMatches) {
                    $error_message = "Invalid email or password access credentials.";
                } else {
                    $allowedRoles = ['Support_Admin', 'Operations_Admin', 'Super_Admin'];
                    if (!in_array($user['role'], $allowedRoles)) {
                        $error_message = "Access denied. Standard account profiles restricted.";
                    } 
                    // FIXED: This validation condition works perfectly now that 'status' is pulled
                    elseif (isset($user['status']) && strtoupper($user['status']) === 'BLOCKED') {
                        $error_message = "This administrative account has been suspended.";
                    } else {
                        $_SESSION['user_logged_in'] = true;
                        $_SESSION['user_id']        = $user['id'];
                        $_SESSION['user_name']      = $user['name'];
                        $_SESSION['user_role']      = $user['role'];

                        header("Location: AdminDash.php");
                        exit();
                    }
                }
            }
            $stmt->close();
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Login | Ubuntu Market</title>
  <link rel="stylesheet" href="Admin-shared.css">
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700;900&family=Barlow+Condensed:wght@700;900&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --font: 'Barlow', sans-serif;
      --font-display: 'Barlow Condensed', sans-serif;
      --Orange: #f89f0f;
    }

    body {
      font-family: var(--font);
      min-height: 100vh;
      display: flex;
      background: #0a0a0a;
      position: relative;
      overflow-x: hidden;
    }

    body::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
      background-size: 40px 40px;
      z-index: 0;
    }

    .left-panel {
      width: 55%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 60px 80px;
      position: relative;
      z-index: 1;
    }
    .brand { margin-bottom: 60px; }
    .brand h1 {
      font-family: var(--font-display);
      font-size: 28px;
      font-weight: 900;
      color: #fff;
      letter-spacing: 2px;
      text-transform: uppercase;
    }
    .brand p { font-size: 11px; font-weight: 600; letter-spacing: 3px; text-transform: uppercase; color: #555; margin-top: 4px; }

    .hero-text h2 {
      font-family: var(--font-display);
      font-size: 72px;
      font-weight: 900;
      line-height: 0.95;
      text-transform: uppercase;
      color: #fff;
      letter-spacing: -1px;
    }
    .hero-text h2 span { color: var(--accent-blue); }
    .hero-text p { font-size: 15px; color: #666; margin-top: 20px; max-width: 380px; line-height: 1.6; }

    .feature-list { display: flex; flex-direction: column; gap: 14px; margin-top: 40px; }
    .feature-item { display: flex; align-items: center; gap: 12px; }
    .feature-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent-blue); flex-shrink: 0; }
    .feature-item span { font-size: 13px; color: #777; font-weight: 500; }

    .right-panel {
      width: 45%;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px;
      position: relative;
      z-index: 1;
    }

    .login-card {
      background: #ffffff;
      border-radius: 24px;
      padding: 44px 40px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 0 0 1px rgba(255,255,255,0.05), 0 40px 80px rgba(0,0,0,0.4);
    }

    .login-card-header { margin-bottom: 32px; }
    .login-card-header h3 {
      font-family: var(--font-display);
      font-size: 28px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #111;
    }
    .login-card-header p { font-size: 13px; color: #888; margin-top: 4px; }

    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #555; margin-bottom: 8px; }
    .form-group input {
      width: 100%;
      padding: 13px 16px;
      border: 1.5px solid #e5e7eb;
      border-radius: 12px;
      font-family: var(--font);
      font-size: 14px;
      color: #111;
      outline: none;
      transition: border-color 0.15s;
      background: #fafafa;
    }
    .form-group input:focus { border-color: #111; background: #fff; }

    .login-btn {
      width: 100%;
      padding: 14px;
      background: #111;
      color: #fff;
      border: none;
      border-radius: 12px;
      font-family: var(--font);
      font-size: 14px;
      font-weight: 700;
      letter-spacing: 0.5px;
      cursor: pointer;
      margin-top: 8px;
      transition: background 0.15s, transform 0.1s;
    }
    .login-btn:hover { background: #222; transform: translateY(-1px); }

    .error-banner {
      background: #fee2e2;
      border-left: 4px solid #dc2626;
      color: #dc2626;
      padding: 12px;
      font-size: 13px;
      font-weight: 600;
      border-radius: 8px;
      margin-bottom: 20px;
    }

    .form-links { display: flex; flex-direction: column; align-items: center; gap: 10px; margin-top: 20px; }
    .form-links a { font-size: 13px; color: #888; text-decoration: none; font-weight: 500; }
    .form-links a:hover { color: #111; }

    .divider { width: 100%; height: 1px; background: #f0f0f0; margin: 20px 0; }
  </style>
</head>
<body>

  <div class="left-panel">
    <div class="brand">
      <h1>Ubuntu Market</h1>
      <p>Admin Portal</p>
    </div>
    <div class="hero-text">
      <h2>Admin<br/><span>Control</span><br/>Centre</h2>
      <p>Secure access to manage users, products, orders, and platform analytics in one place.</p>
    </div>
    <div class="feature-list">
      <div class="feature-item"><div class="feature-dot"></div><span>Manage all users, sellers and buyers</span></div>
      <div class="feature-item"><div class="feature-dot"></div><span>Review and approve product listings</span></div>
      <div class="feature-item"><div class="feature-dot"></div><span>Monitor orders and platform revenue</span></div>
      <div class="feature-item"><div class="feature-dot"></div><span>Access real-time reports & analytics</span></div>
    </div>
  </div>

  <div class="right-panel">
    <div class="login-card">
      <div class="login-card-header">
        <h3>Admin Login</h3>
        <p>Secure access for platform administrators</p>
      </div>

      <?php if (!empty($error_message)): ?>
        <div class="error-banner">
          <?= htmlspecialchars($error_message) ?>
        </div>
      <?php endif; ?>

      <form action="AdminLogin.php" method="POST">
        <div class="form-group">
          <label>Admin Email</label>
          <input type="email" name="email" placeholder="admin@ubuntumarket.co.za" required/>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="••••••••" required/>
        </div>
        <button type="submit" class="login-btn">Login to Dashboard</button>
      </form>

      <div class="divider"></div>
      <div class="form-links">
        <a href="forgetpass.html">Forgot Password?</a>
        <a href="index.html"> Back to Home</a>
      </div>
    </div>
  </div>

</body>
</html>