<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . 'includes/login.php');
    exit();
}

$pdo = getDB();
$userId = $_SESSION['user_id'];


// Fetch user orders
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $orders = [];
    $dbError = "Could not retrieve orders: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Orbitron:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0d0e1a; color: #eee; min-height: 100vh; }
        .tech-bg { position: fixed; inset: 0; z-index: -1; background: radial-gradient(circle at 15% 20%, rgba(230,126,34,0.15), transparent 40%), linear-gradient(#0d0e1a, #14162b 60%, #0d0e1a); }
        .main-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 40px; background: rgba(13,14,26,0.85); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(230,126,34,0.15); }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: white; }
        .brand .logo-icon { width: 42px; height: 42px; background: linear-gradient(135deg, #e67e22, #d35400); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .brand h2 { font-family: 'Orbitron', sans-serif; font-size: 1.15rem; }
        .container { max-width: 900px; margin: 3rem auto; padding: 0 20px; }
        h1 { font-family: 'Orbitron', sans-serif; font-size: 1.8rem; margin-bottom: 1.5rem; color: #fff; display: flex; align-items: center; gap: 12px; }
        .order-card { background: rgba(19, 21, 38, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(230, 126, 34, 0.25); border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .order-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 1rem; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px; }
        .order-num { font-family: 'Orbitron', sans-serif; color: #e67e22; font-size: 1.2rem; font-weight: 700; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge-pickup { background: rgba(52, 152, 219, 0.15); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.3); }
        .badge-delivery { background: rgba(39, 174, 96, 0.15); color: #27ae60; border: 1px solid rgba(39, 174, 96, 0.3); }
        .badge-status { background: rgba(243, 156, 18, 0.15); color: #f39c12; border: 1px solid rgba(243, 156, 18, 0.3); }
        .order-body { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; font-size: 0.9rem; }
        @media(max-width: 768px) { .order-body { grid-template-columns: 1fr; } }
        .item-list { list-style: none; color: #aaa; margin-bottom: 0.8rem; }
        .item-list li { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px dashed rgba(255,255,255,0.05); }
        .order-meta { color: #888; line-height: 1.5; font-size: 0.85rem; }
        .order-total { font-family: 'Orbitron', sans-serif; font-size: 1.1rem; color: #fff; text-align: right; font-weight: 700; margin-top: 0.5rem; }
        .no-orders { text-align: center; padding: 4rem 2rem; background: rgba(19, 21, 38, 0.5); border-radius: 16px; border: 1px dashed rgba(230, 126, 34, 0.3); }
        .btn-browse { display: inline-block; background: linear-gradient(135deg, #e67e22, #d35400); color: #fff; padding: 12px 25px; border-radius: 10px; text-decoration: none; font-weight: 600; margin-top: 1rem; }
    </style>
</head>
<body>
<div class="tech-bg"></div>

<header class="main-header">
    <a href="index.php" class="brand">
        <div class="logo-icon">🔥</div>
        <div><h2>COMFORT ZONE</h2><span style="font-size:0.7rem;color:#888;">Customer Portal</span></div>
    </a>
    <div style="display: flex; align-items: center; gap: 18px;">
        <a href="index.php" style="color:#ccc;text-decoration:none;font-weight:600;"><i class="fas fa-home"></i> Home</a>
        <a href="cart.php" style="color:#e67e22;text-decoration:none;font-weight:600;"><i class="fas fa-shopping-cart"></i> Cart</a>
        <a href="includes/logout.php" style="color:#ccc;text-decoration:none;font-weight:600;">Logout</a>
    </div>
</header>

<div class="container">
    <h1><i class="fas fa-box-open"></i> My Order History</h1>

    <?php if (isset($dbError)): ?>
        <div style="color: #e74c3c; background: rgba(231,76,60,0.1); padding: 15px; border-radius: 10px; margin-bottom: 1.5rem;">
            <?= htmlspecialchars($dbError) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="no-orders">
            <i class="fas fa-shopping-basket" style="font-size: 3rem; color: #e67e22; margin-bottom: 1rem;"></i>
            <h3>No orders placed yet!</h3>
            <p style="color: #888; margin-top: 5px;">Explore our menu and place your first order for pickup or delivery.</p>
            <a href="index.php" class="btn-browse">Browse Menu</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <span class="order-num"><?= htmlspecialchars($order['order_number']) ?></span>
                        <span style="color: #666; font-size: 0.8rem; margin-left: 10px;"><?= htmlspecialchars($order['created_at']) ?></span>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span class="badge <?= ($order['order_type'] === 'Pickup') ? 'badge-pickup' : 'badge-delivery' ?>">
                            <?= htmlspecialchars($order['order_type'] ?? 'Delivery') ?>
                        </span>
                        <span class="badge badge-status"><?= ucfirst(htmlspecialchars($order['order_status'])) ?></span>
                    </div>
                </div>

                <div class="order-body">
                    <div>
                        <span style="color: #aaa; font-weight: 600; font-size: 0.85rem; display: block; margin-bottom: 6px;">Ordered Items:</span>
                        <ul class="item-list">
                            <?php 
                            $decodedItems = json_decode($order['items'], true);
                            if (is_array($decodedItems)) {
                                foreach ($decodedItems as $itm) {
                                    echo '<li><span>' . intval($itm['quantity']) . 'x ' . htmlspecialchars($itm['name'] ?? 'Product') . '</span><span>R' . number_format(($itm['price'] * $itm['quantity']), 2) . '</span></li>';
                                }
                            } else {
                                echo '<li><span>Custom or legacy order items</span></li>';
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="order-meta">
                        <div><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></div>
                        <div><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?></div>
                        <div><strong>Fulfillment:</strong> <?= htmlspecialchars($order['delivery_address']) ?></div>
                        <div class="order-total">Total: R<?= number_format($order['total'], 2) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>