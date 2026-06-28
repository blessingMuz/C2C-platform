<?php
include("config/db.php"); 

session_start();

// Ensure only sellers can run updates or view this content
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'seller') {
    header("Location: login.html?error=Unauthorized+access");
    exit();
}

$seller_id = $_SESSION['user_id']; 
$status_message = "";

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $id, $seller_id);
    if ($stmt->execute()) {
        $status_message = "<div class='alert success'>Product successfully deleted from your catalog.</div>";
    } else {
        $status_message = "<div class='alert error'>Error removing product.</div>";
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update'])) {
    $product_id  = intval($_POST['product_id']);
    $name        = $_POST['name'];
    $price       = floatval($_POST['price']);
    $description = $_POST['description'];
    $category    = $_POST['category'];

    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, description = ?, category = ? WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("sdssii", $name, $price, $description, $category, $product_id, $seller_id);
    
    if ($stmt->execute()) {
        $status_message = "<div class='alert success'>Product listing successfully updated!</div>";
    } else {
        $status_message = "<div class='alert error'>Failed to save database modifications.</div>";
    }
    $stmt->close();
}

$result = $conn->query("SELECT * FROM products WHERE seller_id = $seller_id ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products | Ubuntu Market</title>
    <link rel="stylesheet" href="seller-shared.css">
    <style>
        .inventory-container {
            background: #ffffff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
            border: 1px solid #f0f0f0;
            margin-top: 10px;
        }
        .alert {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .alert.success { background: #e6f4ea; color: #137333; border: 1px solid #c2e7cd; }
        .alert.error { background: #fce8e6; color: #c5221f; border: 1px solid #fad2cf; }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .inventory-table th {
            background: #fafafa;
            padding: 14px 16px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            border-bottom: 2px solid #f0f0f0;
        }
        .inventory-table td {
            padding: 16px;
            border-bottom: 1px solid #f5f5f5;
            font-size: 14px;
            vertical-align: top;
        }
        .prod-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .inline-edit-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            box-sizing: border-box;
            background: #fafafa;
            margin-bottom: 6px;
        }
        .inline-edit-input:focus {
            background: #fff;
            border-color: #000;
            outline: none;
        }
        textarea.inline-edit-input {
            min-height: 65px;
            resize: vertical;
        }
        .btn-action {
            padding: 8px 14px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            display: inline-block;
            text-decoration: none;
            transition: all 0.2s;
            text-align: center;
        }
        .btn-save { background: #000000; color: #ffffff; }
        .btn-save:hover { background: #333333; }
        .btn-delete { background: #fce8e6; color: #c5221f; margin-top: 6px; }
        .btn-delete:hover { background: #c5221f; color: #ffffff; }
    </style>
</head>
<body>

  <div class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-name">Ubuntu Market</div>
      <div class="brand-sub">Seller Panel</div>
    </div>
    
    <div class="seller-avatar">
      <div class="avatar-circle" id="sb-avatar">S</div>
      <div class="avatar-info">
        <div class="av-name" id="sb-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Seller Account'); ?></div>
        <div class="av-role">Seller</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Main</div>
      <ul>
        <li><a href="sellerDash.html"> Dashboard</a></li>
        <li><a href="addProd.html">Add Product</a></li>
        <li><a href="ManageProd.php" class="active"> Manage Products</a></li>
      </ul>
      
      <div class="nav-section-label">Operations</div>
      <ul>
        <li><a href="Inventory.html"> Inventory</a></li>
        <li><a href="sellerOrders.html"> Orders</a></li>
        <li><a href="sellerMess.html"> Messages</a></li>
      </ul>

      <div class="nav-section-label">Account</div>
      <ul>
        <li><a href="Sellerprofile.html"> Profile</a></li>
        <li><a href="help.php">Help</a></li>
        <li><a href="logout.php" style="color: #ff4444;">Logout</a></li>
        
      </ul>
    </nav>
  </div>

  <div class="main-content">
    <div class="page-body">
      
      <div class="topbar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div class="topbar-left">
          <h1 style="margin: 0;">Manage Your Catalog</h1>
          <p style="margin: 4px 0 0 0; color: #666;">Modify product details, change tags, or remove items instantly.</p>
        </div>
        <a href="AddProd.html" class="btn-action btn-save" style="text-decoration: none; padding: 12px 20px;">+ Add New Product</a>
      </div>

      <?php echo $status_message; ?>

      <div class="inventory-container">
        <table class="inventory-table">
          <thead>
            <tr>
              <th style="width: 80px;">Thumbnail</th>
              <th style="width: 260px;">Product Name & Price</th>
              <th>Marketplace Description</th>
              <th style="width: 160px;">Category</th>
              <th style="width: 140px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result->num_rows === 0): ?>
              <tr>
                <td colspan="5" style="text-align: center; color: #999; padding: 40px 0;">
                  You have no active listings. Click "+ Add New Product" above to list one.
                </td>
              </tr>
            <?php else: ?>
              <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                  <td>
                    <img src="images/<?php echo htmlspecialchars($row['image'] ?? 'placeholder.jpg'); ?>" 
                         class="prod-thumb" 
                         onerror="this.src='images/placeholder.jpg'">
                  </td>
                  
                  <form method="POST" action="ManageProd.php">
                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                    
                    <td>
                      <label style="font-size:10px; font-weight:700; color:#999; display:block; margin-bottom:2px;">PRODUCT TITLE</label>
                      <input type="text" name="name" class="inline-edit-input" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                      
                      <label style="font-size:10px; font-weight:700; color:#999; display:block; margin-bottom:2px; margin-top:6px;">PRICE (ZAR)</label>
                      <input type="number" step="0.01" name="price" class="inline-edit-input" value="<?php echo htmlspecialchars($row['price']); ?>" required>
                    </td>
                    
                    <td>
                      <label style="font-size:10px; font-weight:700; color:#999; display:block; margin-bottom:2px;">DESCRIPTION BLURB</label>
                      <textarea name="description" class="inline-edit-input" required><?php echo htmlspecialchars($row['description'] ?? ''); ?></textarea>
                    </td>
                    
                    <td>
                      <label style="font-size:10px; font-weight:700; color:#999; display:block; margin-bottom:2px;">STORE SHELF</label>
                      <select name="category" class="inline-edit-input">
                        <option value="clothing" <?php echo ($row['category'] ?? '') == 'clothing' ? 'selected' : ''; ?>>Clothing</option>
                        <option value="electronics" <?php echo ($row['category'] ?? '') == 'electronics' ? 'selected' : ''; ?>>Electronics</option>
                        <option value="shoes" <?php echo ($row['category'] ?? '') == 'shoes' ? 'selected' : ''; ?>>Shoes</option>
                        <option value="furniture" <?php echo ($row['category'] ?? '') == 'furniture' ? 'selected' : ''; ?>>Furniture</option>
                        <option value="other" <?php echo ($row['category'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                      </select>
                    </td>
                    
                    <td>
                      <button type="submit" name="action_update" class="btn-action btn-save" style="width: 100%;">Save Changes</button>
                      <a href="ManageProd.php?delete=<?php echo $row['id']; ?>" 
                         class="btn-action btn-delete" 
                         style="width: 100%; box-sizing: border-box;"
                         onclick="return confirm('Are you sure you want to delete \'<?php echo htmlspecialchars($row['name']); ?>\' permanently?')">
                        Delete Item
                      </a>
                    </td>
                  </form>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
<footer class="seller-footer">
  <p>&copy; 2026 Ubuntu Market &nbsp;|&nbsp;
    <a href="about.html">About</a>
    <a href="contact.html">Contact</a>
  </p>
</footer>

<script>
function loadSidebarProfile() {
  fetch('getProfile.php')
    .then(response => response.json())
    .then(data => {
      //  redirects to login if user session is expired
      if (!data || data.status === 'login' || !data.name) {
        window.location.href = 'login.html';
        return;
      }
      
      // Update sidebar details text fields dynamically 
      document.getElementById('sb-name').innerText = data.name;
      
      // Safely grab and format the real avatar initials
      document.getElementById('sb-avatar').innerText = data.name.charAt(0).toUpperCase();
    })
    .catch(err => {
      console.log("Could not load dynamic sidebar credentials.");
    });
}

// Fire profile initialization cycle instantly on document layout completion
loadSidebarProfile();
</script>
</body>
</html>