<?php
require_once '../config.php';
requireAdmin(); // Ensures only admins can access

$pdo = getDB();

// Determine the current sub-page/tab (defaults to 'overview')
$tab = $_GET['tab'] ?? 'overview';

$adminName = $_SESSION['user_name'] ?? 'Admin';
$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

// ============================================================
// ALL POST/GET HANDLING RUNS HERE, BEFORE ANY HTML IS OUTPUT.
// This is required so header()/redirects work correctly -
// PHP cannot send headers once HTML has already been echoed.
// ============================================================

// --- Inventory: stock update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $stockId  = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $newStock = isset($_POST['new_stock']) ? (int) $_POST['new_stock'] : 0;

    if ($stockId > 0 && $newStock >= 0) {
        try {
            $updateStmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $updateStmt->execute([$newStock, $stockId]);
        } catch (PDOException $e) {
            // Log this in a real app rather than exposing it to the user
            error_log('Stock update failed: ' . $e->getMessage());
        }
    }

    header("Location: dashboard.php?tab=inventory");
    exit;
}

// --- Products: add / edit (save) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $productId   = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $name        = trim($_POST['name'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $price       = isset($_POST['price']) ? (float) $_POST['price'] : 0;
    $description = trim($_POST['description'] ?? '');

    // Handle optional image upload
    $imagePath = null;
    if (!empty($_FILES['product_image']['name']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '/uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext, $allowedExt, true)) {
            $filename = uniqid('prod_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadDir . $filename)) {
                $imagePath = 'uploads/products/' . $filename;
            }
        }
    }

    if ($name !== '' && $category !== '') {
        try {
            if ($productId > 0) {
                if ($imagePath !== null) {
                    $stmt = $pdo->prepare("UPDATE products SET name = ?, category = ?, price = ?, description = ?, image = ? WHERE id = ?");
                    $stmt->execute([$name, $category, $price, $description, $imagePath, $productId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE products SET name = ?, category = ?, price = ?, description = ? WHERE id = ?");
                    $stmt->execute([$name, $category, $price, $description, $productId]);
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO products (name, category, price, description, image, is_available, stock, min_stock_level) VALUES (?, ?, ?, ?, ?, 1, 0, 5)");
                $stmt->execute([$name, $category, $price, $description, $imagePath]);
            }
        } catch (PDOException $e) {
            error_log('Product save failed: ' . $e->getMessage());
        }
    }

    header("Location: dashboard.php?tab=products");
    exit;
}

// --- Products: delete ---
if (isset($_GET['tab']) && $_GET['tab'] === 'products' && isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    if ($deleteId > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$deleteId]);
        } catch (PDOException $e) {
            error_log('Product delete failed: ' . $e->getMessage());
        }
    }
    header("Location: dashboard.php?tab=products");
    exit;
}

