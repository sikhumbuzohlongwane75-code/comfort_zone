<?php
require_once '../includes/auth_check.php';
require_role('staff');
require_once '../includes/db.php';

// Fetch products safely
$stmt = $pdo->query("SELECT id, name, price, stock FROM products ORDER BY name ASC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory - Staff Portal</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f8f9fa; }
        header { background: #343a40; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 30px; max-width: 1000px; margin: auto; }
        table { width: 100%%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background: #e9ecef; color: #495057; }
        .low-stock { color: #dc3545; font-weight: bold; }
        a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <header>
        <h2>Comfort Zone - Staff Portal</h2>
        <div><a href="dashboard.php" style="color: white;">&larr; Back to Dashboard</a></div>
    </header>
    <div class="container">
        <h1>Inventory & Stock Levels</h1>
        <table>
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Stock Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($products)): ?>
                    <tr><td colspan="4" style="text-align: center; color: #666;">No products found in inventory.</td></tr>
                <?php else: ?>
                    <?php foreach($products as $p): ?>
                    <tr>
                        <td>#<?php echo $p['id']; ?></td>
                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                        <td>$<?php echo number_format($p['price'], 2); ?></td>
                        <td>
                            <span class="<?php echo ($p['stock'] < 5) ? 'low-stock' : ''; ?>">
                                <?php echo intval($p['stock']); ?>
                                <?php if($p['stock'] < 5) echo ' (Low Stock!)'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
