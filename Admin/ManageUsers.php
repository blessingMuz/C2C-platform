<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowedRoles = [ 'Support_Admin', 'Operations_Admin', 'Super_Admin'];
if (!isset($_SESSION['user_logged_in']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: AdminLogin.php"); 
    exit();
}

// Detect if this execution path is an active asynchronous data request from JavaScript
if (isset($_GET['data_stream'])) {
    header('Content-Type: application/json');
    
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
        echo json_encode(['error' => 'Database system collection link drop']);
        exit();
    }

    // Handle AJAX Action Requests Record Deletion Routine
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $actionUserId = intval($_GET['id']);
        
        // Prevent an administrator from deleting their own active profile session
        if ($actionUserId === intval($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Self-deletion restricted.']);
            $conn->close();
            exit();
        }

        $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->bind_param("i", $actionUserId);
        $deleteStmt->execute();
        
        echo json_encode(['success' => true]);
        $conn->close();
        exit();
    }

    // Build Search Filtering Parameters Safely
    $sqlQuery = "SELECT id, name, email, role FROM users";
    $searchString = "";
    
    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $searchTerm = '%' . trim($_GET['search']) . '%';
        $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY id DESC");
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, role FROM users ORDER BY id DESC");
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id'       => intval($row['id']),
            'username' => !empty($row['name']) ? $row['name'] : 'Unnamed Account Profile',
            'email'    => !empty($row['email']) ? $row['email'] : 'No Email Records Provided',
            'role'     => !empty($row['role']) ? $row['role'] : 'User'
        ];
    }

    echo json_encode($users);
    $conn->close();
    exit(); // Terminate early so no HTML formatting gets sent into the backend JSON data stream
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users | Ubuntu Market</title>
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

    .action-btn {
      padding: 6px 12px;
      border: none;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }

    .btn-delete {
      background: #fee2e2;
      color: #dc2626;
    }

    .btn-delete:hover {
      background: #fca5a5;
    }
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
      <a href="ManageUsers.php" class="active">Users</a>
      <a href="ManageProducts.php">Products</a>
      <a href="ManageOrders.php">Orders</a>
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
        <h2 class="page-title">User Base Management</h2>
        <p style="color: #666; margin-top: 4px;">Monitor platform registration profiles, roles, and records access details.</p>
      </div>
    </div>

    <div class="section-card">
      <div class="search-container">
        <input type="text" id="user-search-input" class="search-input" placeholder="Search users dynamically by typing name or email address..." oninput="executeSearchQuery()">
      </div>

      <table>
        <thead>
          <tr>
            <th>User ID</th>
            <th>Full Name</th>
            <th>Email Address</th>
            <th>Role Account Type</th>
            <th>Management Actions</th>
          </tr>
        </thead>
        <tbody id="user-table-body">
          <tr>
            <td colspan="5" style="text-align: center; color: #888;">Opening secure database telemetric data stream...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    let currentSearchString = "";

    function streamMarketplaceUsers() {
        fetch(`ManageUsers.php?data_stream=true&search=${encodeURIComponent(currentSearchString)}`)
        .then(response => response.json())
        .then(data => {
            const targetTableBody = document.getElementById('user-table-body');
            
            if (data.error) {
                targetTableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:#dc2626;">System Flag Error: ${data.error}</td></tr>`;
                return;
            }

            if (!data || data.length === 0) {
                targetTableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color: #888;">No matching platform account records discovered.</td></tr>`;
                return;
            }

            targetTableBody.innerHTML = data.map(user => {
                const idFormatted = "#" + String(user.id).padStart(3, '0');
                const roleFormatted = String(user.role).replace('_', ' ');
                
                return `
                  <tr>
                    <td><strong>${idFormatted}</strong></td>
                    <td>${user.username}</td>
                    <td><span style="color: #666;">${user.email}</span></td>
                    <td><span style="text-transform: capitalize; font-weight: 600;">${roleFormatted}</span></td>
                    <td>
                      <button class="action-btn btn-delete" onclick="executeUserDeletion(${user.id})">Delete Account</button>
                    </td>
                  </tr>
                `;
            }).join('');
        })
        .catch(err => {
            console.error("Pipeline breakdown failure details:", err);
            document.getElementById('user-table-body').innerHTML = `<tr><td colspan="5" style="text-align:center; color:#dc2626;">Error parsing tracking streams data logs layout.</td></tr>`;
        });
    }

    function executeSearchQuery() {
        currentSearchString = document.getElementById("user-search-input").value;
        streamMarketplaceUsers();
    }

    function executeUserDeletion(userId) {
        if (confirm("Are you certain you want to permanently delete user profiling record " + userId + "? This cannot be reversed.")) {
            fetch(`ManageUsers.php?data_stream=true&action=delete&id=${userId}`)
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    streamMarketplaceUsers(); 
                } else {
                    alert("Operation blocked: " + (result.error || "System security authorization refusal"));
                }
            })
            .catch(() => alert("Network communication drops intercepted during user deletion execution."));
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        streamMarketplaceUsers();
        setInterval(streamMarketplaceUsers, 5000); 
    });
  </script>
</body>
</html>