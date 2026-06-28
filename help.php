<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    die("Database connection down across operational vectors.");
}

$tableSetupQuery = "CREATE TABLE IF NOT EXISTS `tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `order_id` INT(11) NULL DEFAULT NULL,
    `message` TEXT NOT NULL,
    `urgency` VARCHAR(20) NOT NULL DEFAULT 'Low',
    `status` VARCHAR(50) NOT NULL DEFAULT 'Open',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
$conn->query($tableSetupQuery);

$isLoggedIn = isset($_SESSION['user_id']); // Updated to match your actual system login key

$prefilledEmail = '';
$prefilledName = '';

// If logged in via user_id, fetch their email and name directly from the database dynamically
if ($isLoggedIn) {
    $user_id = intval($_SESSION['user_id']);
    // Check users table first
    $userQuery = $conn->query("SELECT name, email FROM users WHERE id = $user_id LIMIT 1");
    if ($userQuery && $userQuery->num_rows > 0) {
        $userRow = $userQuery->fetch_assoc();
        $prefilledEmail = trim($userRow['email']);
        $prefilledName = trim($userRow['name']);
    } else {
        // check for sellers table
        $sellerQuery = $conn->query("SELECT name, email FROM sellers WHERE id = $user_id LIMIT 1");
        if ($sellerQuery && $sellerQuery->num_rows > 0) {
            $sellerRow = $sellerQuery->fetch_assoc();
            $prefilledEmail = trim($sellerRow['email']);
            $prefilledName = trim($sellerRow['name']);
        }
    }
}

//  if database didn't have it
if (empty($prefilledEmail)) {
    if (!empty($_SESSION['user_email'])) { $prefilledEmail = trim($_SESSION['user_email']); }
    elseif (!empty($_SESSION['email'])) { $prefilledEmail = trim($_SESSION['email']); }
}
if (empty($prefilledName)) {
    if (!empty($_SESSION['user_name'])) { $prefilledName = trim($_SESSION['user_name']); }
    elseif (!empty($_SESSION['name'])) { $prefilledName = trim($_SESSION['name']); }
    elseif (!empty($_SESSION['username'])) { $prefilledName = trim($_SESSION['username']); }
}

