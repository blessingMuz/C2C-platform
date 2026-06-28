<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders | Ubuntu Market</title>
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
    <div class="avatar-circle" id="sb-avatar">B</div>
    <div class="avatar-info">
      <div class="av-name" id="sb-name">Buyer</div>
      <div class="av-role">Buyer</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <ul>
      <li><a href="BuyerDash.php">Dashboard</a></li>
      <li><a href="productPage.html">Shop Products</a></li>
      <li><a href="Shoppingcart.html"> My Cart</a></li>
    </ul>
    <div class="nav-section-label">Account</div>
    <ul>
      <li><a href="buyerOrder.php" class="active"> My Orders</a></li>
      <li><a href="buyerMess.php"> Messages</a></li>
      <li><a href="BuyerProfile.html"> Profile</a></li>
      <li><a href="help.php">Help</a></li>
    </ul>
  </nav>
  <div class="sidebar-bottom">
    <a href="logout.php"> Logout</a>
  </div>
</div>

<div class="main-content">
<div class="page-body">

  <div class="topbar">
    <div class="topbar-left">
      <h1>My Orders</h1>
      <p>Track all your purchases</p>
    </div>
    <div class="topbar-right">
      <div class="badge-pill" id="order-count">Loading...</div>
    </div>
  </div>

  <div class="stat-cards">
    <div class="stat-card">
      <div class="stat-label">Total Orders</div>
      <div class="stat-value" id="total-orders">—</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pending</div>
      <div class="stat-value orange" id="pending-count">—</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Shipped</div>
      <div class="stat-value blue" id="shipped-count">—</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Delivered</div>
      <div class="stat-value green" id="delivered-count">—</div>
    </div>
  </div>

  <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
    <button class="btn btn-dark"  onclick="filterOrders('all')"       id="tab-all">All</button>
    <button class="btn btn-ghost" onclick="filterOrders('pending')"   id="tab-pending">Pending</button>
    <button class="btn btn-ghost" onclick="filterOrders('shipped')"   id="tab-shipped">Shipped</button>
    <button class="btn btn-ghost" onclick="filterOrders('delivered')" id="tab-delivered">Delivered</button>
    <button class="btn btn-ghost" onclick="filterOrders('cancelled')" id="tab-cancelled">Cancelled</button>
  </div>

  <div class="panel">
    <div class="panel-header">
      <h2>Order History</h2>
      <div class="search-box" style="margin:0;width:220px;">
        <span class="s-icon"></span>
        <input type="text" id="order-search" placeholder="Search orders..."
               oninput="searchOrders()" style="padding:8px 8px 8px 34px;">
      </div>
    </div>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Product</th>
            <th>Qty</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="orders-body">
          <tr><td colspan="8" style="text-align:center;color:#aaa;padding:30px;">Loading orders...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<div id="order-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;padding:30px;width:500px;max-width:95vw;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
      <h2 style="font-family:'Bebas Neue',sans-serif;font-size:28px;" id="modal-title">Order Details</h2>
      <button onclick="closeModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#888;">✕</button>
    </div>
    <div id="modal-body"></div>
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
let allOrders    = [];
let activeFilter = 'all';

// Dynamic Profile Sync Logic Engine
function loadSidebarProfile() {
  fetch('getProfile.php')
    .then(response => response.json())
    .then(data => {
      if (!data || data.status === 'login' || !data.name) {
        window.location.href = 'login.html';
        return;
      }
      document.getElementById('sb-name').innerText = data.name;
      document.getElementById('sb-avatar').innerText = data.name.charAt(0).toUpperCase();
    })
    .catch(err => {
      // fallback to session data if system network drops
      const fallbackName = sessionStorage.getItem('name') || 'Buyer';
      document.getElementById('sb-name').innerText = fallbackName;
      document.getElementById('sb-avatar').innerText = fallbackName.charAt(0).toUpperCase();
    });
}

function loadOrders(){
  fetch('getBuyerOrders.php')
  .then(r => r.json())
  .then(orders => {
    if(orders.status === 'login'){ window.location.href='login.html'; return; }
    allOrders = orders;

    const total     = orders.length;
    const pending   = orders.filter(o => o.status==='pending').length;
    const shipped   = orders.filter(o => o.status==='shipped').length;
    const delivered = orders.filter(o => o.status==='delivered').length;

    document.getElementById('total-orders').innerText   = total;
    document.getElementById('pending-count').innerText  = pending;
    document.getElementById('shipped-count').innerText  = shipped;
    document.getElementById('delivered-count').innerText = delivered;
    document.getElementById('order-count').innerText    = ` ${total} Orders`;

    renderOrders(filterByStatus(orders, activeFilter));
  }).catch(() => {});
}

