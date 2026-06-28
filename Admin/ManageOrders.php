<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowedRoles = [ 'Support_Admin', 'Operations_Admin', 'Super_Admin', 'Moderator'];
if (!isset($_SESSION['user_logged_in']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: AdminLogin.php"); 
    exit();
}

if (isset($_GET['data_stream'])) {
    // Silence any background database notices so they don't break JSON formatting
    error_reporting(0);
    ini_set('display_errors', 0);
    
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
        // Local XAMPP Setup
        $db_host = 'localhost';
        $db_user = 'root';
        $db_pass = '';
        $db_name = 'c2c_platform'; 
    } else {
        // Live InfinityFree Setup
        $db_host = 'sql300.infinityfree.com';
        $db_user = 'if0_42151694';
        $db_pass = 'eOQ4iKLlaOj6'; 
        $db_name = 'if0_42151694_c2c_platform';
    }

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    

   // Handle AJAX Request and Update Order Transaction Status
    if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
        $orderId = intval($_GET['id']);
        $nextStatus = trim($_GET['status']);

        $updateStmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $updateStmt->bind_param("si", $nextStatus, $orderId);

        if ($updateStmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        $conn->close();
        exit();
    }

    $orders = [];
    
    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $searchTerm = '%' . trim($_GET['search']) . '%';
        // Securely searches order IDs, customer names, or status matches
        $stmt = $conn->prepare("SELECT id, full_name, total, status, created_at FROM orders WHERE full_name LIKE ? OR status LIKE ? OR id LIKE ? ORDER BY id DESC");
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, total, status, created_at FROM orders ORDER BY id DESC");
    }

    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orders[] = [
                'id'          => intval($row['id']),
                'buyer_name'  => !empty($row['full_name']) ? htmlspecialchars($row['full_name']) : 'Customer Account',
                'total_price' => floatval($row['total']),
                'status'      => !empty($row['status']) ? htmlspecialchars($row['status']) : 'Pending',
                'date'        => !empty($row['created_at']) ? substr($row['created_at'], 0, 10) : date('Y-m-d')
            ];
        }
    } 

    echo json_encode($orders);
    $conn->close();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Orders | Ubuntu Market</title>
  <link rel="stylesheet" href="Admin-shared.css">
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700;900&family=Barlow+Condensed:wght@700;900&display=swap" rel="stylesheet"/>
  <style>
    body {
      margin: 0;
      padding: 0;
      display: flex;
      background: var(--main-bg, #fafafa);
      min-height: 100vh;
      font-family: 'Barlow', sans-serif;
    }

    .main-content {
      margin-left: var(--sidebar-width, 260px);
      width: calc(100% - var(--sidebar-width, 260px));
      padding: 40px;
      box-sizing: border-box;
    }

    .page-header {
      margin-bottom: 32px;
    }

    .page-title {
      font-family: var(--font-display, 'Barlow Condensed'), sans-serif;
      font-size: 32px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-primary, #111);
    }

    .search-container {
      margin-bottom: 24px;
    }

    .search-input {
      width: 100%;
      padding: 14px 18px;
      border: 1px solid rgba(0,0,0,0.08);
      border-radius: 12px;
      font-size: 14px;
      outline: none;
      background: #ffffff;
      box-shadow: 0 2px 10px rgba(0,0,0,0.01);
      box-sizing: border-box;
    }

    .search-input:focus {
      border-color: #111;
    }

    .section-card {
      background: #ffffff;
      border-radius: 16px;
      padding: 32px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
      border: 1px solid rgba(0,0,0,0.05);
      width: 100%;
      box-sizing: border-box;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }

    th, td {
      padding: 16px;
      border-bottom: 1px solid #f3f4f6;
      font-size: 14px;
    }

    th {
      font-family: var(--font-display, 'Barlow Condensed'), sans-serif;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #6b7280;
      font-weight: 700;
    }

    .order-status-select {
      padding: 6px 10px;
      border-radius: 6px;
      border: 1px solid #ddd;
      font-size: 13px;
      font-weight: 600;
      background: #fff;
      outline: none;
      cursor: pointer;
    }

    .status-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-processing { background: #e0f2fe; color: #0284c7; }
    .status-shipped { background: #f3e8ff; color: #7c3aed; }
    .status-delivered { background: #dcfce7; color: #16a34a; }
    .status-completed { background: #dcfce7; color: #16a34a; }
    .status-cancelled { background: #fee2e2; color: #dc2626; }
  </style>
</head>
<body>

  <div class="sidebar">
    <div class="sidebar-brand">
      <h1>Ubuntu Market</h1>
      <p>Admin Panel</p>
    </div>

    <div class="admin-badge">
      <div class="admin-avatar"><?= isset($_SESSION['user_name']) ? strtoupper(substr(htmlspecialchars($_SESSION['user_name']), 0, 1)) : 'B' ?></div>
      <div class="admin-info">
        <span><?= isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Blessing' ?></span>
        <span class="admin-role"><?= isset($_SESSION['user_role']) ? str_replace('_', ' ', htmlspecialchars($_SESSION['user_role'])) : 'Super Admin' ?></span>
      </div>
    </div>
    
    <div class="nav-section-label">Main</div>
    <nav>
      <a href="AdminDash.php">Dashboard</a>
      <a href="ManageUsers.php">Users</a>
      <a href="ManageProducts.php">Products</a>
      <a href="ManageOrders.php" class="active">Orders</a>
      <div class="nav-section-label">Account</div>
      <a href="Reports.php">Reports</a>
      <a href="AdminReplyEmail.php">Ticket Replies</a>
      <div class="sidebar-spacer"></div>
      <a href="AdminLogout.php" style="color: #dc2626;">Logout</a>
    </nav>
  </div>

  <div class="main-content">
    <div class="page-header">
      <div>
        <h2 class="page-title">Fulfillment & Orders Control</h2>
        <p style="color: #666; margin-top: 4px;">Track transaction details, monitor order revenue totals, and update shipment status codes.</p>
      </div>
    </div>

    <div class="section-card">
      <div class="search-container">
        <input type="text" id="order-search-input" class="search-input" placeholder="Search customer invoices by client profile name or status keyword..." oninput="executeSearchQuery()">
      </div>

      <table>
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Buyer Customer</th>
            <th>Transaction Date</th>
            <th>Invoice Total</th>
            <th>Fulfillment Badge</th>
            <th>Change Status</th>
          </tr>
        </thead>
        <tbody id="orders-table-body">
          <tr>
            <td colspan="6" style="text-align: center; color: #888;">Opening live transaction telemetry feed...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    let currentSearchString = "";

    function streamMarketplaceOrders() {
        fetch(`ManageOrders.php?data_stream=true&search=${encodeURIComponent(currentSearchString)}&_nocache=${Date.now()}`)
        .then(response => {
            if (!response.ok) throw new Error("HTTP error payload state");
            return response.json();
        })
        .then(data => {
            const targetTableBody = document.getElementById('orders-table-body');
            
            if (data.error) {
                targetTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#dc2626;">System Flag Error: ${data.error}</td></tr>`;
                return;
            }

            if (!data || data.length === 0) {
                targetTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color: #888;">No transaction logs discovered.</td></tr>`;
                return;
            }

            targetTableBody.innerHTML = data.map(order => {
                const idFormatted = "#" + String(order.id).padStart(3, '0');
                const cleanStatus = String(order.status).toLowerCase();
                
                // Set uniform badge styling colors
                let badgeClass = "status-pending";
                if (cleanStatus === 'shipped') badgeClass = "status-shipped";
                if (cleanStatus === 'delivered') badgeClass = "status-delivered";
                
                if (cleanStatus === 'cancelled') badgeClass = "status-cancelled";

                return `
                  <tr>
                    <td><strong>${idFormatted}</strong></td>
                    <td style="font-weight: 600; color: #111;">${order.buyer_name}</td>
                    <td style="color: #666;">${order.date}</td>
                    <td style="font-weight: 700; color: #111;">R ${order.total_price.toFixed(2)}</td>
                    <td><span class="status-badge ${badgeClass}">${order.status}</span></td>
                    <td>
                      <select class="order-status-select" onchange="executeUpdateStatus(${order.id}, this.value)">
                        <option value="Pending" ${cleanStatus === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="Shipped" ${cleanStatus === 'shipped' ? 'selected' : ''}>Shipped</option>
                        <option value="Delivered" ${cleanStatus === 'delivered' ? 'selected' : ''}>Delivered</option>
                        
                        <option value="Cancelled" ${cleanStatus === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                      </select>
                    </td>
                  </tr>
                `;
            }).join('');
        })
        .catch(err => {
            console.error("Pipeline failure connection error tracking logs:", err);
            document.getElementById('orders-table-body').innerHTML = `<tr><td colspan="6" style="text-align:center; color:#dc2626;">Error parsing billing stream data logs layout.</td></tr>`;
        });
    }

    function executeSearchQuery() {
        currentSearchString = document.getElementById("order-search-input").value;
        streamMarketplaceOrders();
    }

    function executeUpdateStatus(orderId, nextStatusState) {
        fetch(`ManageOrders.php?data_stream=true&action=update_status&id=${orderId}&status=${nextStatusState}&_nocache=${Date.now()}`)
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                // Instantly re-fetch data stream to visually confirm state
                streamMarketplaceOrders(); 
            } else {
                alert("Notice: " + (result.error || "Running on UI placeholder values."));
            }
        })
        .catch(() => alert("Network communication dropped."));
    }

    document.addEventListener("DOMContentLoaded", () => {
        streamMarketplaceOrders();
        // Automatically check records every 5 seconds
        setInterval(streamMarketplaceOrders, 5000); 
    });
  </script>
</body>
</html>