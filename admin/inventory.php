<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';
requireAdmin();

$pdo = getDB();
$message = '';
$message_type = 'success';

// Handle stock level updates inline without page redirection redirects
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $newStock = isset($_POST['new_stock']) ? intval($_POST['new_stock']) : 0;
    
    if ($productId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $executed = $stmt->execute([$newStock, $productId]);
            
            if ($executed) {
                $message = "Stock level updated successfully!";
            } else {
                $message = "Failed to update database record.";
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = "Invalid Product ID provided.";
        $message_type = 'error';
    }
}

// Fetch all products for inventory tracking safely
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY category, name");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
    $message = "Could not load inventory: " . $e->getMessage();
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - <?php echo SITE_NAME; ?></title>
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
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        th { background: #f8f9fa; }
        .btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; background: #e67e22; color: white; font-size: 0.85rem; }
        .btn:hover { background: #d35400; }
        .message { padding: 1rem; border-radius: 10px; margin-bottom: 1rem; background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .stock-input { width: 70px; padding: 6px; border: 2px solid #eee; border-radius: 6px; text-align: center; font-weight: bold; }
        .product-thumb { width: 35px; height: 35px; object-fit: cover; border-radius: 6px; background: #eee; vertical-align: middle; margin-right: 8px; }
        @media (max-width: 768px) { .sidebar { width: 100%; position: relative; height: auto; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="sidebar"><h3><i class="fas fa-fire"></i> Comfort Zone Admin</h3>
        <nav><ul>
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-utensils"></i> Products</a></li>
            <li><a href="orders.php"><i class="fas fa-truck"></i> Orders</a></li>
            <li><a href="inventory.php" class="active"><i class="fas fa-boxes"></i> Inventory</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Site</a></li>
        </ul></nav>
    </div>
    <div class="main-content">
        <h2 style="margin-bottom: 2rem;">Inventory Management</h2>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type === 'error' ? 'error' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Stock Levels</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Min Level</th>
                        <th>Status</th>
                        <th>Update Stock</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="6" style="text-align:center; color:#777;">No products found in inventory.</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if (!empty($product['image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($product['image']); ?>" class="product-thumb" alt="">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($product['name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['category'] ?? 'General'); ?></td>
                            <td><strong><?php echo $product['stock']; ?></strong></td>
                            <td><?php echo $product['min_stock_level']; ?></td>
                            <td>
                                <?php if ($product['stock'] <= 0): ?>
                                    <span style="color: #e74c3c; font-weight:600;">Out of Stock</span>
                                <?php elseif ($product['stock'] <= $product['min_stock_level']): ?>
                                    <span style="color: #f39c12; font-weight:600;">Low Stock</span>
                                <?php else: ?>
                                    <span style="color: #27ae60; font-weight:600;">In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="inventory.php" style="display: flex; gap: 8px; align-items: center;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="number" name="new_stock" value="<?php echo $product['stock']; ?>" class="stock-input" required min="0">
                                    <button type="submit" name="update_stock" class="btn">Update</button>
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
</body>
</html>