// --- Products: load product being edited (for the modal) ---
$editProduct = null;
if (isset($_GET['tab']) && $_GET['tab'] === 'products' && isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    if ($editId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$editId]);
        $editProduct = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Control Center - <?php echo SITE_NAME; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Orbitron:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        background: radial-gradient(circle at 12% 10%, rgba(230,126,34,0.10), transparent 38%), linear-gradient(180deg, #08090f, #0f1119 55%, #08090f);
    }
    .tech-bg::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
        background-size: 46px 46px;
        mask-image: radial-gradient(ellipse 75% 55% at 65% 10%, black 30%, transparent 85%);
    }

    .sidebar {
        width: 264px;
        background: rgba(10,11,18,0.9);
        backdrop-filter: blur(18px);
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
    }

    .sidebar-brand h3 {
        font-family: 'Orbitron', sans-serif;
        font-size: 0.92rem;
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
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .sidebar-menu a:hover { background: rgba(255,255,255,0.035); color: #e2e4ec; }
    .sidebar-menu a.active {
        background: linear-gradient(135deg, rgba(230,126,34,0.14), rgba(230,126,34,0.05));
        color: var(--accent);
        border: 1px solid rgba(230,126,34,0.25);
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

    .btn-store {
        background: linear-gradient(135deg, var(--accent), var(--accent-dim));
        color: white;
        padding: 10px 18px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.83rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.25s ease;
        box-shadow: 0 4px 16px rgba(230,126,34,0.28);
    }
    .btn-store:hover { transform: translateY(-2px); }

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
        font-size: 1.8rem;
        font-weight: 800;
        color: #fff;
        font-family: 'Orbitron', sans-serif;
    }

    .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        background: rgba(230,126,34,0.12);
        color: var(--accent);
        border: 1px solid rgba(230,126,34,0.25);
    }
    .stat-card.alert-card .stat-icon { background: rgba(231,76,60,0.12); color: #e74c3c; border-color: rgba(231,76,60,0.25); }

    .section-card {
        background: var(--panel);
        border: 1px solid var(--panel-border);
        padding: 30px 32px;
        border-radius: 18px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.35);
        backdrop-filter: blur(14px);
        margin-bottom: 26px;
    }
    .section-card h3 {
        font-family: 'Orbitron', sans-serif;
        font-size: 1.08rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-card h3 i { color: var(--accent); }

    table { width: 100%; border-collapse: collapse; text-align: left; }
    th, td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.88rem; }
    th { color: var(--text-dim); font-weight: 600; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; background: rgba(255,255,255,0.02); }
    td { color: #d0d2df; }

    .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; display: inline-block; text-transform: capitalize; }
    .status-pending { background: rgba(241, 196, 15, 0.15); color: #f39c12; border: 1px solid rgba(241, 196, 15, 0.3); }
    .status-confirmed { background: rgba(52, 152, 219, 0.15); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.3); }
    .status-preparing { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); }
    .status-ready { background: rgba(26, 188, 156, 0.15); color: #1abc9c; border: 1px solid rgba(26, 188, 156, 0.3); }
    .status-delivered { background: rgba(39, 174, 96, 0.2); color: #27ae60; border: 1px solid rgba(39, 174, 96, 0.4); }
    .status-cancelled { background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }

    .low-stock-row { background: rgba(231, 76, 60, 0.05); }

    .btn-action {
        background: rgba(230,126,34,0.12);
        color: var(--accent);
        padding: 5px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.78rem;
        font-weight: 600;
        border: 1px solid rgba(230,126,34,0.25);
        display: inline-block;
        cursor: pointer;
    }
    .btn-action:hover { background: var(--accent); color: white; }

    .chart-container { width: 100%; height: 300px; position: relative; }

    .modal-overlay { display: none; }
</style>
</head>
<body>

<div class="tech-bg"></div>

<div class="sidebar">
    <a href="../index.php" class="sidebar-brand">
        <div class="logo-icon">🔥</div>
        <div>
            <h3>COMFORT ZONE</h3>
            <span>Admin Control Center</span>
        </div>
    </a>

    <div class="nav-category">Modules</div>
    <ul class="sidebar-menu">
        <li><a class="nav-link active" data-target="overview"><i class="fas fa-chart-line"></i> Dashboard</a></li>
        <li><a class="nav-link" data-target="products"><i class="fas fa-utensils"></i> Products</a></li>
        <li><a class="nav-link" data-target="orders"><i class="fas fa-truck"></i> Orders</a></li>
        <li><a class="nav-link" data-target="inventory"><i class="fas fa-boxes"></i> Inventory</a></li>
        <li><a class="nav-link" data-target="reports"><i class="fas fa-chart-bar"></i> Reports</a></li>
        <li><hr></li>
        <li><a href="../index.php"><i class="fas fa-store"></i> View Store</a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="top-header">
        <div>
            <div class="eyebrow"><span class="dot"></span> Admin Control Center</div>
            <h1><?php echo htmlspecialchars($greeting); ?>, <?php echo htmlspecialchars($adminName); ?></h1>
            <div class="sub" id="header-subtitle">Performance overview & unified management dashboard.</div>
        </div>
        <div class="top-actions">
            <a href="../index.php" class="btn-store"><i class="fas fa-store"></i> Back to Store</a>
        </div>
    </div>

    <!-- OVERVIEW TAB -->
    <div id="tab-overview" class="tab-pane active">
        <?php
        $totalCustomers = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'")->fetch()['total'];
        $totalOrders = $pdo->query("SELECT COUNT(*) as total FROM orders")->fetch()['total'];
        $totalRevenue = $pdo->query("SELECT SUM(total) as rev FROM orders WHERE order_status != 'cancelled'")->fetch()['rev'] ?? 0;
        $todaySales = $pdo->query("SELECT SUM(total) as sales FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled'")->fetch()['sales'] ?? 0;
        $pendingOrders = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'pending'")->fetch()['total'];
        $lowStockProducts = $pdo->query("SELECT * FROM products WHERE stock <= min_stock_level AND is_available = 1")->fetchAll();
        $recentOrders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();

        $monthlySales = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total) as revenue FROM orders WHERE order_status != 'cancelled' GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month DESC LIMIT 6")->fetchAll();
        $monthlySales = array_reverse($monthlySales);
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info"><div class="label">Total Customers</div><div class="stat-value"><?php echo $totalCustomers; ?></div></div>
                <div class="stat-icon"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><div class="label">Total Orders</div><div class="stat-value"><?php echo $totalOrders; ?></div></div>
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><div class="label">Total Revenue</div><div class="stat-value">R<?php echo number_format($totalRevenue, 2); ?></div></div>
                <div class="stat-icon"><i class="fas fa-rand"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><div class="label">Today's Sales</div><div class="stat-value">R<?php echo number_format($todaySales, 2); ?></div></div>
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><div class="label">Pending Orders</div><div class="stat-value"><?php echo $pendingOrders; ?></div></div>
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-card alert-card">
                <div class="stat-info"><div class="label">Low Stock Items</div><div class="stat-value" style="color:#e74c3c;"><?php echo count($lowStockProducts); ?></div></div>
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>

        <?php if (!empty($lowStockProducts)): ?>
        <div class="section-card">
            <h3><i class="fas fa-exclamation-triangle" style="color:#e74c3c;"></i> Low Stock Warning</h3>
            <table>
                <thead><tr><th>Product</th><th>Current Stock</th><th>Min Level</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($lowStockProducts as $prod): ?>
                    <tr class="low-stock-row">
                        <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                        <td><?php echo $prod['stock']; ?></td>
                        <td><?php echo $prod['min_stock_level']; ?></td>
                        <td><a href="#" onclick="switchTab('inventory'); return false;" class="btn-action">Manage Inventory</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="section-card">
            <h3><i class="fas fa-chart-line"></i> Monthly Revenue Trend</h3>
            <div class="chart-container"><canvas id="revenueChart"></canvas></div>
        </div>
    </div>

    <!-- PRODUCTS TAB -->
    <div id="tab-products" class="tab-pane <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'products') ? 'active' : ''; ?>">
        <div class="section-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin-bottom: 0;"><i class="fas fa-utensils"></i> Product Catalog</h3>
                <!-- Button triggers the modal -->
                <button type="button" class="btn-store" id="openAddModalBtn" style="font-size: 0.78rem; padding: 8px 14px; border:none; cursor:pointer;">
                    <i class="fas fa-plus"></i> Add New Product
                </button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $products = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 15")->fetchAll();
                foreach($products as $p):
                ?>
                    <tr>
                        <td>#<?php echo $p['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                        <td>R<?php echo number_format($p['price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($p['category'] ?? 'General'); ?></td>
                        <td>
                            <span class="status-badge <?php echo $p['is_available'] ? 'status-delivered' : 'status-cancelled'; ?>">
                                <?php echo $p['is_available'] ? 'Available' : 'Unavailable'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="dashboard.php?tab=products&edit=<?php echo $p['id']; ?>" class="btn-store" style="padding: 4px 8px; font-size: 0.7rem; text-decoration:none;">Edit</a>
                            <a href="dashboard.php?tab=products&delete=<?php echo $p['id']; ?>" class="btn-store" style="padding: 4px 8px; font-size: 0.7rem; background:#e74c3c; text-decoration:none;" onclick="return confirm('Delete this product?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- INLINE MODAL FORM OVERLAY -->
    <div class="modal-overlay <?php echo ($editProduct !== null || isset($_GET['add'])) ? 'active' : ''; ?>" id="productModal" style="display: <?php echo ($editProduct !== null || isset($_GET['add'])) ? 'flex' : 'none'; ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center;">
        <div class="section-card" style="width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; background: white; padding: 2rem; position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; color:#1a1a2e;"><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h3>
                <a href="dashboard.php?tab=products" style="text-decoration: none; color: #666; font-size: 1.2rem;"><i class="fas fa-times"></i></a>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo $editProduct['id'] ?? 0; ?>">

                <div style="margin-bottom: 1rem;">
                    <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:#1a1a2e;">Product Name</label>
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:#1a1a2e;">Category</label>
                    <select name="category" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
                        <?php
                        $cats = ['Meat', 'Side Dish', 'Beverage', 'Herbal', 'Grilled special'];
                        foreach($cats as $c) {
                            $selected = (($editProduct['category'] ?? '') == $c) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($c) . "\" $selected>" . htmlspecialchars($c) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:#1a1a2e;">Price (R)</label>
                    <input type="number" step="0.01" name="price" required value="<?php echo $editProduct['price'] ?? ''; ?>" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:#1a1a2e;">Description</label>
                    <textarea name="description" rows="3" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;"><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:#1a1a2e;">Product Image</label>
                    <input type="file" name="product_image" accept="image/*">
                </div>

                <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                    <button type="submit" name="save_product" class="btn-store" style="border:none; cursor:pointer;"><?php echo $editProduct ? 'Update Product' : 'Save Product'; ?></button>
                    <a href="dashboard.php?tab=products" class="btn-store" style="background: #666; text-decoration:none; text-align:center;">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- ORDERS TAB -->
    <div id="tab-orders" class="tab-pane">
        <div class="section-card">
            <h3><i class="fas fa-truck"></i> Full Order Management</h3>
            <table>
                <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php
                $orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 15")->fetchAll();
                foreach($orders as $o):
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                        <td>R<?php echo number_format($o['total'], 2); ?></td>
                        <td style="text-transform:capitalize;"><?php echo $o['payment_method']; ?></td>
                        <td><span class="status-badge status-<?php echo $o['order_status']; ?>"><?php echo ucfirst($o['order_status']); ?></span></td>
                        <td><?php echo date('d M Y, H:i', strtotime($o['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- INVENTORY TAB -->
    <div id="tab-inventory" class="tab-pane">
        <div class="section-card">
            <h3><i class="fas fa-boxes"></i> Inventory Control & Stock Levels</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Min Level</th>
                        <th>Status</th>
                        <th>Update Stock</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $inventory = $pdo->query("SELECT * FROM products ORDER BY stock ASC")->fetchAll();
                foreach($inventory as $inv):
                    $isLow = $inv['stock'] <= $inv['min_stock_level'];
                ?>
                    <tr class="<?php echo $isLow ? 'low-stock-row' : ''; ?>">
                        <td><strong><?php echo htmlspecialchars($inv['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($inv['category'] ?? 'General'); ?></td>
                        <td><strong><?php echo $inv['stock']; ?></strong></td>
                        <td><?php echo $inv['min_stock_level']; ?></td>
                        <td>
                            <?php if($isLow): ?>
                                <span class="status-badge status-pending">⚠️ Critical Low</span>
                            <?php else: ?>
                                <span class="status-badge status-delivered">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="dashboard.php?tab=inventory" style="display: flex; gap: 8px; align-items: center;">
                                <input type="hidden" name="product_id" value="<?php echo $inv['id']; ?>">
                                <input type="number" name="new_stock" value="<?php echo $inv['stock']; ?>" min="0" style="width: 70px; padding: 5px; background: rgba(255,255,255,0.05); border: 1px solid var(--panel-border); color: #fff; border-radius: 6px; text-align: center;">
                                <button type="submit" name="update_stock" class="btn-action" style="border: none; cursor: pointer;">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- JavaScript for Tab Switching, Subtitles & Charts -->
<script>
const navLinks = document.querySelectorAll('.nav-link');
const tabPanes = document.querySelectorAll('.tab-pane');
const subTitle = document.getElementById('header-subtitle');

const subtitles = {
    overview: 'Performance overview & unified management dashboard.',
    products: 'Browse and manage active store merchandise catalog items.',
    orders: 'Inspect, track, and manage client orders across lifecycles.',
    inventory: 'Monitor stock parameters and prevent shortages.',
    reports: 'Enterprise insights, aggregated reporting, and metrics.'
};

function switchTab(targetId, updateHistory = true) {
    // 1. Update active state on nav links
    navLinks.forEach(link => {
        if(link.getAttribute('data-target') === targetId) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });

    // 2. Physically show/hide tab panes
    tabPanes.forEach(pane => {
        if(pane.id === 'tab-' + targetId) {
            pane.classList.add('active');
            pane.style.display = 'block';
        } else {
            pane.classList.remove('active');
            pane.style.display = 'none';
        }
    });

    // 3. Update header subtitle text
    if(subtitles[targetId] && subTitle) {
        subTitle.textContent = subtitles[targetId];
    }

    // 4. Update browser URL history without reloading
    if (updateHistory) {
        const newUrl = window.location.pathname + '?tab=' + targetId;
        window.history.pushState({ tab: targetId }, '', newUrl);
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Bind click events to sidebar nav items
navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const target = link.getAttribute('data-target');
        if (target) {
            switchTab(target);
        }
    });
});

// Handle browser back/forward navigation buttons
window.addEventListener('popstate', (e) => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab') || 'overview';
    switchTab(tabParam, false);
});

// Check URL parameters on initial page load
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab');
    if (initialTab && document.getElementById('tab-' + initialTab)) {
        switchTab(initialTab, false);
    }

    <?php if (isset($editProduct) && $editProduct !== null || isset($_GET['add'])): ?>
    switchTab('products', false);
    <?php endif; ?>
});

// Open/close the add-product modal
const modal = document.getElementById('productModal');
const openBtn = document.getElementById('openAddModalBtn');

if (openBtn && modal) {
    openBtn.addEventListener('click', () => {
        modal.style.display = 'flex';
        modal.classList.add('active');
    });
}

// Chart.js Rendering Initialization
if (typeof Chart !== 'undefined') {
    // Revenue Line Chart (Tab version)
    const trendCtxTab = document.getElementById('revenueTrendChartTab');
    if (trendCtxTab) {
        new Chart(trendCtxTab.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($reportDates ?? []); ?>,
                datasets: [{
                    label: 'Daily Revenue (R)',
                    data: <?php echo json_encode($reportRevenues ?? []); ?>,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39,174,96,0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 5,
                    pointBackgroundColor: '#27ae60'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: '#bbb', font: { family: 'Inter', size: 12 } } } },
                scales: {
                    x: { ticks: { color: '#aaa' }, grid: { color: 'rgba(255,255,255,0.04)' } },
                    y: { ticks: { color: '#aaa' }, grid: { color: 'rgba(255,255,255,0.04)' } }
                }
            }
        });
    }

    // Enterprise Pie Chart
    const pieCtxTab = document.getElementById('enterprisePieChartTab');
    const hasPieData = <?php echo (!empty($reportLabels)) ? 'true' : 'false'; ?>;
    
    if (pieCtxTab && hasPieData) {
        new Chart(pieCtxTab.getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($reportLabels ?? []); ?>,
                datasets: [{
                    data: <?php echo json_encode($reportCounts ?? []); ?>,
                    backgroundColor: ['#e67e22', '#d35400', '#f39c12', '#27ae60', '#3498db', '#9b59b6'],
                    borderColor: '#08090f',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#bbb', font: { family: 'Inter', size: 12 }, padding: 20 }
                    }
                }
            }
        });
    }
}
</script>
</body>
</html>