<?php
// Admin panel
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check that route back to HTML visual form if unauthorized
if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], [ 'Support_Admin', 'Operations_Admin', 'Super_Admin'])) {
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
// Fecthes the unresolved tickets 
$openTicketsList = [];
$ticketQuery = $conn->query("SELECT id, full_name, subject, status FROM tickets WHERE status != 'Resolved' AND status != 'Closed' ORDER BY id DESC");
if ($ticketQuery) {
    while ($row = $ticketQuery->fetch_assoc()) {
        $openTicketsList[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard – Ubuntu Market</title>
  <link rel="stylesheet" href="Admin-shared.css">
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700;900&family=Barlow+Condensed:wght@700;900&display=swap" rel="stylesheet"/>
  <style>
    body { margin: 0; padding: 0; display: flex; background: var(--main-bg); min-height: 100vh; font-family: 'Barlow', sans-serif; }
    .main-content-wrapper { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); padding: 40px; box-sizing: border-box; }
    .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 32px; width: 100%; }
    .metric-card { background: var(--card-bg); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); border: 1px solid rgba(0,0,0,0.05); }
    .metric-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); display: block; margin-bottom: 8px; }
    .metric-value { font-family: var(--font-display); font-size: 36px; font-weight: 900; color: var(--text-primary); }
    .section-card { background: var(--card-bg); border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); border: 1px solid rgba(0,0,0,0.05); width: 100%; box-sizing: border-box; margin-bottom: 32px; }
    .page-header { margin-bottom: 32px; }
    .page-title { font-family: var(--font-display); font-size: 32px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-primary); }
    .page-subtitle { font-size: 14px; color: var(--text-muted); margin-top: 4px; }
    
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { background: #111; color: #fff; text-transform: uppercase; font-size: 11px; padding: 12px; }
    td { padding: 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
    .action-btn { background: #ea580c; color: white; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-weight: bold; font-size: 11px; text-transform: uppercase; display: inline-block; }
    .action-btn:hover { background: #111; }
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
      <a href="AdminDash.php" class="active">Dashboard</a>
      <a href="ManageUsers.php">Users</a>
      <a href="ManageProducts.php">Products</a>
      <a href="ManageOrders.php">Orders</a>
      <div class="nav-section-label">Account</div>
      <a href="Reports.php">Reports</a>
      <a href="AdminReplyEmail.php">Ticket Replies</a>
      <div class="sidebar-spacer"></div>
      <a href="AdminLogout.php" style="color: var(--accent-red);">Logout</a>
    </nav>
  </div>

  <div class="main-content-wrapper">
    
    <div class="page-header">
      <div>
        <h2 class="page-title">Admin Dashboard</h2>
        <p class="page-subtitle">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>. Here is what's happening on Ubuntu Market today.</p>
      </div>
    </div>

    <div class="metrics-grid">
      <div class="metric-card">
        <span class="metric-label">Total Registered Users</span>
        <div id="metric-total-users" class="metric-value">...</div>
      </div>
      <div class="metric-card">
        <span class="metric-label">Active Market Listings</span>
        <div id="metric-active-listings" class="metric-value">...</div>
      </div>
      <div class="metric-card">
        <span class="metric-label">Total Platform Orders</span>
        <div id="metric-total-orders" class="metric-value" style="color: var(--accent-orange);">...</div>
      </div>
    </div>

    <div class="section-card">
      <h3 style="font-family: var(--font-display); font-size: 20px; text-transform: uppercase; margin-bottom: 20px; color: #ea580c;">Incoming Support Tickets</h3>
      <table>
        <thead>
          <tr>
            <th>Ticket Reference ID</th>
            <th>Client Name</th>
            <th>Subject Matter Context</th>
            <th>Status</th>
            <th>Action Link</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($openTicketsList)): ?>
            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 20px;">No open help requests waiting resolution.</td></tr>
          <?php else: ?>
            <?php foreach ($openTicketsList as $dashTicket): ?>
              <tr>
                <td><strong>#TK-<?= str_pad($dashTicket['id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                <td><?= htmlspecialchars($dashTicket['full_name']) ?></td>
                <td><?= htmlspecialchars($dashTicket['subject']) ?></td>
                <td><span style="color: #dc2626; font-weight: bold; font-size: 12px; text-transform: uppercase;"><?= htmlspecialchars($dashTicket['status']) ?></span></td>
                <td><a href="AdminReplyEmail.php?id=<?= $dashTicket['id'] ?>" class="action-btn">Open &amp; Reply</a></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="section-card">
      <h3 style="font-family: var(--font-display); font-size: 20px; text-transform: uppercase; margin-bottom: 20px;">Recent Platform Registrations</h3>
      <table>
        <thead>
          <tr>
            <th>User ID</th>
            <th>Name</th>
            <th>Email Address</th>
            <th>Assigned Role</th>
          </tr>
        </thead>
        <tbody id="recent-users-table-body">
          <tr>
            <td colspan="4" style="text-align: center; color: var(--text-muted);">Connecting to real-time data stream...</td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>

  <script>
    function loadDashboardDataPipeline() {
        fetch('getAdminMetrics.php')
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error("Server Error " + response.status) });
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('metric-total-users').textContent = data.total_users !== undefined ? data.total_users : '0';
            document.getElementById('metric-active-listings').textContent = data.total_products !== undefined ? data.total_products : '0';
            document.getElementById('metric-total-orders').textContent = data.total_orders !== undefined ? data.total_orders : '0';

            const targetTableBody = document.getElementById('recent-users-table-body');
            if (!data.recent_users || data.recent_users.length === 0) {
                targetTableBody.innerHTML = `<tr><td colspan="4" style="text-align:center; color: var(--text-muted);">No recent platform registrations found.</td></tr>`;
                return;
            }
            
            targetTableBody.innerHTML = data.recent_users.map(user => {
                const userIdFormatted = "#" + String(user.id).padStart(3, '0');
                const roleClean = String(user.role || 'User').replace('_', ' ');
                return `
                  <tr>
                    <td><strong>${userIdFormatted}</strong></td>
                    <td>${user.name}</td>
                    <td><span style="color: var(--text-muted);">${user.email}</span></td>
                    <td><span class="status-badge status-active">${roleClean}</span></td>
                  </tr>
                `;
            }).join('');
        })
        .catch(err => {
            console.error("Real-time stream connection drop:", err);
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        loadDashboardDataPipeline(); 
        setInterval(loadDashboardDataPipeline, 10000); 
    });
  </script>
</body>
</html>