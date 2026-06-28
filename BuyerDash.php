<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'buyer') {
    header("Location: login.html?auth=required");
    exit();
}

// Extract the logged-in session name to render instantly into the HTML template
$sessionName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Buyer';
$avatarLetter = strtoupper(substr($sessionName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buyer Dashboard | Ubuntu Market</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="buyer-shared.css">
</head>
<body>

<div class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-name">Ubuntu Market</div>
    <div class="brand-sub">Buyer Panel</div>
  </div>
  <div class="buyer-avatar">
    <div class="avatar-circle" id="sb-avatar"><?php echo htmlspecialchars($avatarLetter); ?></div>
    <div class="avatar-info">
      <div class="av-name" id="sb-name"><?php echo htmlspecialchars($sessionName); ?></div>
      <div class="av-role">Buyer</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <ul>
      <li><a href="BuyerDash.php" class="active"> Dashboard</a></li>
      <li><a href="productPage.html"> Shop Products</a></li>
      <li><a href="Shoppingcart.html"> My Cart</a></li>
    </ul>
    <div class="nav-section-label">Account</div>
    <ul>
      <li><a href="buyerOrder.php">My Orders</a></li>
      <li><a href="buyerMess.php"> Messages</a></li>
      <li><a href="BuyerProfile.html"> Profile</a></li>
      <li><a href="help.php">Help</a></li>
    </ul>
  </nav>
  <div class="sidebar-bottom">
    <a href="logout.php">Logout</a>
  </div>
</div>

<div class="main-content">
<div class="page-body">

  <div class="topbar">
    <div class="topbar-left">
      <h1>Buyer Dashboard</h1>
      <p>Welcome back, <span id="buyer-name"><?php echo htmlspecialchars($sessionName); ?></span></p>
    </div>
    <div class="topbar-right">
      <div class="badge-pill" id="member-since"> Member Since 2026</div>
      <div class="badge-pill" id="live-time">—</div>
    </div>
  </div>

  <div class="stat-cards">
    <div class="stat-card">
      <div class="stat-label">Total Orders</div>
      <div class="stat-value blue" id="stat-orders">—</div>
      <div class="stat-sub">All time purchases</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Cart Items</div>
      <div class="stat-value" id="stat-cart">—</div>
      <div class="stat-sub">Ready to checkout</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Spent</div>
      <div class="stat-value green" id="stat-spent">—</div>
      <div class="stat-sub">Across all orders</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pending Orders</div>
      <div class="stat-value orange" id="stat-pending">—</div>
      <div class="stat-sub">Awaiting delivery</div>
    </div>
  </div>

  <div class="quick-actions">
    <a href="productPage.html" class="qa-btn">
      Browse Products
    </a>
    <a href="Shoppingcart.html" class="qa-btn">
      View Cart
    </a>
    <a href="buyerOrder.php" class="qa-btn">
      My Orders
    </a>
    <a href="buyerMess.php" class="qa-btn">
      Messages
    </a>
  </div>

  <div class="panel">
    <div class="panel-header">
      <h2>Recent Orders</h2>
      <a href="buyerOrder.php" class="btn btn-ghost">View All →</a>
    </div>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Product</th>
            <th>Total</th>
            <th>Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="recent-orders">
          <tr><td colspan="5" style="text-align:center;color:#aaa;padding:30px;">Loading orders...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <div class="panel-header">
      <h2>Cart Preview</h2>
      <a href="Shoppingcart.html" class="btn btn-ghost">View Cart →</a>
    </div>
    <div id="cart-preview" style="padding:20px;">
      <p style="color:#aaa;text-align:center;">Loading cart...</p>
    </div>
  </div>

</div>

<footer class="buyer-footer">
  <p>&copy; 2026 Ubuntu Market &nbsp;|&nbsp;
    <a href="about.html">About</a>
    <a href="contact.html">Contact</a>
  </p>
</footer>
</div>

<script>
// Maintains buyer side backups if parameters are used
const params = new URLSearchParams(window.location.search);
let name = params.get('name') || sessionStorage.getItem('buyerName');

if(params.get('name')) {
    sessionStorage.setItem('buyerName', params.get('name'));
}

if (name) {
    document.getElementById('buyer-name').innerText = name;
    document.getElementById('sb-name').innerText    = name;
    document.getElementById('sb-avatar').innerText  = name.charAt(0).toUpperCase();
}

// Live clock (South Africa Standard Time format support)
function updateTime(){
  const now = new Date();
  document.getElementById('live-time').innerText =
    now.toLocaleTimeString('en-ZA',{hour:'2-digit',minute:'2-digit'}) + ' · ' +
    now.toLocaleDateString('en-ZA',{day:'numeric',month:'short'});
}
updateTime();
setInterval(updateTime, 1000);

// Load stats from endpoint
function loadStats(){
  fetch('getBuyerStats.php')
  .then(r => r.json())
  .then(d => {
    if(d.status === 'login'){ window.location.href='login.html'; return; }
    document.getElementById('stat-orders').innerText  = d.orders   ?? 0;
    document.getElementById('stat-cart').innerText    = d.cart     ?? 0;
    document.getElementById('stat-spent').innerText   = 'R' + (d.spent ?? 0).toLocaleString();
    document.getElementById('stat-pending').innerText = d.pending  ?? 0;
    if(d.joined){
      const j = new Date(d.joined);
      document.getElementById('member-since').innerText =
        ' Member Since ' + j.toLocaleDateString('en-ZA',{month:'long',year:'numeric'});
    }
  }).catch(() => {});
}

// Load recent orders from endpoint
function loadRecentOrders(){
  fetch('getBuyerOrders.php?limit=5')
  .then(r => r.json())
  .then(orders => {
    const tbody = document.getElementById('recent-orders');
    if(!orders.length){
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#aaa;padding:30px;">No orders yet — <a href="productPage.html" style="color:#3b82f6;">start shopping!</a></td></tr>';
      return;
    }
    const sc = {pending:'badge-pending',shipped:'badge-shipped',delivered:'badge-delivered',cancelled:'badge-cancelled'};
    tbody.innerHTML = orders.map(o => `
      <tr>
        <td><strong>#${o.id}</strong></td>
        <td>${o.product_name}</td>
        <td><strong>R${parseFloat(o.total).toFixed(2)}</strong></td>
        <td style="color:#aaa;">${new Date(o.created_at).toLocaleDateString('en-ZA')}</td>
        <td><span class="badge ${sc[o.status]||'badge-pending'}">${o.status}</span></td>
      </tr>
    `).join('');
  }).catch(() => {});
}

// Cart preview loader panel
function loadCartPreview(){
  fetch('getCart.php')
  .then(r => r.json())
  .then(items => {
    const div = document.getElementById('cart-preview');
    if(!items.length){
      div.innerHTML = '<p style="color:#aaa;text-align:center;padding:20px;">Your cart is empty. <a href="productPage.html" style="color:#3b82f6;">Browse products</a></p>';
      return;
    }
    div.innerHTML = `
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;">
        ${items.slice(0,4).map(item => `
          <div style="display:flex;gap:12px;align-items:center;background:#f9f9f9;border-radius:10px;padding:12px;">
            <img src="images/${item.image}" onerror="this.src='images/placeholder.jpg'"
                 style="width:50px;height:50px;object-fit:cover;border-radius:7px;">
            <div>
              <div style="font-size:13px;font-weight:600;">${item.name}</div>
              <div style="font-size:13px;color:#16a34a;font-weight:700;">R${parseFloat(item.price).toFixed(2)} x${item.quantity}</div>
            </div>
          </div>
        `).join('')}
      </div>
      ${items.length > 4 ? `<p style="color:#aaa;font-size:13px;margin-top:12px;">+${items.length-4} more items</p>` : ''}
      <div style="margin-top:166px;">
        <a href="Shoppingcart.html" class="btn btn-dark">Go to Cart →</a>
      </div>`;
  }).catch(() => {});
}

// Core initialization triggers
loadStats();
loadRecentOrders();
loadCartPreview();
setInterval(() => { loadStats(); loadRecentOrders(); }, 30000);
</script>
</body>
</html>