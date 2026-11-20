<?php
require_once 'config.php';

$cartItems = [];
$subtotal = 0;

if (isLoggedIn()) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.stock, p.category FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll();
    foreach ($cartItems as $item) { $subtotal += $item['price'] * $item['quantity']; }
} elseif (isset($_SESSION['guest_cart'])) {
    $pdo = getDB();
    foreach ($_SESSION['guest_cart'] as $cartItem) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_available = 1");
        $stmt->execute([$cartItem['product_id']]);
        $product = $stmt->fetch();
        if ($product) {
            $cartItems[] = ['product_id' => $product['id'], 'name' => $product['name'], 'price' => $product['price'], 'quantity' => $cartItem['quantity'], 'stock' => $product['stock'], 'category' => $product['category']];
            $subtotal += $product['price'] * $cartItem['quantity'];
        }
    }
}
$delivery_fee = ($subtotal >= FREE_DELIVERY_MIN) ? 0 : DELIVERY_FEE;
$tax = $subtotal * TAX_RATE;
$total = $subtotal + $delivery_fee + $tax;
$icons = ['meat' => '🥩', 'side_dish' => '🥗', 'beverage' => '🥤', 'herbal' => '🌿', 'grilled_special' => '🔥'];
$staffName = $_SESSION['username'] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - <?php echo SITE_NAME; ?></title>
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
            min-height: 100vh;
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

        .navbar {
            background: rgba(10,11,18,0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--panel-border);
            padding: 1.1rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .logo-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dim));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            box-shadow: 0 0 0 1px rgba(230,126,34,0.3), 0 8px 20px rgba(230,126,34,0.25);
        }
        .logo-text h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: white;
        }
        .logo-text span {
            display: block;
            font-size: 0.65rem;
            color: var(--text-dim);
            letter-spacing: 0.5px;
        }

        .nav-links { display: flex; align-items: center; gap: 15px; }
        .nav-links a {
            color: #9195a5;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.88rem;
            padding: 8px 14px;
            border-radius: 9px;
            transition: all 0.2s ease;
        }
        .nav-links a:hover { color: #fff; background: rgba(255,255,255,0.04); }
        .nav-links a.active { color: var(--accent); background: rgba(230,126,34,0.1); border: 1px solid rgba(230,126,34,0.2); }

        .cart-container {
            max-width: 1200px;
            margin: 2.5rem auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
        }

        .cart-items {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.35);
            backdrop-filter: blur(14px);
        }

        .cart-items h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cart-item {
            display: flex;
            gap: 1.25rem;
            padding: 1.25rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            align-items: center;
        }
        .cart-item:last-child { border-bottom: none; }

        .cart-item-image {
            width: 65px; height: 65px;
            background: rgba(230,126,34,0.1);
            border: 1px solid rgba(230,126,34,0.2);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px;
        }

        .cart-item-details { flex: 1; }
        .cart-item-details h3 { font-size: 0.95rem; font-weight: 600; color: #fff; margin-bottom: 0.3rem; }
        .cart-item-price { color: var(--accent); font-weight: 700; font-size: 0.9rem; }

        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 0.6rem;
        }

        .qty-btn {
            width: 28px; height: 28px;
            border-radius: 50%;
            border: 1px solid var(--panel-border);
            background: rgba(255,255,255,0.05);
            color: #fff;
            cursor: pointer;
            font-weight: bold;
            display: flex; align-items: center; justify-content: center;
            transition: 0.2s;
        }
        .qty-btn:hover { background: var(--accent); border-color: var(--accent); color: white; }
        .cart-item-quantity span { color: #ededf2; font-weight: 600; font-size: 0.9rem; }

        .remove-btn {
            color: #e74c3c;
            cursor: pointer;
            margin-left: 1rem;
            font-size: 0.82rem;
            font-weight: 500;
            transition: 0.2s;
        }
        .remove-btn:hover { color: #ff6b6b; text-decoration: underline; }

        .cart-item-total {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            color: #fff;
            min-width: 90px;
            text-align: right;
        }

        .cart-summary {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.35);
            backdrop-filter: blur(14px);
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .cart-summary h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1.25rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.85rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 0.9rem;
            color: var(--text-dim);
        }
        .summary-row span:last-child { color: #fff; font-weight: 600; }
        .summary-total {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent) !important;
            border-bottom: none;
            padding-top: 1.25rem;
        }
        .summary-total span:last-child { color: var(--accent) !important; }

        .delivery-notice {
            background: rgba(241, 196, 15, 0.1);
            border: 1px solid rgba(241, 196, 15, 0.25);
            color: #f39c12;
            padding: 0.85rem;
            border-radius: 12px;
            margin: 1.25rem 0;
            text-align: center;
            font-size: 0.84rem;
            font-weight: 500;
        }

        .checkout-btn {
            width: 100%;
            padding: 1.1rem;
            background: linear-gradient(135deg, var(--accent), var(--accent-dim));
            color: white;
            border: none;
            border-radius: 14px;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            margin-top: 1.25rem;
            transition: all 0.25s ease;
            box-shadow: 0 4px 16px rgba(230,126,34,0.3);
        }
        .checkout-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(230,126,34,0.4); }

        .empty-cart { text-align: center; padding: 3.5rem 1rem; color: var(--text-dim); }
        .empty-cart i { font-size: 70px; color: rgba(255,255,255,0.1); margin-bottom: 1rem; }
        .empty-cart p { font-size: 1rem; margin-bottom: 1rem; }
        .empty-cart a { color: var(--accent); text-decoration: none; font-weight: 600; }
        .empty-cart a:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .cart-container { grid-template-columns: 1fr; padding: 0 1rem; }
            .navbar { padding: 1rem; }
        }
    </style>