$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_ticket'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? $prefilledEmail);
    $subject   = trim($_POST['subject'] ?? '');
    $order_id  = !empty($_POST['order_id']) ? intval($_POST['order_id']) : null;
    $message   = trim($_POST['message'] ?? '');
    $urgency   = trim($_POST['urgency'] ?? 'Low');

    if (empty($full_name) || empty($email) || empty($subject) || empty($message)) {
        $error_msg = "Please ensure all mandatory context standard inputs are filled.";
    } else {
        $stmt = $conn->prepare("INSERT INTO tickets (full_name, email, subject, order_id, message, urgency, status) VALUES (?, ?, ?, ?, ?, ?, 'Open')");
        $stmt->bind_param("sssiss", $full_name, $email, $subject, $order_id, $message, $urgency);
        
        if ($stmt->execute()) {
            $success_msg = "Your support escalation reference has been logged. Our administration queue will review this shortly.";
            $_SESSION['last_ticket_email'] = $email;
        } else {
            $error_msg = "Critical execution error mapping input variables down to the core engine datastore.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Help Center & Order Disputes | Ubuntu Market</title>
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700;900&family=Barlow+Condensed:wght@700;900&display=swap" rel="stylesheet"/>
  <style>
    body { font-family: 'Barlow', sans-serif; margin: 0; background-color: #fcfcfc; color: #111; }
    
    .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #111; color: #fff; padding: 20px 40px; }
    .navbar .logo { font-family: 'Barlow Condensed', sans-serif; font-size: 24px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
    .nav-links { list-style: none; display: flex; gap: 30px; margin: 0; padding: 0; }
    .nav-links a { color: #fff; text-decoration: none; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    .nav-links a:hover { color: #ea580c; }

    .hero-section { padding: 40px 40px; }
    .hero-section h1 { font-family: 'Barlow Condensed', sans-serif; font-size: 40px; font-weight: 900; text-transform: uppercase; margin: 0; }
    .hero-section p { font-size: 16px; color: #666; margin-top: 5px; }

    .main-container { max-width: 1200px; margin: 0 auto; padding: 0 40px 40px 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
    
    .help-panel { background: #fff; padding: 30px; border: 1px solid #eee; border-radius: 4px; }
    .full-width-panel { grid-column: 1 / -1; }
    
    .help-panel h2 { font-family: 'Barlow Condensed', sans-serif; font-size: 20px; font-weight: 900; text-transform: uppercase; margin-top: 0; margin-bottom: 25px; border-bottom: 2px solid #111; padding-bottom: 8px; display: inline-block; }
    
    .faq-item h4 { font-family: 'Barlow', sans-serif; font-size: 15px; font-weight: 700; margin: 0 0 5px 0; }
    .faq-item p { font-size: 14px; color: #555; line-height: 1.5; margin-bottom: 20px; }
    
    .form-group label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; margin-bottom: 15px; }
    .btn-submit { background: #111; color: #fff; border: none; padding: 12px 20px; font-weight: 700; text-transform: uppercase; cursor: pointer; width: 100%; }
    
    .ticket-log-card { border: 1px solid #eee; padding: 20px; margin-bottom: 15px; background: #fff; border-radius: 4px; }
    
    .status-badge { font-family: 'Barlow Condensed', sans-serif; font-size: 11px; padding: 4px 8px; border-radius: 2px; text-transform: uppercase; font-weight: 700; float: right; }
    .status-open { background: #ffeaec; color: #d9383a; border: 1px solid #fbcfe8; }
    .status-investigation { background: #fff3db; color: #d97706; border: 1px solid #fde68a; }
    .status-resolved { background: #eefdf3; color: #065f46; border: 1px solid #a7f3d0; }

    .reply-box { background: #f8fafc; border-left: 3px solid #0284c7; padding: 12px; margin-top: 12px; border-radius: 0 4px 4px 0; }
    
    .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; font-size: 14px; }
    .alert-success { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc; }
    .alert-danger { background-color: #f8d7da; color: #842029; border-color: #f5c2c7; }
</style>
</head>
<body>

  <header class="navbar">
    <div class="logo">Ubuntu Market</div>
    <nav>
      <ul class="nav-links">
        <li><a href="productPage.html">Marketplace</a></li>
        <li><a href="help.php">Help Center</a></li>
        <li><a href="dashboard.php">Dashboard</a></li>
      </ul>
    </nav>
  </header>

  <section class="hero-section">
    <h1>Help Desk & Dispute Resolutions</h1>
    <p>Submit general help queries, client support system issues, or file transaction disputes seamlessly.</p>
  </section>

  <div class="main-container">
    
    <div class="help-panel">
      <h2>Frequently Asked Questions</h2>
      
      <div class="faq-item">
        <h4>How do order refunds operate?</h4>
        <p>If an item arrives damaged or does not match descriptions, open an entry query detailing your target transaction order ID to escalate an official administrative file claim.</p>
      </div>

      <div class="faq-item">
        <h4>What is the estimated response window?</h4>
        <p>Our dedicated platform moderation team reads through active system queue files systematically. Normal response turnarounds execute within 24 operational hours.</p>
      </div>

      <div class="faq-item">
        <h4>How do I update my payment wallet details?</h4>
        <p>Navigate straight down to your internal Profile options parameters grid window to link alternative payout nodes securely.</p>
      </div>
    </div>

    <div class="help-panel">
      <h2>Lodge Ticket / Order Dispute</h2>
      
      <?php if (!empty($success_msg)) { echo '<div class="alert alert-success">'.$success_msg.'</div>'; } ?>
      <?php if (!empty($error_msg)) { echo '<div class="alert alert-danger">'.$error_msg.'</div>'; } ?>

      <form action="help.php" method="POST">
        <div class="form-group">
          <label>Full Structural Name</label>
          <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($prefilledName); ?>" required />
        </div>

        <div class="form-group">
          <label>Registered System Email Address</label>
          <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($prefilledEmail); ?>" required />
        </div>

        <div class="form-group">
          <label>Subject / Matter Title</label>
          <input type="text" name="subject" class="form-control" placeholder="Briefly specify your core issue..." required />
        </div>

        <div class="form-group">
          <label>Linked Order ID Reference Number <span style="color:#9ca3af; font-size:11px;">(Optional Dispute Mode Only)</span></label>
          <input type="number" name="order_id" class="form-control" placeholder="e.g. 100456 (Leave blank if general support)" />
        </div>

        <div class="form-group">
          <label>Urgency Level Metric</label>
          <select name="urgency" class="form-control">
            <option value="Low">Low Priority</option>
            <option value="Medium">Medium Priority</option>
            <option value="High">High Priority</option>
          </select>
        </div>

        <div class="form-group">
          <label>Detailed Explanation Log Context</label>
          <textarea name="message" class="form-control" style="height:120px;" placeholder="Provide a complete chronological overview of what occurred..." required></textarea>
        </div>

        <button type="submit" name="submit_ticket" class="btn-submit">Dispatch Help Ticket</button>
      </form>
    </div>

    <div class="help-panel full-width-panel">
      <h2>Your Recent Support Tickets Logs</h2>

      <?php
      // Determine target lookup email regardless of explicit login state matrix
      $lookupEmail = $prefilledEmail;
      if (empty($lookupEmail) && !empty($_SESSION['last_ticket_email'])) {
          $lookupEmail = $_SESSION['last_ticket_email'];
      }

      if (empty($lookupEmail)) {
          echo '<p style="color:#6b7280; font-style:italic; margin:0;">Please login or submit a ticket to monitor real-time tracking queues or read active response timelines.</p>';
      } else {
          $safeEmail = $conn->real_escape_string($lookupEmail);
          $fetchLogQuery = $conn->query("SELECT id, subject, order_id, message, urgency, status, created_at FROM tickets WHERE email = '$safeEmail' ORDER BY id DESC LIMIT 3");
          
          if (!$fetchLogQuery || $fetchLogQuery->num_rows === 0) {
              echo '<p style="color:#6b7280; font-style:italic; margin:0;">No operational ticket escalations logged under your account context.</p>';
          } else {
              while ($ticket = $fetchLogQuery->fetch_assoc()) { 
                  $rawStatus = strtolower($ticket['status']);
                  $cleanStatus = 'open';
                  if ($rawStatus === 'under investigation' || $rawStatus === 'in progress') {
                      $cleanStatus = 'investigation';
                  } elseif ($rawStatus === 'resolved' || $rawStatus === 'closed') {
                      $cleanStatus = 'resolved';
                  }
                  ?>
                  <div class="ticket-log-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                      <span style="font-size:13px; color:#4b5563;">
                        <strong>Ticket #TK-<?php echo str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?></strong> &nbsp;|&nbsp; 
                        Subject: <?php echo htmlspecialchars($ticket['subject']); ?>
                        <?php if (!empty($ticket['order_id'])): ?>
                            &nbsp;|&nbsp; <span style="color:#ea580c; font-weight:bold;">Linked Order: #ORD-<?php echo htmlspecialchars($ticket['order_id']); ?></span>
                        <?php endif; ?>
                      </span>
                      <span class="status-badge status-<?php echo $cleanStatus; ?>"><?php echo htmlspecialchars($ticket['status']); ?></span>
                    </div>
                    <p style="font-size:14px; margin: 5px 0 12px 0; color: #333;"><strong>My Description:</strong> <?php echo htmlspecialchars($ticket['message']); ?></p>
                    
                    <?php
                    $t_id = (int)$ticket['id'];
                    $replyQuery = $conn->query("SELECT message, created_at FROM ticket_replies WHERE ticket_id = $t_id ORDER BY id ASC");
                    if ($replyQuery && $replyQuery->num_rows > 0) {
                        while ($reply = $replyQuery->fetch_assoc()) { ?>
                            <div class="reply-box">
                                <span style="font-size:12px; font-weight:700; color:#0369a1; display: block; margin-bottom: 4px;">📩 Official Support Response:</span>
                                <p style="margin:4px 0; font-size:13.5px; color:#222; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($reply['message'])); ?></p>
                                <small style="color:#6b7280; font-size:11px; display:block; margin-top: 6px;"><?php echo $reply['created_at']; ?></small>
                            </div>
                        <?php }
                    } else {
                        echo '<small style="color:#9ca3af; font-style:italic;">Awaiting formal administration reply streams...</small>';
                    }
                    ?>
                  </div>
              <?php }
          }
      }
      $conn->close();
      ?>
    </div>

  </div>

</body>
</html>