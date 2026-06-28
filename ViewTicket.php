<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_logged_in'])) {
    header("Location: Login.php");
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
    die("Database fallback connection failure.");
}

if (!isset($_GET['id'])) {
    die("No explicit ticket ID provided.");
}

$ticketId = intval($_GET['id']);
$userEmail = $_SESSION['user_email']; 
$userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

$ticketStmt = $conn->prepare("SELECT id, subject, order_id, message, status, urgency, created_at FROM tickets WHERE id = ? AND email = ?");
$ticketStmt->bind_param("is", $ticketId, $userEmail);
$ticketStmt->execute();
$ticketResult = $ticketStmt->get_result();

if ($ticketResult->num_rows === 0) {
    die("Ticket not found or access denied.");
}

$ticket = $ticketResult->fetch_assoc();
$ticketStmt->close();

$successMsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_message'])) {
    $clientMsg = trim($_POST['client_message']);
    if (!empty($clientMsg)) {
        $insertStmt = $conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_id, sender_role, message, created_at) VALUES (?, ?, 'user', ?, NOW())");
        $insertStmt->bind_param("iis", $ticketId, $userId, $clientMsg);
        
        if ($insertStmt->execute()) {
            $conn->query("UPDATE tickets SET status = 'Under Investigation' WHERE id = $ticketId");
            header("Location: ViewTicket.php?id=" . $ticketId);
            exit();
        }
        $insertStmt->close();
    }
}

$replies = [];
$repliesStmt = $conn->prepare("SELECT sender_role, message, created_at FROM ticket_replies WHERE ticket_id = ? ORDER BY id ASC");
$repliesStmt->bind_param("i", $ticketId);
$repliesStmt->execute();
$repliesResult = $repliesStmt->get_result();
while ($row = $repliesResult->fetch_assoc()) {
    $replies[] = $row;
}
$repliesStmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #TK-<?= str_pad($ticket['id'], 4, '0', STR_PAD_LEFT) ?> | Ubuntu Market</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700;900&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Barlow', sans-serif; background: #f4f5f7; margin: 0; padding: 40px; color: #111; }
        .container { max-width: 700px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        
        .header-area { border-bottom: 2px solid #111; padding-bottom: 15px; margin-bottom: 20px; }
        .ticket-title { font-size: 24px; font-weight: 900; text-transform: uppercase; margin: 0; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-top: 8px; background: #e5e7eb; }
        .status-open { background: #fee2e2; color: #ef4444; }
        .status-investigation { background: #fef3c7; color: #d97706; }
        .status-resolved { background: #d1fae5; color: #10b981; }

        .original-complaint { background: #f9fafb; padding: 16px; border-radius: 8px; border-left: 4px solid #111; margin-bottom: 25px; }
        .original-complaint h4 { margin: 0 0 6px 0; text-transform: uppercase; font-size: 12px; color: #6b7280; }
        .order-indicator { color: #f97316; font-weight: bold; margin-top: 4px; display: block; font-size: 13px; }

        .chat-stream { display: flex; flex-direction: column; gap: 14px; margin-bottom: 30px; background: #fafafa; padding: 20px; border-radius: 8px; max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; }
        .bubble { max-width: 75%; padding: 12px 16px; border-radius: 12px; font-size: 14px; line-height: 1.5; position: relative; }

        .bubble.admin { align-self: flex-start; background: #0369a1; color: #ffffff; border-bottom-left-radius: 2px; }
    
        .bubble.user { align-self: flex-end; background: #e5e7eb; color: #111111; border-bottom-right-radius: 2px; }
        
        .bubble-meta { font-size: 10px; display: block; margin-top: 6px; opacity: 0.8; font-weight: 600; text-transform: uppercase; }
        .admin .bubble-meta { color: #e0f2fe; text-align: left; }
        .user .bubble-meta { color: #6b7280; text-align: right; }

        .input-box { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-family: 'Barlow', sans-serif; font-size: 14px; box-sizing: border-box; resize: vertical; }
        .submit-btn { background: #111; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 700; text-transform: uppercase; cursor: pointer; margin-top: 8px; float: right; transition: background 0.2s; }
        .submit-btn:hover { background: #333; }
        .no-replies { text-align: center; color: #9ca3af; font-style: italic; padding: 20px; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-area">
        <h2 class="ticket-title">Ticket Support Log #TK-<?= str_pad($ticket['id'], 4, '0', STR_PAD_LEFT) ?></h2>
        <span class="status-badge <?php 
            $s = strtolower($ticket['status']);
            if($s == 'open' || $s == 'pending') echo 'status-open';
            elseif($s == 'under investigation' || $s == 'in progress') echo 'status-investigation';
            else echo 'status-resolved';
        ?>"><?= $ticket['status'] ?></span>
    </div>

    <div class="original-complaint">
        <h4>Original Issue: <?= htmlspecialchars($ticket['subject']) ?></h4>
        <p style="margin: 0; font-size: 14px; color: #374151;"><?= htmlspecialchars($ticket['message']) ?></p>
        
        <?php if(!empty($ticket['order_id'])): ?>
            <span class="order-indicator">🔗 Linked Order Reference: #ORD-<?= htmlspecialchars($ticket['order_id']) ?></span>
        <?php endif; ?>
    </div>

    <h3 style="text-transform: uppercase; font-size: 14px; letter-spacing: 0.5px; margin-bottom: 10px;">Communication History Thread</h3>
    
    <div class="chat-stream">
        <?php if (empty($replies)): ?>
            <div class="no-replies">Our administrative staff hasn't left a response statement yet. Your case is currently queued.</div>
        <?php else: ?>
            <?php foreach ($replies as $reply): ?>
                <div class="bubble <?= $reply['sender_role'] === 'admin' ? 'admin' : 'user' ?>">
                    <?= htmlspecialchars($reply['message']) ?>
                    <span class="bubble-meta">
                        <?= $reply['sender_role'] === 'admin' ? 'Official Support' : 'You' ?> &bull; <?= $reply['created_at'] ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php 
    $s = strtolower($ticket['status']);
    if ($s !== 'resolved' && $s !== 'closed'): 
    ?>
        <form method="POST" style="overflow: hidden; border-top: 1px solid #e5e7eb; padding-top: 20px;">
            <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 6px; display: block;">Send Message Update to Support</label>
            <textarea name="client_message" class="input-box" rows="3" placeholder="Provide additional details or address admin questions..." required></textarea>
            <button type="submit" class="submit-btn">Send Message</button>
        </form>
    <?php else: ?>
        <div style="background: #f3f4f6; padding: 12px; text-align: center; font-size: 13px; color: #4b5563; border-radius: 6px; font-weight: 600;">
            This escalation record has been closed. If you require further help, please create a brand new claim.
        </div>
    <?php endif; ?>
</div>

<script>
    const cs = document.querySelector('.chat-stream');
    if(cs) cs.scrollTop = cs.scrollHeight;
</script>
</body>
</html>