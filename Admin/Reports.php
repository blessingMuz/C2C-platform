<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowedRoles = [ 'Support_Admin', 'Operations_Admin', 'Super_Admin'];
if (!isset($_SESSION['user_logged_in']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: AdminLogin.php"); 
    exit();
}

if (isset($_GET['data_stream'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
    header('Content-Type: application/json; charset=utf-8');
    
  
    if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
        // Local XAMPP Parameters
        $db_host = 'localhost';
        $db_user = 'root';
        $db_pass = '';
        $db_name = 'c2c_platform'; 
    } else {
        // Live InfinityFree Parameters
        $db_host = 'sql300.infinityfree.com';
        $db_user = 'if0_42151694';
        $db_pass = 'eOQ4iKLlaOj6'; 
        $db_name = 'if0_42151694_c2c_platform';
    }

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    

    if ($conn->connect_error) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    // Handle Live Action Dispatcher to Close/Resolve Ticket
    if (isset($_GET['action']) && $_GET['action'] === 'resolve_ticket' && isset($_GET['id'])) {
        $ticketId = intval($_GET['id']);

        $checkTable = $conn->query("SHOW TABLES LIKE 'tickets'");
        $tableName = ($checkTable && $checkTable->num_rows > 0) ? 'tickets' : '';
        
        if (empty($tableName)) {
            $checkAlt = $conn->query("SHOW TABLES LIKE 'customer_support'");
            if ($checkAlt && $checkAlt->num_rows > 0) {
                $tableName = 'customer_support';
            }
        }

        if (!empty($tableName)) {
            $updateStmt = $conn->prepare("UPDATE $tableName SET status = 'Resolved' WHERE id = ?");
            $updateStmt->bind_param("i", $ticketId);
            $updateStmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => true]);
        }
        $conn->close();
        exit();
    }

    $totalTickets = 0;
    $resolvedTickets = 0;
    $pendingTickets = 0;
    $ticketsList = [];

    $checkTable = $conn->query("SHOW TABLES LIKE 'tickets'");
    $targetTable = ($checkTable && $checkTable->num_rows > 0) ? 'tickets' : '';
    if (empty($targetTable)) {
        $checkAlt = $conn->query("SHOW TABLES LIKE 'customer_support'");
        if ($checkAlt && $checkAlt->num_rows > 0) { $targetTable = 'customer_support'; }
    }

    if (!empty($targetTable)) {
        $countRes = $conn->query("SELECT COUNT(*) as total FROM $targetTable");
        if ($countRes) $totalTickets = intval($countRes->fetch_assoc()['total']);

        $resRes = $conn->query("SELECT COUNT(*) as total FROM $targetTable WHERE status = 'Resolved'");
        if ($resRes) $resolvedTickets = intval($resRes->fetch_assoc()['total']);
        
        $pendingTickets = $totalTickets - $resolvedTickets;

        $colCheck = $conn->query("SHOW COLUMNS FROM $targetTable");
        $cols = [];
        while($c = $colCheck->fetch_assoc()) { $cols[] = $c['Field']; }

        $nameField = in_array('full_name', $cols) ? 'full_name' : (in_array('name', $cols) ? 'name' : "'Customer' as full_name");
        $emailField = in_array('email', $cols) ? 'email' : "'No Email Provided' as email";
        $subjectField = in_array('subject', $cols) ? 'subject' : "'General inquiry' as subject";
        $messageField = in_array('message', $cols) ? 'message' : "'No description message' as message";
        $urgencyField = in_array('urgency', $cols) ? 'urgency' : (in_array('priority', $cols) ? 'priority' : "'Medium' as urgency");
        $statusField = in_array('status', $cols) ? 'status' : "'Pending' as status";

        $listQuery = $conn->query("SELECT id, $nameField as full_name, $emailField as email, $subjectField as subject, $messageField as message, $urgencyField as urgency, $statusField as status FROM $targetTable ORDER BY id DESC LIMIT 10");
        
        if ($listQuery) {
            while ($row = $listQuery->fetch_assoc()) {
                $ticketsList[] = [
                    'id'       => intval($row['id']),
                    'name'     => htmlspecialchars($row['full_name']),
                    'email'    => htmlspecialchars($row['email']),
                    'subject'  => htmlspecialchars($row['subject']),
                    'message'  => htmlspecialchars($row['message']),
                    'urgency'  => htmlspecialchars($row['urgency']),
                    'status'   => htmlspecialchars($row['status'])
                ];
            }
        }
    }

    echo json_encode([
        'total'    => $totalTickets,
        'resolved' => $resolvedTickets,
        'pending'  => $pendingTickets,
        'tickets'  => $ticketsList
    ]);
    $conn->close();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports & Disputes – Ubuntu Market</title>
  <link rel="stylesheet" href="Admin-shared.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700;900&family=Barlow+Condensed:wght@700;900&display=swap" rel="stylesheet"/>
  <style>
    body { margin: 0; padding: 0; display: flex; background: var(--main-bg); min-height: 100vh; }
    .main-content-wrapper { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); padding: 40px; box-sizing: border-box; }
    .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; margin-bottom: 32px; width: 100%; }
    .metric-card { background: var(--card-bg); border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); border: 1px solid rgba(0,0,0,0.05); }
    .metric-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); display: block; margin-bottom: 8px; }
    .metric-value { font-family: var(--font-display); font-size: 36px; font-weight: 900; color: var(--text-primary); }
    .section-card { background: var(--card-bg); border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); border: 1px solid rgba(0,0,0,0.05); width: 100%; box-sizing: border-box; margin-bottom: 32px; }
    .page-header { margin-bottom: 32px; }
    .page-title { font-family: var(--font-display); font-size: 32px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-primary); }
    .page-subtitle { font-size: 14px; color: var(--text-muted); margin-top: 4px; }
    .chart-container-wrapper { position: relative; height: 260px; width: 100%; }
    .priority-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .high { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
    .medium { background: rgba(251, 191, 36, 0.1); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.2); }
    .low { background: rgba(96, 165, 250, 0.1); color: #60a5fa; border: 1px solid rgba(96, 165, 250, 0.2); }
    .resolved-text { font-weight: 700; color: #34d399; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
    .resolve-btn { background: #ffffff; color: #000000; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 700; text-transform: uppercase; transition: background 0.2s; }
    .resolve-btn:hover { background: #cccccc; }
  </style>
</head>
<body>

  <div class="sidebar">
    <div class="sidebar-brand">
      <h1>Ubuntu Market</h1>
      <p>Admin Panel</p>
    </div>
    
    <div class="admin-badge">
      <div class="admin-avatar"><?= isset($_SESSION['user_name']) ? strtoupper(substr(htmlspecialchars($_SESSION['user_name']), 0, 1)) : 'A' ?></div>
      <div class="admin-info">
        <span><?= isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Administrator' ?></span>
        <span class="admin-role"><?= isset($_SESSION['user_role']) ? str_replace('_', ' ', htmlspecialchars($_SESSION['user_role'])) : 'Admin' ?></span>
      </div>
    </div>

    <div class="nav-section-label">Main</div>
    <nav>
      <a href="AdminDash.php">Dashboard</a>
      <a href="ManageUsers.php">Users</a>
      <a href="ManageProducts.php">Products</a>
      <a href="ManageOrders.php">Orders</a>
      <div class="nav-section-label">Account</div>
      <a href="Reports.php" class="active">Reports</a>
      <a href="AdminReplyEmail.php">Ticket Replies</a>
      <div class="sidebar-spacer"></div>
      <a href="AdminLogout.php" style="color: var(--accent-red);">Logout</a>
    </nav>
  </div>

  <div class="main-content-wrapper">
    
    <div class="page-header">
      <div>
        <h2 class="page-title">Reports & Disputes Console</h2>
        <p class="page-subtitle">Manage system support tickets, reported listings, and escrow disputes.</p>
      </div>
    </div>

    <div class="metrics-grid">
      <div class="metric-card">
        <span class="metric-label">Total Tickets</span>
        <div id="count-total" class="metric-value">...</div>
      </div>
      <div class="metric-card">
        <span class="metric-label">Open Claims</span>
        <div id="count-open" class="metric-value" style="color: var(--accent-red);">...</div>
      </div>
      <div class="metric-card">
        <span class="metric-label">Escrow Holds</span>
        <div id="count-escrow" class="metric-value" style="color: var(--accent-orange);">0</div>
      </div>
      <div class="metric-card">
        <span class="metric-label">Resolved Cases</span>
        <div id="count-resolved" class="metric-value" style="color: #34d399;">...</div>
      </div>
    </div>

    <div class="section-card">
      <h3 style="font-family: var(--font-display); font-size: 20px; text-transform: uppercase; margin-bottom: 20px;">Ticket Distribution Analytics</h3>
      <div class="chart-container-wrapper">
        <canvas id="disputesAnalyticsChart"></canvas>
      </div>
    </div>

    <div class="section-card">
      <h3 style="font-family: var(--font-display); font-size: 20px; text-transform: uppercase; margin-bottom: 20px;">Active Complaints Queue</h3>
      <table>
        <thead>
          <tr>
            <th style="width: 12%; text-align: center;">Ticket ID</th>
            <th style="width: 25%;">User Profile</th>
            <th style="width: 38%; text-align: left;">Subject Issue</th>
            <th style="width: 10%; text-align: center;">Urgency</th>
            <th style="width: 15%; text-align: center;">Management Action</th>
          </tr>
        </thead>
        <tbody id="disputes-table-body">
          <tr>
            <td colspan="5" style="text-align: center; color: var(--text-muted);">Connecting to real-time data stream...</td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>

<script>
let analyticsChartInstance = null;

function renderAnalyticsChart(openCount, resolvedCount) {
  const canvasCtx = document.getElementById('disputesAnalyticsChart').getContext('2d');
  
  if (analyticsChartInstance) {
    analyticsChartInstance.destroy();
  }

  const displayData = (openCount === 0 && resolvedCount === 0) ? [0, 1] : [openCount, resolvedCount];
  const displayLabels = (openCount === 0 && resolvedCount === 0) ? ['No Active Tickets', 'Queue Clear'] : ['Open Claims', 'Resolved Cases'];
  
  const displayColors = (openCount === 0 && resolvedCount === 0) 
    ? ['rgba(255, 255, 255, 0.03)', 'rgba(255, 255, 255, 0.03)'] 
    : ['rgba(239, 68, 68, 0.15)', 'rgba(52, 211, 153, 0.15)'];
    
  const borderColors = (openCount === 0 && resolvedCount === 0)
    ? ['#262626', '#262626']
    : ['#ef4444', '#34d399'];

  analyticsChartInstance = new Chart(canvasCtx, {
    type: 'bar',
    data: {
      labels: displayLabels,
      datasets: [{
        data: displayData,
        backgroundColor: displayColors,
        borderColor: borderColors,
        borderWidth: 1,
        borderRadius: 4, 
        barThickness: 32
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: { 
        y: { 
          beginAtZero: true, 
          ticks: { color: '#666666', font: { family: 'Barlow', size: 11 }, stepSize: 1 },
          grid: { color: 'rgba(255,255,255,0.05)', drawTicks: false }
        },
        x: { 
          ticks: { color: '#888888', font: { family: 'Barlow', size: 12, weight: 600 } },
          grid: { display: false } 
        }
      }
    }
  });
}

function streamDisputesDashboard() {
  fetch('Reports.php?data_stream=true')
    .then(res => {
      if (!res.ok) throw new Error("Network status error");
      return res.json();
    })
    .then(data => {
      document.getElementById('count-total').innerText = data.total;
      document.getElementById('count-open').innerText = data.pending;
      document.getElementById('count-resolved').innerText = data.resolved;

      renderAnalyticsChart(data.pending, data.resolved);

      const tableBody = document.getElementById('disputes-table-body');
      if (!data.tickets || data.tickets.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="5" style="color: var(--text-muted); text-align:center; padding:30px; font-style:italic;">No complaints filed in system registry.</td></tr>`;
        return;
      }

      tableBody.innerHTML = data.tickets.map(ticket => {
        const isResolved = ticket.status.toLowerCase() === 'resolved';
        
        let priorityClass = 'low';
        if (ticket.urgency.toLowerCase() === 'high') priorityClass = 'high';
        if (ticket.urgency.toLowerCase() === 'medium') priorityClass = 'medium';

        const actionColumn = isResolved 
          ? `<span class="resolved-text">✓ Closed</span>`
          : `<button class="resolve-btn" onclick="modifyTicketState(${ticket.id})">Close</button>`;

        return `
          <tr>
            <td style="text-align: center; color: var(--text-muted);">#TK-${String(ticket.id).padStart(4, '0')}</td>
            <td><strong>${ticket.name}</strong><br><small style="color: var(--text-muted);">${ticket.email}</small></td>
            <td style="text-align:left;"><strong>${ticket.subject}</strong><br><span style="font-size:13px; color: var(--text-muted); display:inline-block; margin-top:3px;">${ticket.message}</span></td>
            <td style="text-align: center;"><span class="priority-badge ${priorityClass}">${ticket.urgency}</span></td>
            <td style="text-align: center;">${actionColumn}</td>
          </tr>
        `;
      }).join('');
    })
    .catch(err => {
      console.error("Telemetry pipeline offline:", err);
      document.getElementById('disputes-table-body').innerHTML = `<tr><td colspan="5" style="text-align:center; color:var(--accent-red);">Telemetry connection trace lost. Reconnecting...</td></tr>`;
    });
}

function modifyTicketState(ticketId) {
  if (confirm(`Mark ticket complaint record #${ticketId} as completely resolved?`)) {
    fetch(`Reports.php?data_stream=true&action=resolve_ticket&id=${ticketId}`)
    .then(res => res.json())
    .then(result => {
      if (result.success) {
        streamDisputesDashboard();
      } else {
        alert("Operation blocked.");
      }
    });
  }
}

document.addEventListener("DOMContentLoaded", () => {
  streamDisputesDashboard();
  setInterval(streamDisputesDashboard, 5000);
});
</script>
</body>
</html>