function filterByStatus(orders, status){
  return status === 'all' ? orders : orders.filter(o => o.status === status);
}

function filterOrders(status){
  activeFilter = status;
  document.querySelectorAll('[id^="tab-"]').forEach(b => b.className = 'btn btn-ghost');
  document.getElementById('tab-' + status).className = 'btn btn-dark';
  renderOrders(filterByStatus(allOrders, status));
}

function searchOrders(){
  const q = document.getElementById('order-search').value.toLowerCase();
  renderOrders(filterByStatus(allOrders, activeFilter)
    .filter(o => o.product_name.toLowerCase().includes(q) || String(o.id).includes(q)));
}

const sc = {pending:'badge-pending',shipped:'badge-shipped',delivered:'badge-delivered',cancelled:'badge-cancelled'};

function renderOrders(orders){
  const tbody = document.getElementById('orders-body');
  if(!orders.length){
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#aaa;padding:30px;">No orders found.</td></tr>';
    return;
  }
  tbody.innerHTML = orders.map(o => {
    const canCancel = o.status === 'pending';
    return `
      <tr>
        <td><strong>#${o.id}</strong></td>
        <td>${o.product_name}</td>
        <td>${o.quantity}</td>
        <td><strong>R${parseFloat(o.total).toFixed(2)}</strong></td>
        <td><span class="badge ${o.payment_status==='paid'?'badge-paid':'badge-unpaid'}">${o.payment_method||'—'}</span></td>
        <td style="color:#aaa;">${new Date(o.created_at).toLocaleDateString('en-ZA')}</td>
        <td><span class="badge ${sc[o.status]||'badge-pending'}">${o.status}</span></td>
        <td>
          <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <button class="btn btn-dark" onclick="viewOrder(${o.id})" style="font-size:12px;padding:6px 10px;">View</button>
            ${canCancel ? `<button class="btn btn-red" onclick="cancelOrder(${o.id})" style="font-size:12px;padding:6px 10px;">Cancel</button>` : ''}
          </div>
        </td>
      </tr>`;
  }).join('');
}

function viewOrder(id){
  const o = allOrders.find(x => x.id == id);
  if(!o) return;
  document.getElementById('modal-title').innerText = 'Order #' + o.id;
  document.getElementById('modal-body').innerHTML = `
    <div style="display:flex;gap:14px;align-items:center;background:#f9f9f9;border-radius:10px;padding:14px;margin-bottom:16px;">
      <img src="images/${o.image||''}" onerror="this.src='images/placeholder.jpg'"
           style="width:60px;height:60px;object-fit:cover;border-radius:8px;">
      <div>
        <div style="font-weight:700;font-size:15px;">${o.product_name}</div>
        <div style="color:#16a34a;font-weight:700;">R${parseFloat(o.total).toFixed(2)}</div>
      </div>
    </div>
    <div style="display:grid;gap:10px;font-size:14px;">
      <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0;">
        <span style="color:#888;">Status</span>
        <span class="badge ${sc[o.status]||'badge-pending'}">${o.status}</span>
      </div>
      <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0;">
        <span style="color:#888;">Payment</span>
        <span style="font-weight:600;text-transform:capitalize;">${o.payment_method||'—'}</span>
      </div>
      <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0;">
        <span style="color:#888;">Quantity</span>
        <span style="font-weight:600;">x${o.quantity}</span>
      </div>
      <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0;">
        <span style="color:#888;">Order Date</span>
        <span style="font-weight:600;">${new Date(o.created_at).toLocaleDateString('en-ZA',{day:'numeric',month:'long',year:'numeric'})}</span>
      </div>
      <div style="display:flex;justify-content:space-between;padding:10px 0;">
        <span style="color:#888;">Delivery Address</span>
        <span style="font-weight:600;text-align:right;">${o.city||'—'}${o.province?', '+o.province:''}</span>
      </div>
    </div>
    <div style="margin-top:20px;">
      <a href="buyerMess.php?seller_id=${o.seller_id}&product_id=${o.product_id}"
         class="btn btn-dark" style="width:100%;text-align:center;display:block;box-sizing:border-box;">💬 Message Seller</a>
    </div>`;
  document.getElementById('order-modal').style.display = 'flex';
}

function closeModal(){
  document.getElementById('order-modal').style.display = 'none';
}

function cancelOrder(id){
  if(!confirm('Cancel this order?')) return;
  fetch('cancelOrder.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'order_id=' + id
  })
  .then(r => r.json())
  .then(d => { if(d.status==='success') loadOrders(); });
}

// Initialization 
loadSidebarProfile();
loadOrders();
setInterval(loadOrders, 30000);
</script>
</body>
</html>