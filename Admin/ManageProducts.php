<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowedRoles = [ 'Support_Admin', 'Operations_Admin', 'Super_Admin'];
if (!isset($_SESSION['user_logged_in']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: AdminLogin.php"); 
    exit();
}

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
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// BACKGROUND DATA FETCHING & API ACTIONS (Runs on AJAX requests)
if (isset($_GET['data_stream'])) {
    header('Content-Type: application/json');
    
    // Handle AJAX Request Soft Delete (Archive) Product Record
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $productId = intval($_GET['id']);

        $archiveStmt = $conn->prepare("UPDATE products SET status = 'active' WHERE id = ?"); 
        $archiveStmt = $conn->prepare("UPDATE products SET status = 'archived' WHERE id = ?");
        $archiveStmt->bind_param("i", $productId);
        
        if ($archiveStmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $archiveStmt->error]);
        }
        
        $archiveStmt->close();
        $conn->close();
        exit();
    }

    // Pull Active Listings Safely
    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $searchTerm = '%' . trim($_GET['search']) . '%';
        $stmt = $conn->prepare("SELECT id, name, price, description, category FROM products WHERE status = 'active' AND (name LIKE ? OR description LIKE ?) ORDER BY id DESC");
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
    } else {
        $stmt = $conn->prepare("SELECT id, name, price, description, category FROM products WHERE status = 'active' ORDER BY id DESC");
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];

    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id'          => intval($row['id']),
            'name'        => !empty($row['name']) ? $row['name'] : 'Unnamed Item',
            'price'       => floatval($row['price']),
            'description' => !empty($row['description']) ? $row['description'] : 'No details provided.',
            'category'    => !empty($row['category']) ? $row['category'] : 'General'
        ];
    }

    echo json_encode($products);
    $conn->close();
    exit(); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Products | Ubuntu Market</title>
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
      vertical-align: top;
    }

    th {
      font-family: var(--font-display, 'Barlow Condensed'), sans-serif;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #6b7280;
      font-weight: 700;
    }

    .product-price {
      font-weight: 700;
      color: var(--text-primary, #111);
    }

    .category-badge {
      display: inline-block;
      padding: 4px 8px;
      background: #f3f4f6;
      color: #374151;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
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
      <a href="ManageUsers.php">Users</a>
      <a href="ManageProducts.php" class="active">Products</a>
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
        <h2 class="page-title">Marketplace Inventory Control</h2>
        <p style="color: #666; margin-top: 4px;">Monitor, filter, and moderate live product listings published to Ubuntu Market.</p>
      </div>
    </div>

    <div class="section-card">
      <div class="search-container">
        <input type="text" id="product-search-input" class="search-input" placeholder="Search marketplace inventory by title, item keyword or description info..." oninput="executeSearchQuery()">
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Description</th>
            <th>Management Action</th>
          </tr>
        </thead>
        <tbody id="product-table-body">
          <tr>
            <td colspan="6" style="text-align: center; color: #888;">Opening live inventory stream access metrics...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    let currentSearchString = "";

    function streamMarketplaceProducts() {
        fetch(`ManageProducts.php?data_stream=true&search=${encodeURIComponent(currentSearchString)}`)
        .then(response => response.json())
        .then(data => {
            const targetTableBody = document.getElementById('product-table-body');
            
            if (data.error) {
                targetTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#dc2626;">System Flag Error: ${data.error}</td></tr>`;
                return;
            }

            if (!data || data.length === 0) {
                targetTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color: #888;">No items matched your current collection filters.</td></tr>`;
                return;
            }

            targetTableBody.innerHTML = data.map(product => {
                const idFormatted = "#" + String(product.id).padStart(3, '0');
                
                return `
                  <tr>
                    <td><strong>${idFormatted}</strong></td>
                    <td style="font-weight: 600; color: #111;">${product.name}</td>
                    <td><span class="category-badge">${product.category}</span></td>
                    <td><span class="product-price">R ${product.price.toFixed(2)}</span></td>
                    <td><span style="color: #555; font-size: 13px; display: block; max-width: 340px; word-wrap: break-word;">${product.description}</span></td>
                    <td>
                      <button class="action-btn btn-delete" onclick="executeProductDeletion(${product.id})">Remove Listing</button>
                    </td>
                  </tr>
                `;
            }).join('');
        })
        .catch(err => {
            console.error("Pipeline failure:", err);
            document.getElementById('product-table-body').innerHTML = `<tr><td colspan="6" style="text-align:center; color:#dc2626;">Error parsing inventory logs stream layout.</td></tr>`;
        });
    }

    function executeSearchQuery() {
        currentSearchString = document.getElementById("product-search-input").value;
        streamMarketplaceProducts();
    }

   function executeProductDeletion(productId) {
        if (confirm("Are you absolutely sure you want to completely remove product listing #" + productId + " from the marketplace?")) {
            fetch(`ManageProducts.php?data_stream=true&action=delete&id=${productId}`)
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    streamMarketplaceProducts(); 
                } else {
                    // Display the custom error message from your database catch block
                    alert("Operation refused: " + (result.error || "Product protection fault."));
                }
            })
            .catch(() => alert("Network communication drops intercepted during execution routine."));
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        streamMarketplaceProducts();
        setInterval(streamMarketplaceProducts, 5000); 
    });
  </script>
</body>
</html>