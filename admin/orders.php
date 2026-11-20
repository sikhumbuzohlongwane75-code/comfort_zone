<?php
require_once '../config.php';
requireAdmin();

$pdo = getDB();

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['order_status'];
    $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    $message = "Order status updated";
}

// Get orders
$stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #fef8f0; }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #1a1a2e; color: white; padding: 2rem; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar h3 { margin-bottom: 2rem; color: #e67e22; }
        .sidebar nav ul { list-style: none; }
        .sidebar nav ul li { margin-bottom: 1rem; }
        .sidebar nav ul li a { color: #aaa; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; transition: all 0.3s; }
        .sidebar nav ul li a:hover, .sidebar nav ul li a.active { background: #e67e22; color: white; }
        .main-content { flex: 1; margin-left: 280px; padding: 2rem; }
        .card { background: white; border-radius: 20px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #cce5ff; color: #004085; }
        .status-preparing { background: #d4edda; color: #155724; }
        .status-ready { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #27ae60; color: white; }
        .status-cancelled { background: #e74c3c; color: white; }
        select { padding: 5px 10px; border-radius: 5px; border: 1px solid #ddd; }
        .btn { padding: 5px 10px; border-radius: 5px; border: none; cursor: pointer; }
        .btn-primary { background: #e67e22; color: white; }
        .message { padding: 1rem; border-radius: 10px; margin-bottom: 1rem; background: #d4edda; color: #155724; }
        @media (max-width: 768px) { .sidebar { width: 100%; position: relative; height: auto; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="sidebar"><h3><i class="fas fa-fire"></i> Comfort Zone Admin</h3>
        <nav><ul>
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-utensils"></i> Products</a></li>
            <li><a href="orders.php" class="active"><i class="fas fa-truck"></i> Orders</a></li>
            <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Site</a></li>
        </ul></nav>
    </div>
    <div class="main-content">
        <h2 style="margin-bottom: 2rem;">Manage Orders</h2>
        <?php if (isset($message)): ?><div class="message"><?php echo $message; ?></div><?php endif; ?>
        <div class="card"><h3>All Orders</h3>
            <table><thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Type</th><th>Status</th><th>Date</th><th>Action</th></tr></thead><tbody>
            <?php foreach ($orders as $order): $items = json_decode($order['items'], true); ?>
            <tr><td><?php echo $order['order_number']; ?></td><td><?php echo htmlspecialchars($order['customer_name']); ?><br><small><?php echo $order['customer_phone']; ?></small></td><td>R<?php echo number_format($order['total'], 2); ?></td><td><?php echo ucfirst($order['order_type']); ?></td>
            <td><span class="status-badge status-<?php echo $order['order_status']; ?>"><?php echo ucfirst($order['order_status']); ?></span></td>
            <td><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></td>
            <td><form method="POST" style="display:flex;gap:5px;"><input type="hidden" name="order_id" value="<?php echo $order['id']; ?>"><select name="order_status"><option value="pending" <?php echo $order['order_status']=='pending'?'selected':''; ?>>Pending</option><option value="confirmed" <?php echo $order['order_status']=='confirmed'?'selected':''; ?>>Confirmed</option><option value="preparing" <?php echo $order['order_status']=='preparing'?'selected':''; ?>>Preparing</option><option value="ready" <?php echo $order['order_status']=='ready'?'selected':''; ?>>Ready</option><option value="delivered" <?php echo $order['order_status']=='delivered'?'selected':''; ?>>Delivered</option><option value="cancelled" <?php echo $order['order_status']=='cancelled'?'selected':''; ?>>Cancelled</option></select><button type="submit" name="update_status" class="btn btn-primary">Update</button></form></td></tr>
            <tr style="background:#f9f9f9;"><td colspan="7"><strong>Items:</strong> <?php foreach ($items as $item): echo $item['quantity'] . 'x ' . htmlspecialchars($item['name']) . ', '; endforeach; ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </div>
</div>
</body>
</html>