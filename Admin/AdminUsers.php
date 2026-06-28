<?php
require_once 'auth.php';
requireAdmin();
require_once 'db.php';

$message = '';

// Handles POST actions 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        switch ($action) {

            case 'block':
                $pdo->prepare("UPDATE users SET status='blocked' WHERE id=?")->execute([$userId]);
                logAction($pdo, 'blocked_user', 'user', $userId);
                $message = 'User blocked.';
                break;

            case 'unblock':
                $pdo->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$userId]);
                logAction($pdo, 'unblocked_user', 'user', $userId);
                $message = 'User unblocked.';
                break;

            case 'delete':
                // Only super-admins can delete
                if (isSuperAdmin()) {
                    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$userId]);
                    logAction($pdo, 'deleted_user', 'user', $userId);
                    $message = 'User deleted.';
                } else {
                    $message = 'Only super-admins can delete users.';
                }
                break;

            case 'approve':
                $pdo->prepare("UPDATE users SET status='active' WHERE id=? AND status='pending'")->execute([$userId]);
                logAction($pdo, 'approved_user', 'user', $userId);
                $message = 'User approved.';
                break;
        }
    }
}


$search = trim($_GET['q'] ?? '');
$role   = $_GET['role']   ?? '';
$status = $_GET['status'] ?? '';

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($role)   { $where[] = "role = ?";   $params[] = $role; }
if ($status) { $where[] = "status = ?"; $params[] = $status; }

$sql   = "SELECT * FROM users WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
$stmt  = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users | Ubuntu Market Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root { --bg:#f4f4f5; --sidebar:#0f0f0f; --accent:#f97316; --card:#fff; --muted:#888; }
    body { font-family: 'DM Sans', sans-serif; background: var(--bg); }
    
    .sidebar { width:240px; height:100vh; background:var(--sidebar); color:#fff; position:fixed; top:0; left:0; display:flex; flex-direction:column; padding:24px 16px; }
    .sidebar-logo { font-family:'Bebas Neue',sans-serif; font-size:22px; letter-spacing:2px; color:var(--accent); margin-bottom:4px; }
    .sidebar-sub  { font-size:11px; color:#666; margin-bottom:32px; }
    .sidebar nav ul { list-style:none; }
    .sidebar nav li { margin:4px 0; }
    .sidebar nav a { display:flex; align-items:center; gap:10px; color:#ccc; text-decoration:none; padding:10px 12px; border-radius:8px; font-size:14px; font-weight:500; transition:all 0.2s; }
    .sidebar nav a:hover, .sidebar nav a.active { background:rgba(249,115,22,0.15); color:var(--accent); }
    .sidebar-footer { margin-top:auto; padding-top:20px; border-top:1px solid #222; }
    .sidebar-footer a { color:#888; text-decoration:none; font-size:13px; }
    
    .main { margin-left:240px; padding:30px; }
    .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
    .topbar h1 { font-family:'Bebas Neue',sans-serif; font-size:32px; letter-spacing:1px; }
    
    .filters { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
    .filters input, .filters select {
      padding:10px 14px; border:1.5px solid #e0e0e0; border-radius:8px;
      font-family:inherit; font-size:14px; outline:none;
      background:#fff;
    }
    .filters input:focus, .filters select:focus { border-color:#111; }
    .btn-search {
      padding:10px 20px; background:#111; color:#fff;
      border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:14px;
    }
    .btn-search:hover { background:var(--accent); }
    
    .alert { padding:10px 16px; border-radius:8px; margin-bottom:16px; font-size:14px; font-weight:500; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
    
    .panel { background:var(--card); border-radius:12px; box-shadow:0 1px 6px rgba(0,0,0,0.07); overflow:hidden; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    th { background:#f4f4f5; padding:12px 14px; text-align:left; font-size:11px; text-transform:uppercase; color:var(--muted); font-weight:700; }
    td { padding:12px 14px; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
    tr:last-child td { border-bottom:none; }
    .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .badge-active  { background:#dcfce7; color:#15803d; }
    .badge-blocked { background:#fee2e2; color:#dc2626; }
    .badge-pending { background:#fef3c7; color:#92400e; }
    .btn { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; transition:opacity 0.2s; }
    .btn:hover { opacity:0.8; }
    .btn-block   { background:#fee2e2; color:#dc2626; }
    .btn-unblock { background:#dcfce7; color:#15803d; }
    .btn-approve { background:#e0f2fe; color:#0369a1; }
    .btn-delete  { background:#f4f4f5; color:#555; }
    @media(max-width:768px){ .sidebar{display:none;} .main{margin-left:0;padding:16px;} }
  </style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-logo">Ubuntu Market</div>
  <div class="sidebar-sub">Admin Control Panel</div>
  <nav><ul>
    <li><a href="AdminDash.php">Dashboard</a></li>
    <li><a href="AdminUsers.php" class="active"> Users</a></li>
    <li><a href="AdminProducts.php">Products</a></li>
    <li><a href="AdminOrders.php">Orders</a></li>
    <li><a href="AdminReviews.php"> Reviews</a></li>
    <li><a href="AdminReports.php">Reports</a></li>
    <li><a href="AdminReplyEmail.php">Ticket Replies</a></li>
  </ul></nav>
  <div class="sidebar-footer"><a href="AdminLogout.php"> Logout</a></div>
</div>

<div class="main">
  <div class="topbar">
    <div>
      <h1>Manage Users</h1>
      <p style="color:var(--muted);font-size:14px;">View, approve, block or remove platform users</p>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="alert"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  
  <form method="GET" class="filters">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or email…">
    <select name="role">
      <option value="">All Roles</option>
      <option value="buyer"  <?= $role==='buyer'  ? 'selected':'' ?>>Buyer</option>
      <option value="seller" <?= $role==='seller' ? 'selected':'' ?>>Seller</option>
    </select>
    <select name="status">
      <option value="">All Statuses</option>
      <option value="active"  <?= $status==='active'  ? 'selected':'' ?>>Active</option>
      <option value="blocked" <?= $status==='blocked' ? 'selected':'' ?>>Blocked</option>
      <option value="pending" <?= $status==='pending' ? 'selected':'' ?>>Pending</option>
    </select>
    <button type="submit" class="btn-search">Search</button>
  </form>

  <div class="panel">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Name / Email</th>
          <th>Role</th>
          <th>Status</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td style="color:var(--muted);">#<?= $u['id'] ?></td>
          <td>
            <strong><?= htmlspecialchars($u['name']) ?></strong><br>
            <small style="color:var(--muted);"><?= htmlspecialchars($u['email']) ?></small>
          </td>
          <td><?= $u['role'] ?></td>
          <td><span class="badge badge-<?= $u['status'] ?>"><?= $u['status'] ?></span></td>
          <td style="color:var(--muted);font-size:12px;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">

              <?php if ($u['status'] === 'pending'): ?>
                <button type="submit" name="action" value="approve" class="btn btn-approve">Approve</button>
              <?php endif; ?>

              <?php if ($u['status'] !== 'blocked'): ?>
                <button type="submit" name="action" value="block"
                        onclick="return confirm('Block this user?')" class="btn btn-block">Block</button>
              <?php else: ?>
                <button type="submit" name="action" value="unblock" class="btn btn-unblock">Unblock</button>
              <?php endif; ?>

              <?php if (isSuperAdmin()): ?>
                <button type="submit" name="action" value="delete"
                        onclick="return confirm('Permanently delete this user?')" class="btn btn-delete">Delete</button>
              <?php endif; ?>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?>
          <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--muted);">No users found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