</head>
<body>

<div class="tech-bg"></div>

<nav class="navbar">
    <a href="index.php" class="logo">
        <div class="logo-icon">🔥</div>
        <div class="logo-text">
            <h1>COMFORT ZONE</h1>
            <span>Flame-Grilled &amp; Herbal Goods</span>
        </div>
    </a>
    <div class="nav-links">
        <a href="index.php"><i class="fas fa-store"></i> Store Menu</a>
        <a href="cart.php" class="active"><i class="fas fa-shopping-cart"></i> Cart</a>
        <a href="my_orders.php"><i class="fas fa-box"></i> My Orders</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
            <a href="staff/orders.php" style="background: linear-gradient(135deg, #e67e22, #d35400); color: white; display: inline-flex; align-items: center; gap: 6px;"><i class="fas fa-chart-line"></i> Staff Dashboard</a>
        <?php endif; ?>
    </div>
</nav>

<div class="cart-container">
    <div class="cart-items">
        <h2><i class="fas fa-shopping-bag" style="color: var(--accent);"></i> Your Cart (<?php echo count($cartItems); ?> items)</h2>
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Your shopping cart is currently empty</p>
                <a href="index.php">Start Ordering <i class="fas fa-arrow-right"></i></a>
            </div>
        <?php else: ?>
            <?php foreach ($cartItems as $item): ?>
            <div class="cart-item">
                <div class="cart-item-image"><?php echo $icons[$item['category']] ?? '🍽️'; ?></div>
                <div class="cart-item-details">
                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                    <div class="cart-item-price">R<?php echo number_format($item['price'], 2); ?></div>
                    <div class="cart-item-quantity">
                        <button class="qty-btn" onclick="updateQuantity(<?php echo $item['product_id']; ?>, -1)">-</button>
                        <span id="qty-<?php echo $item['product_id']; ?>"><?php echo $item['quantity']; ?></span>
                        <button class="qty-btn" onclick="updateQuantity(<?php echo $item['product_id']; ?>, 1)">+</button>
                        <span class="remove-btn" onclick="removeItem(<?php echo $item['product_id']; ?>)"><i class="fas fa-trash"></i> Remove</span>
                    </div>
                </div>
                <div class="cart-item-total">R<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="cart-summary">
        <h3>Order Summary</h3>
        <div class="summary-row"><span>Subtotal</span><span>R<?php echo number_format($subtotal, 2); ?></span></div>
        <div class="summary-row"><span>Delivery Fee</span><span><?php echo $delivery_fee == 0 ? 'Free' : 'R' . number_format($delivery_fee, 2); ?></span></div>
        <div class="summary-row"><span>Tax (15% VAT)</span><span>R<?php echo number_format($tax, 2); ?></span></div>
        <div class="summary-row summary-total"><span>Total</span><span>R<?php echo number_format($total, 2); ?></span></div>
        
        <?php if ($subtotal < FREE_DELIVERY_MIN && $subtotal > 0): ?>
            <div class="delivery-notice">Add R<?php echo number_format(FREE_DELIVERY_MIN - $subtotal, 2); ?> more for free delivery!</div>
        <?php endif; ?>
        
        <button class="checkout-btn" onclick="checkout()" <?php echo empty($cartItems) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>Proceed to Checkout</button>
    </div>
</div>

<script>
function updateQuantity(productId, change) {
    fetch('api/update_cart.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'product_id=' + productId + '&change=' + change })
    .then(response => response.json()).then(data => { if (data.success) location.reload(); });
}
function removeItem(productId) {
    if (confirm('Remove this item from cart?')) {
        fetch('api/update_cart.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'product_id=' + productId + '&remove=1' })
        .then(response => response.json()).then(data => { if (data.success) location.reload(); });
    }
}
function checkout() { window.location.href = 'checkout.php'; }
</script>
</body>
</html>