<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Support_Admin', 'Operations_Admin', 'Super_Admin'])) {
    header("Location: AdminLogin.php"); 
    exit();
}

if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'c2c_platform'; 
} else {
    $db_host = 'sql300.infinityfree.com';
    $db_user = 'if0_42151694';
    $db_pass = 'eOQ4iKLlaOj6';
    $db_name = 'if0_42151694_c2c_platform';
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database system tracking offline.");
}

$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch active ticket data to gather the recipient user email
$ticket = null;
if ($ticket_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$simulated_email = null;
$success_msg = "";

// pipline handler
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_reply'])) {
    $admin_message = trim($_POST['admin_message'] ?? '');
    
    if (!empty($admin_message) && $ticket) {
        //  Commit reply log to central database stream
        $stmt = $conn->prepare("INSERT INTO ticket_replies (ticket_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $ticket_id, $admin_message);
        $stmt->execute();
        $stmt->close();
        
        // Change tracking status criteria to Resolved
        $conn->query("UPDATE tickets SET status = 'Resolved' WHERE id = $ticket_id");

        // executes displays simluation 
        $to_email = $ticket['email'];
        $user_name = $ticket['full_name'];
        $email_subject = "Official Helpdesk Update: Ticket #TK-" . str_pad($ticket_id, 4, '0', STR_PAD_LEFT);
        
        $email_body = "Hello " . $user_name . ",\n\n";
        $email_body .= "An administrator has posted an update regarding your support ticket:\n";
        $email_body .= "--------------------------------------------------\n";
        $email_body .= $admin_message . "\n";
        $email_body .= "--------------------------------------------------\n\n";
        $email_body .= "You can view the full transcript history on your active Help Center dashboard profile.\n\n";
        $email_body .= "Best regards,\nUbuntu Market Administration Support Team";

        // Attempt actual transmission
        $headers = "From: support@ubuntu-market.com\r\nReply-To: support@ubuntu-market.com\r\nContent-Type: text/plain; charset=UTF-8";
        @mail($to_email, $email_subject, $email_body, $headers);

        // Store variables for the screen simulation matrix view block
        $simulated_email = [
            'to' => $to_email,
            'subject' => $email_subject,
            'body' => $email_body
        ];
        
        $success_msg = "Response logged down successfully. Notification email dispatched.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ticket Replies – Ubuntu Market</title>
  <link rel="stylesheet" href="Admin-shared.css">
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700;900&family=Barlow+Condensed:wght@700;900&display=swap" rel="stylesheet"/>
  <style>
    
    body { margin: 0; padding: 0; display: flex; background: var(--main-bg); min-height: 100vh; font-family: 'Barlow', sans-serif; }
    .main-content-wrapper { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); padding: 40px; box-sizing: border-box; }
    .page-header { margin-bottom: 32px; }
    .page-title { font-family: var(--font-display); font-size: 32px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-primary); }
    .page-subtitle { font-size: 14px; color: var(--text-muted); margin-top: 4px; }
    
    .section-card { background: var(--card-bg); border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); border: 1px solid rgba(0,0,0,0.05); width: 100%; box-sizing: border-box; }
    .meta-info { background: #f3f4f6; padding: 20px; margin-bottom: 24px; font-size: 14px; border-left: 4px solid #111; border-radius: 8px; line-height: 1.6; }
    
    textarea { width: 100%; height: 160px; padding: 16px; font-family: 'Barlow', sans-serif; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box; resize: vertical; margin-bottom: 20px; }
    textarea:focus { outline: none; border-color: var(--accent-orange); }
    
    .btn-dispatch { background: #111; color: #fff; border: none; padding: 16px 24px; font-size: 14px; font-weight: 700; text-transform: uppercase; border-radius: 8px; width: 100%; cursor: pointer; letter-spacing: 0.5px; transition: background 0.2s; }
    .btn-dispatch:hover { background: var(--accent-orange, #ea580c); }
    
    .alert-box { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; padding: 14px 16px; margin-bottom: 24px; border-radius: 8px; font-weight: 600; font-size: 14px; }
    
    /*  Simulation View Block Layout */
    .simulation-container { margin-top: 40px; border: 2px dashed #ea580c; background: #fffbf7; padding: 24px; border-radius: 12px; }
    .simulation-title { color: #ea580c; font-family: 'Barlow Condensed', sans-serif; font-size: 20px; font-weight: 900; margin-top: 0; text-transform: uppercase; }
    .email-header-line { font-size: 13px; font-weight: 700; border-bottom: 1px solid #e5e7eb; padding: 8px 0; color: #374151; }
    .email-preview-body { background: #ffffff; border: 1px solid #e5e7eb; padding: 16px; font-family: monospace; font-size: 13px; white-space: pre-wrap; margin-top: 16px; color: #1f2937; border-radius: 6px; }
  </style>
</head>
<body>

  <div class="sidebar">
    <div class="sidebar-brand">
      <h1>Ubuntu Market</h1>
      <p>Admin Panel</p>
    </div>
    
    <div class="admin-badge">
      <div class="admin-avatar"><?= strtoupper(substr(htmlspecialchars($_SESSION['user_name']), 0, 1)) ?></div>
      <div class="admin-info">
        <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <span class="admin-role"><?= str_replace('_', ' ', htmlspecialchars($_SESSION['user_role'])) ?></span>
      </div>
    </div>

    <div class="nav-section-label">Main</div>
    <nav>
      <a href="AdminDash.php">Dashboard</a>
      <a href="ManageUsers.php">Users</a>
      <a href="ManageProducts.php">Products</a>
      <a href="ManageOrders.php">Orders</a>
      <div class="nav-section-label">Account</div>
      <a href="Reports.php">Reports</a>
      <a href="AdminReplyEmail.php" class="active">Ticket Replies</a>
      <div class="sidebar-spacer"></div>
      <a href="AdminLogout.php" style="color: var(--accent-red);">Logout</a>
    </nav>
  </div>

  <div class="main-content-wrapper">
    
    <div class="page-header">
      <div>
        <h2 class="page-title">Ticket Dispute Desk</h2>
        <p class="page-subtitle">Review core context configurations and dispatch formal resolution update sequences.</p>
      </div>
    </div>

    <div class="section-card">
        <?php if (!empty($success_msg)): ?>
            <div class="alert-box"><?= $success_msg ?></div>
        <?php endif; ?>

        <?php if ($ticket): ?>
            <div class="meta-info">
                <strong>Ticket Reference ID:</strong> #TK-<?= str_pad($ticket['id'], 4, '0', STR_PAD_LEFT) ?><br>
                <strong>Registered Account Holder:</strong> <?= htmlspecialchars($ticket['full_name']) ?> (<?= htmlspecialchars($ticket['email']) ?>)<br>
                <strong>Dispute Matter Title:</strong> <?= htmlspecialchars($ticket['subject']) ?><br>
                <strong>Chronological Overview Context:</strong> "<?= htmlspecialchars($ticket['message']) ?>"
            </div>

            <form method="POST">
                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 8px; color: var(--text-muted, #6b7280);">Official Resolution Context Response</label>
                <textarea name="admin_message" required placeholder="Type out your formal resolution statement context..."></textarea>
                <button type="submit" name="send_reply" class="btn-dispatch">Post Response & Dispatch Simulated Email</button>
            </form>
        <?php else: ?>
            <p style="color: #dc2626; font-weight: bold; background: #fee2e2; padding: 15px; border-radius: 8px;">No active ticket context selection ID parameters were parsed from your navigation view layout selection.</p>
        <?php endif; ?>

        <?php if ($simulated_email): ?>
            <div class="simulation-container">
                <div class="simulation-title"> Outbound Email Dispatch Log Simulator</div>
                <div class="email-header-line"><strong>To:</strong> <?= htmlspecialchars($simulated_email['to']) ?></div>
                <div class="email-header-line"><strong>From:</strong> support@ubuntu-market.com</div>
                <div class="email-header-line"><strong>Subject:</strong> <?= htmlspecialchars($simulated_email['subject']) ?></div>
                <div class="email-preview-body"><?= htmlspecialchars($simulated_email['body']) ?></div>
            </div>
        <?php endif; ?>
    </div>

  </div>

</body>
</html>