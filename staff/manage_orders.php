<?php
require_once '../includes/auth_check.php';
require_once '../config.php';
require_role('staff');

$db = getDB();

// Determine active sub-page/tab (defaults to 'overview')
$tab = $_GET['tab'] ?? 'overview';

// Handle Status Updates (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['order_status'] ?? '');
    $allowedStatuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
    
    if ($orderId > 0 && in_array($newStatus, $allowedStatuses)) {
        $updateStmt = $db->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $orderId]);
        header("Location: orders.php?tab=orders&msg=status_updated");
        exit;
    }
}

// Handle Export Reports (CSV download)
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=comfort_zone_orders_report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID', 'Order Number', 'Customer Name', 'Total (R)', 'Payment Method', 'Status', 'Date Created']);
    
    $exportStmt = $db->query("SELECT id, order_number, customer_name, total, payment_method, order_status, created_at FROM orders ORDER BY created_at DESC");
    while ($row = $exportStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Fetch metrics & data
$pendingOrdersCount = 0;
$lowStockCount = 0;

try {
    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'");
    $pendingOrdersCount = $stmt->fetchColumn() ?: 0;

    $stmt = $db->query("SELECT COUNT(*) FROM products WHERE stock <= 5");
    $lowStockCount = $stmt->fetchColumn() ?: 0;
} catch (Exception $e) {
    // Fallback if tables are structured differently
}

$staffName = $_SESSION['username'] ?? 'Staff';
$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

// Filtering logic for Orders tab
$filterStatus = $_GET['status'] ?? '';
$filterDate = $_GET['date'] ?? '';
$filterCustomer = trim($_GET['customer'] ?? '');

$queryStr = "SELECT * FROM orders WHERE 1=1";
$params = [];

if (!empty($filterStatus)) {
    $queryStr .= " AND order_status = ?";
    $params[] = $filterStatus;
}
if (!empty($filterDate)) {
    $queryStr .= " AND DATE(created_at) = ?";
    $params[] = $filterDate;
}
if (!empty($filterCustomer)) {
    $queryStr .= " AND (customer_name LIKE ? OR order_number LIKE ?)";
    $params[] = "%$filterCustomer%";
    $params[] = "%$filterCustomer%";
}
$queryStr .= " ORDER BY created_at DESC LIMIT 50";

$ordersStmt = $db->prepare($queryStr);
$ordersStmt->execute($params);
$ordersList = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch specific order details if requested via detail view parameter
$selectedOrderDetails = null;
$selectedOrderItems = [];
if (isset($_GET['detail_id'])) {
    $detailId = (int)$_GET['detail_id'];
    $detStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $detStmt->execute([$detailId]);
    $selectedOrderDetails = $detStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selectedOrderDetails) {
        try {
            $itemStmt = $db->prepare("SELECT oi.*, p.name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
            $itemStmt->execute([$detailId]);
            $selectedOrderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $selectedOrderItems = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Portal - Comfort Zone</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Orbitron:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
        --accent: #e67e22;
        --accent-dim: #d35400;
        --bg: #08090f;
        --panel: rgba(255,255,255,0.03);
        --panel-border: rgba(255,255,255,0.07);
        --text-dim: #8a8d9a;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: var(--bg);
        color: #ededf2;
        display: flex;
        min-height: 100vh;
        overflow-x: hidden;
    }

    .tech-bg {
        position: fixed;
        inset: 0;
        z-index: -1;
        background: 
            radial-gradient(circle at 12% 10%, rgba(230,126,34,0.10), transparent 38%),
            radial-gradient(circle at 90% 85%, rgba(88,101,242,0.08), transparent 42%),
            linear-gradient(180deg, #08090f, #0f1119 55%, #08090f);
    }
    .tech-bg::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: 
            linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
        background-size: 46px 46px;
        mask-image: radial-gradient(ellipse 75% 55% at 65% 10%, black 30%, transparent 85%);
    }

    .sidebar {
        width: 264px;
        background: rgba(10,11,18,0.9);
        backdrop-filter: blur(18px) saturate(140%);
        border-right: 1px solid var(--panel-border);
        color: white;
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100vh;
        left: 0;
        top: 0;
        z-index: 100;
        overflow-y: auto;
    }

    .sidebar-brand {
        padding: 26px 24px;
        display: flex;
        align-items: center;
        gap: 13px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        text-decoration: none;
        pointer-events: none;
        cursor: default;
    }

    .sidebar-brand .logo-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--accent), var(--accent-dim));
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 0 0 1px rgba(230,126,34,0.3), 0 8px 20px rgba(230,126,34,0.25);
    }

    .sidebar-brand h3 {
        font-family: 'Orbitron', sans-serif;
        font-size: 0.92rem;
        letter-spacing: 1.2px;
        font-weight: 700;
        color: white;
    }

    .sidebar-brand span {
        display: block;
        font-size: 0.68rem;
        color: var(--text-dim);
        margin-top: 2px;
    }

    .nav-category {
        padding: 22px 24px 8px;
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 1.6px;
        color: #565a6b;
        font-weight: 700;
    }

    .sidebar-menu { list-style: none; padding: 0 12px; flex: 1; }
    .sidebar-menu li { margin-bottom: 3px; }

    .sidebar-menu a {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 11px 15px;
        color: #9195a5;
        text-decoration: none;
        border-radius: 9px;
        font-weight: 500;
        font-size: 0.865rem;
        border: 1px solid transparent;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .sidebar-menu a i { width: 16px; text-align: center; font-size: 0.85rem; opacity: 0.85; }
    .sidebar-menu a:hover { background: rgba(255,255,255,0.035); color: #e2e4ec; }
    .sidebar-menu a.active {
        background: linear-gradient(135deg, rgba(230,126,34,0.14), rgba(230,126,34,0.05));
        color: var(--accent);
        border-color: rgba(230,126,34,0.25);
        font-weight: 600;
    }
    .sidebar-menu hr { border: none; border-top: 1px solid rgba(255,255,255,0.05); margin: 14px 4px; }

    .main-content {
        margin-left: 264px;
        flex: 1;
        padding: 38px 44px 60px;
        max-width: 1240px;
    }

    .top-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 34px;
    }

    .eyebrow {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 1.8px;
        color: var(--accent);
        font-weight: 700;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 7px;
    }
    .eyebrow .dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: #35d07f;
        box-shadow: 0 0 8px #35d07f;
    }

    .top-header h1 {
        font-family: 'Orbitron', sans-serif;
        font-size: 1.55rem;
        font-weight: 700;
        color: #fff;
    }
    .top-header .sub { color: var(--text-dim); font-size: 0.88rem; margin-top: 6px; }

    .top-actions { display: flex; gap: 12px; align-items: center; }

    .staff-badge {
        background: var(--panel);
        border: 1px solid var(--panel-border);
        padding: 9px 16px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.83rem;
        color: #cfd1db;
        display: flex;
        align-items: center;
        gap: 9px;
        backdrop-filter: blur(10px);
    }
    .staff-badge .avatar {
        width: 22px; height: 22px; border-radius: 50%;
        background: linear-gradient(135deg, var(--accent), #b34700);
        display: flex; align-items: center; justify-content: center;
        font-size: 0.68rem; font-weight: 700; color: white;
    }

    .tab-pane { display: none; }
    .tab-pane.active { display: block; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 18px;
        margin-bottom: 26px;
    }

    .stat-card {
        background: var(--panel);
        border: 1px solid var(--panel-border);
        padding: 24px;
        border-radius: 18px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.35);
        backdrop-filter: blur(14px);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .stat-info .label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-dim);
        margin-bottom: 10px;
        font-weight: 600;
    }
    .stat-info .stat-value {
        font-size: 2.1rem;
        font-weight: 800;
        color: #fff;
        font-family: 'Orbitron', sans-serif;
    }
    .stat-info .hint { font-size: 0.75rem; color: #666a78; margin-top: 8px; }

    .stat-icon {
        width: 52px; height: 52px; border-radius: 15px;
        display: flex; align-items: center; justify-content: center; font-size: 20px;
    }
    .stat-icon.orange { background: rgba(230,126,34,0.12); color: var(--accent); border: 1px solid rgba(230,126,34,0.25); }
    .stat-icon.red { background: rgba(231,76,60,0.12); color: #e74c3c; border: 1px solid rgba(231,76,60,0.25); }

    .card {
        background: var(--panel);
        border: 1px solid var(--panel-border);
        padding: 30px 32px;
        border-radius: 18px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.35);
        backdrop-filter: blur(14px);
        margin-bottom: 26px;
    }
    .card .card-head { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
    .card .card-head i { color: var(--accent); font-size: 0.95rem; }
    .card h2 { font-family: 'Orbitron', sans-serif; font-size: 1.08rem; font-weight: 700; color: #fff; }
    .card p { color: var(--text-dim); line-height: 1.65; margin-bottom: 24px; font-size: 0.9rem; max-width: 560px; }

    .filter-bar {
        display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;
    }
    .filter-bar input, .filter-bar select {
        background: rgba(255,255,255,0.04); border: 1px solid var(--panel-border);
        color: #fff; padding: 10px 14px; border-radius: 9px; font-size: 0.85rem; outline: none;
    }
    .btn-filter {
        background: rgba(230,126,34,0.15); color: var(--accent);
        border: 1px solid rgba(230,126,34,0.3); padding: 10px 18px;
        border-radius: 9px; font-weight: 600; cursor: pointer; transition: 0.2s;
    }
    .btn-filter:hover { background: var(--accent); color: white; }

    table { width: 100%; border-collapse: collapse; text-align: left; }
    th, td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.88rem; }
    th { color: var(--text-dim); font-weight: 600; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; background: rgba(255,255,255,0.02); }
    td { color: #d0d2df; }
    tr:hover td { background: rgba(255,255,255,0.015); }

    .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; display: inline-block; text-transform: capitalize; }
    .status-pending { background: rgba(241, 196, 15, 0.15); color: #f39c12; border: 1px solid rgba(241, 196, 15, 0.3); }
    .status-processing { background: rgba(52, 152, 219, 0.15); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.3); }
    .status-shipped { background: rgba(155, 89, 182, 0.15); color: #9b59b6; border: 1px solid rgba(155, 89, 182, 0.3); }
    .status-completed { background: rgba(39, 174, 96, 0.2); color: #27ae60; border: 1px solid rgba(39, 174, 96, 0.4); }
    .status-cancelled { background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }

    .inline-status-form { display: inline-flex; gap: 8px; align-items: center; }
    .inline-status-form select {
        background: rgba(0,0,0,0.3); border: 1px solid var(--panel-border);
        color: #fff; padding: 5px 8px; border-radius: 6px; font-size: 0.78rem;
    }
    .btn-update {
        background: var(--accent); color: white; border: none;
        padding: 5px 10px; border-radius: 6px; font-size: 0.78rem; cursor: pointer; font-weight: 600;
    }

    .modal-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(6px);
        display: flex; align-items: center; justify-content: center; z-index: 1000;
        opacity: 0; pointer-events: none; transition: 0.25s ease;
    }
    .modal-overlay.open { opacity: 1; pointer-events: auto; }
    .modal-card {
        background: #11131d; border: 1px solid var(--panel-border);
        width: 100%; max-width: 650px; border-radius: 18px; padding: 30px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.6); max-height: 90vh; overflow-y: auto;
    }
    .modal-close { float: right; background: none; border: none; color: var(--text-dim); font-size: 1.2rem; cursor: pointer; }
    .modal-close:hover { color: #fff; }

    .nav-links { display: flex; gap: 13px; flex-wrap: wrap; }
    .nav-links a {
        background: rgba(255,255,255,0.03); color: #e2e4ec; padding: 12px 20px;
        border-radius: 11px; text-decoration: none; font-weight: 600; font-size: 0.85rem;
        transition: all 0.25s ease; border: 1px solid var(--panel-border);
        display: inline-flex; align-items: center; gap: 9px;
    }
    .nav-links a i { color: var(--accent); }
    .nav-links a:hover {
        background: linear-gradient(135deg, var(--accent), var(--accent-dim));
        color: white; transform: translateY(-2px); box-shadow: 0 10px 24px rgba(230,126,34,0.35);
    }
</style>
</head>
<body>

    <div class="tech-bg"></div>

    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="logo-icon">🔥</div>
            <div>
                <h3>COMFORT ZONE</h3>
                <span>Staff Portal</span>
            </div>
        </div>

        <div class="nav-category">Main Navigation</div>
        <ul class="sidebar-menu">
            <li><a class="nav-link" data-target="overview"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a class="nav-link" data-target="orders"><i class="fas fa-shipping-fast"></i> Manage Orders</a></li>
            <li><a class="nav-link" data-target="inventory"><i class="fas fa-boxes"></i> Inventory</a></li>
            <li><hr></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-header">
            <div>
                <div class="eyebrow"><span class="dot"></span> Live operations control</div>
                <h1><?php echo htmlspecialchars($greeting); ?>, <?php echo htmlspecialchars($staffName); ?></h1>
                <div class="sub" id="header-subtitle">Unified operational dashboard for order workflows and inventory.</div>
            </div>
            <div class="top-actions">
                <div class="staff-badge">
                    <div class="avatar"><?php echo strtoupper(substr($staffName, 0, 1)); ?></div>
                    <?php echo htmlspecialchars($staffName); ?>
                </div>
            </div>
        </div>

        <!-- TAB 1: OVERVIEW -->
        <div id="tab-overview" class="tab-pane">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <div class="label">Pending Orders</div>
                        <div class="stat-value"><?= $pendingOrdersCount ?></div>
                        <div class="hint">Awaiting confirmation</div>
                    </div>
                    <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <div class="label">Low Stock Items</div>
                        <div class="stat-value"><?= $lowStockCount ?></div>
                        <div class="hint">5 units or fewer remaining</div>
                    </div>
                    <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><i class="fas fa-bolt"></i><h2>Control Center Modules</h2></div>
                <p>Manage daily store operations, filter order pipelines, update delivery status, and stay ahead of low inventory levels all from one cohesive hub.</p>
                <div class="nav-links">
                    <a href="#" onclick="switchTab('orders', true)"><i class="fas fa-clipboard-list"></i> Manage Customer Orders</a>
                    <a href="#" onclick="switchTab('inventory', true)"><i class="fas fa-box-open"></i> Inventory &amp; Stock Tracking</a>
                    <a href="orders.php?action=export_csv" style="background: rgba(46, 204, 113, 0.15); border-color: rgba(46, 204, 113, 0.3); color: #2ecc71;"><i class="fas fa-file-excel" style="color: #2ecc71;"></i> Export Reports (CSV)</a>
                </div>
            </div>
        </div>

        <!-- TAB 2: ORDERS -->
        <div id="tab-orders" class="tab-pane">
            <div class="card" style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                    <div class="card-head" style="margin-bottom:0;"><i class="fas fa-shipping-fast"></i><h2>Order Management &amp; Workflow</h2></div>
                    <div>
                        <a href="orders.php?action=export_csv" class="btn-filter" style="text-decoration:none; display:inline-block;"><i class="fas fa-download"></i> Export Orders to CSV</a>
                    </div>
                </div>

                <form method="GET" action="orders.php" class="filter-bar">
                    <input type="hidden" name="tab" value="orders">
                    <input type="text" name="customer" placeholder="Search customer or order #" value="<?= htmlspecialchars($filterCustomer) ?>">
                    <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>">
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $filterStatus === 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="shipped" <?= $filterStatus === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Apply Filters</button>
                    <?php if(!empty($filterStatus) || !empty($filterDate) || !empty($filterCustomer)): ?>
                        <a href="orders.php?tab=orders" style="color:var(--text-dim); font-size:0.85rem; text-decoration:underline;">Reset</a>
                    <?php endif; ?>
                </form>

                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer Info</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ordersList)): ?>
                                <tr><td colspan="7" style="text-align:center; color: var(--text-dim); padding: 30px;">No matching order records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($ordersList as $ord): ?>
                                    <tr>
                                        <td><strong><a href="orders.php?tab=orders&detail_id=<?= $ord['id'] ?>" style="color: var(--accent); text-decoration:none;"><?= htmlspecialchars($ord['order_number']) ?></a></strong></td>
                                        <td><?= htmlspecialchars($ord['customer_name'] ?? 'Guest Customer') ?></td>
                                        <td>R<?= number_format($ord['total'], 2) ?></td>
                                        <td style="text-transform: capitalize;"><?= htmlspecialchars($ord['payment_method'] ?? 'Online') ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $ord['order_status'] ?>">
                                                <?= ucfirst($ord['order_status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d M Y, H:i', strtotime($ord['created_at'])) ?></td>
                                        <td>
                                            <form method="POST" action="orders.php?tab=orders" class="inline-status-form">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                                <select name="order_status">
                                                    <option value="pending" <?= $ord['order_status']=='pending'?'selected':'' ?>>Pending</option>
                                                    <option value="processing" <?= $ord['order_status']=='processing'?'selected':'' ?>>Processing</option>
                                                    <option value="shipped" <?= $ord['order_status']=='shipped'?'selected':'' ?>>Shipped</option>
                                                    <option value="completed" <?= $ord['order_status']=='completed'?'selected':'' ?>>Completed</option>
                                                    <option value="cancelled" <?= $ord['order_status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                                                </select>
                                                <button type="submit" class="btn-update">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: INVENTORY -->
        <div id="tab-inventory" class="tab-pane">
            <div class="card" style="padding: 24px;">
                <div class="card-head"><i class="fas fa-boxes"></i><h2>Inventory &amp; Stock Levels</h2></div>
                <p>Track store products and monitor low threshold components.</p>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock Quantity</th>
                                <th>Stock Condition</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $invStmt = $db->query("SELECT * FROM products ORDER BY stock ASC");
                            $inventoryItems = $invStmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($inventoryItems as $item):
                                $isLow = $item['stock'] <= 5;
                            ?>
                                <tr <?= $isLow ? 'style="background: rgba(231,76,60,0.04);"' : '' ?>>
                                    <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($item['category'] ?? 'General') ?></td>
                                    <td>R<?= number_format($item['price'], 2) ?></td>
                                    <td><?= $item['stock'] ?></td>
                                    <td>
                                        <?php if ($isLow): ?>
                                            <span class="status-badge status-cancelled">⚠️ Low Stock</span>
                                        <?php else: ?>
                                            <span class="status-badge status-completed">Optimal</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal Popup -->
    <div class="modal-overlay <?= $selectedOrderDetails ? 'open' : '' ?>" id="orderDetailModal">
        <div class="modal-card">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <?php if ($selectedOrderDetails): ?>
                <h2 style="font-family:'Orbitron'; margin-bottom: 15px; color: #fff;">Order #<?= htmlspecialchars($selectedOrderDetails['order_number']) ?></h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <div>
                        <div style="color:var(--text-dim); font-size: 0.75rem; text-transform: uppercase;">Customer Information</div>
                        <div style="font-weight: 600; margin-top: 4px;"><?= htmlspecialchars($selectedOrderDetails['customer_name'] ?? 'N/A') ?></div>
                        <div style="font-size: 0.85rem; color: #aaa; margin-top: 2px;"><?= htmlspecialchars($selectedOrderDetails['shipping_address'] ?? 'No address provided') ?></div>
                    </div>
                    <div>
                        <div style="color:var(--text-dim); font-size: 0.75rem; text-transform: uppercase;">Payment & Status</div>
                        <div style="font-weight: 600; margin-top: 4px; text-transform: capitalize;"><?= htmlspecialchars($selectedOrderDetails['payment_method']) ?></div>
                        <div style="margin-top: 5px;"><span class="status-badge status-<?= $selectedOrderDetails['order_status'] ?>"><?= ucfirst($selectedOrderDetails['order_status']) ?></span></div>
                    </div>
                </div>

                <h4 style="margin-bottom: 10px; font-size: 0.9rem; color: var(--accent);">Ordered Line Items</h4>
                <table>
                    <thead><tr><th>Item / Product</th><th>Qty</th><th>Price</th></tr></thead>
                    <tbody>
                        <?php if (empty($selectedOrderItems)): ?>
                            <tr><td colspan="3" style="color:var(--text-dim);">Detailed item rows recorded under order total: R<?= number_format($selectedOrderDetails['total'], 2) ?></td></tr>
                        <?php else: ?>
                            <?php foreach($selectedOrderItems as $oi): ?>
                                <tr>
                                    <td><?= htmlspecialchars($oi['name'] ?? 'Product #' . $oi['product_id']) ?></td>
                                    <td><?= $oi['quantity'] ?></td>
                                    <td>R<?= number_format($oi['price'] ?? 0, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div style="text-align: right; margin-top: 15px; font-family: 'Orbitron'; font-weight: bold; font-size: 1.1rem;">
                    Total: R<?= number_format($selectedOrderDetails['total'], 2) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
const navLinks = document.querySelectorAll('.nav-link');
const tabPanes = document.querySelectorAll('.tab-pane');
const subTitle = document.getElementById('header-subtitle');

const subtitles = {
    overview: 'Unified operational dashboard for order workflows and inventory.',
    orders: 'View, filter, update statuses, and export customer orders.',
    inventory: 'Monitor stock levels and manage operational thresholds.'
};

function switchTab(targetId, updateHistory = true) {
    navLinks.forEach(link => {
        if(link.getAttribute('data-target') === targetId) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });

    tabPanes.forEach(pane => {
        if(pane.id === 'tab-' + targetId) {
            pane.classList.add('active');
        } else {
            pane.classList.remove('active');
        }
    });

    if(subtitles[targetId]) {
        subTitle.textContent = subtitles[targetId];
    }

    if (updateHistory) {
        const url = new URL(window.location);
        url.searchParams.set('tab', targetId);
        window.history.pushState({ tab: targetId }, '', url);
    }
}

navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const target = link.getAttribute('data-target');
        switchTab(target, true);
    });
});

window.addEventListener('popstate', (e) => {
    const params = new URLSearchParams(window.location.search);
    const activeTab = params.get('tab') || 'overview';
    switchTab(activeTab, false);
});

window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const initialTab = params.get('tab') || '<?php echo htmlspecialchars($tab); ?>';
    switchTab(initialTab, false);
});

function closeModal() {
    const modal = document.getElementById('orderDetailModal');
    if (modal) {
        modal.classList.remove('open');
    }
}
</script>
</body>
</html>