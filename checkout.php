<?php
session_start();
require_once 'config.php';
$pdo = getDB();

if (!isLoggedIn()) {
    header('Location: includes/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user profile from database to get pre-existing phone number & details
$userPhone = '';
$userName = $_SESSION['user_name'] ?? '';
try {
    $userStmt = $pdo->prepare("SELECT full_name, phone, email FROM users WHERE id = ? LIMIT 1");
    $userStmt->execute([$userId]);
    $userProfile = $userStmt->fetch();
    if ($userProfile) {
        $userPhone = $userProfile['phone'] ?? '';
        if (!empty($userProfile['full_name'])) {
            $userName = $userProfile['full_name'];
        }
    }
} catch (PDOException $e) {
    // Graceful fallback to session data
}

// Fetch cart items
$stmt = $pdo->prepare("SELECT c.*, p.name, p.price 
                       FROM cart c 
                       JOIN products p ON c.product_id = p.id 
                       WHERE c.user_id = ?");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$vat = $subtotal * 0.15;

$orderPlaced = false;
$generatedOrderNumber = '';
$orderTypeChosen = 'Delivery';

// Handle Order Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderType     = $_POST['order_type'] ?? 'Delivery';
    $name          = trim($_POST['name'] ?? '');
    $email         = $_SESSION['user_email'] ?? ''; 
    $phone         = trim($_POST['phone'] ?? '');
    
    // Set proper address and delivery fee based on order type securely on backend
    if ($orderType === 'Pickup') {
        $address      = 'Walk-In Counter Order';
        $delivery_fee = 0.00;
    } else {
        $address      = trim($_POST['address'] ?? '');
        $delivery_fee = 35.00;
    }
    
    $paymentMethod = $_POST['payment_method'] ?? 'eft_paysharp';
    $total         = $subtotal + $vat + $delivery_fee;

    try {
        // Deduct stock for each item
        foreach ($cartItems as $item) {
            $updateStockStmt = $pdo->prepare("
                UPDATE products 
                SET stock = stock - ? 
                WHERE id = ?
            ");
            $updateStockStmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Generate Unique Order Number
        $generatedOrderNumber = 'CZ-' . strtoupper(substr(uniqid(), -5));
        
        // Prepare items JSON payload for storage
        $itemsJson = json_encode($cartItems);
        
        // Insert order matching exact database table columns
        $insertStmt = $pdo->prepare("INSERT INTO orders (
            order_number, user_id, customer_name, customer_email, customer_phone, 
            delivery_address, items, subtotal, delivery_fee, tax, total, 
            payment_method, payment_status, order_status, order_type, special_instructions, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, '', NOW())");
        
        $insertStmt->execute([
            $generatedOrderNumber, 
            $userId, 
            $name, 
            $email, 
            $phone, 
            $address, 
            $itemsJson, 
            $subtotal, 
            $delivery_fee, 
            $vat, 
            $total, 
            $paymentMethod, 
            $orderType
        ]);
        
        // Clear the user's cart after successful order creation
        $clearCart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $clearCart->execute([$userId]);
        
        $orderPlaced = true;
        $orderTypeChosen = $orderType;
    } catch (PDOException $e) {
        $dbError = "Database Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo SITE_NAME; ?></title>
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
        .checkout-container { max-width: 1050px; margin: 3rem auto; padding: 0 20px; }
        .checkout-grid { display: grid; grid-template-columns: 1.1fr 1.1fr; gap: 2rem; }
        @media(max-width: 900px) { .checkout-grid { grid-template-columns: 1fr; } }
        .card-box { background: rgba(19, 21, 38, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(230, 126, 34, 0.25); padding: 2rem; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.4); margin-bottom: 1.5rem; }
        h3 { font-family: 'Orbitron', sans-serif; font-size: 1.1rem; color: #fff; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-size: 0.85rem; color: #aaa; margin-bottom: 0.5rem; font-weight: 600; }
        .form-control { width: 100%; padding: 12px 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: #fff; font-size: 0.95rem; }
        .form-control:focus { outline: none; border-color: #e67e22; background: rgba(230,126,34,0.03); }
        .payment-options, .order-type-options { display: flex; gap: 12px; flex-direction: column; margin-bottom: 1.2rem; }
        .payment-option, .type-option { display: flex; align-items: center; gap: 12px; color: #fff; cursor: pointer; background: rgba(255,255,255,0.02); padding: 14px 18px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.06); transition: all 0.3s; }
        .payment-option:hover, .type-option:hover { border-color: rgba(230,126,34,0.5); background: rgba(230,126,34,0.04); }
        input[type="radio"] { accent-color: #e67e22; width: 18px; height: 18px; cursor: pointer; }
        .row-flex { display: flex; align-items: center; gap: 10px; width: 100%; }
        .icon-badge { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .icon-badge.delivery { background: rgba(39, 174, 96, 0.1); color: #27ae60; }
        .icon-badge.eft { background: rgba(155, 89, 182, 0.1); color: #9b59b6; }
        .instruction-box { background: linear-gradient(135deg, rgba(230, 126, 34, 0.08), rgba(211, 84, 0, 0.12)); border: 1px solid rgba(230, 126, 34, 0.35); color: #f39c12; padding: 15px 18px; border-radius: 14px; margin-top: 1.5rem; font-size: 0.85rem; line-height: 1.5; }
        .summary-line { display: flex; justify-content: space-between; margin-bottom: 0.8rem; font-size: 0.95rem; color: #aaa; }
        .summary-total { display: flex; justify-content: space-between; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 1.25rem; font-weight: 800; color: #e67e22; font-family: 'Orbitron', sans-serif; }
        .btn-submit { width: 100%; background: linear-gradient(135deg, #e67e22, #d35400); color: white; padding: 15px; border-radius: 12px; font-weight: 700; border: none; cursor: pointer; font-size: 1rem; margin-top: 1.5rem; box-shadow: 0 4px 15px rgba(230, 126, 34, 0.3); transition: transform 0.2s; }
        .btn-submit:hover { transform: translateY(-2px); }
        .success-screen { text-align: center; padding: 3rem 1rem; }
        .success-screen h2 { font-family: 'Orbitron', sans-serif; color: #27ae60; margin-bottom: 1rem; font-size: 1.8rem; }
        .order-badge-num { font-family: 'Orbitron', sans-serif; font-size: 2rem; background: rgba(230, 126, 34, 0.1); border: 2px dashed #e67e22; padding: 15px; border-radius: 12px; display: inline-block; margin: 15px 0; color: #e67e22; }
    </style>
</head>
<body>
<div class="tech-bg"></div>

<header class="main-header">
    <a href="index.php" class="brand">
        <div class="logo-icon">🔥</div>
        <div><h2>COMFORT ZONE</h2><span style="font-size:0.7rem;color:#888;">Secure Checkout</span></div>
    </a>
    <a href="cart.php" style="color:#ccc;text-decoration:none;font-weight:600;"><i class="fas fa-arrow-left"></i> Back to Cart</a>
</header>

<div class="checkout-container">
    <?php if (isset($dbError)): ?>
        <div class="card-box" style="border-color: #e74c3c; margin-bottom: 1.5rem; color: #e74c3c;">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($dbError) ?>
        </div>
    <?php endif; ?>

    <?php if ($orderPlaced): ?>
        <div class="card-box success-screen">
            <h2><i class="fas fa-check-circle"></i> Order Placed Successfully!</h2>
            <p style="color: #aaa; font-size: 1.05rem;">Your generated order number is:</p>
            <div class="order-badge-num"><?= htmlspecialchars($generatedOrderNumber) ?></div>
            
            <?php if ($orderTypeChosen === 'Pickup'): ?>
                <p style="margin-top: 15px; font-size: 1rem; line-height: 1.6; color: #ddd;">
                    Come straight through and <strong>show this order number to the staff</strong> to skip the line and have your meal prepared right away!
                </p>
            <?php else: ?>
                <p style="margin-top: 15px; font-size: 1rem; line-height: 1.6; color: #ddd;">
                    We are preparing your order! It should arrive soon to your specified Isipingo location.
                </p>
            <?php endif; ?>

            <div class="instruction-box" style="text-align: left; margin-top: 2rem;">
                <div style="font-weight: 700; margin-bottom: 5px;"><i class="fas fa-info-circle"></i> Payment Follow-up Notice</div>
                Please make your EFT transfer and follow up with the staff desk at <strong>031 143 4567</strong>.
            </div>

            <a href="my_orders.php" class="btn-submit" style="display: block; text-decoration: none; text-align: center; margin-top: 2rem;">View My Orders</a>
        </div>
    <?php else: ?>
        <form action="checkout.php" method="POST" id="checkoutForm">
            <div class="checkout-grid">
                <!-- LEFT COLUMN: Order Type & Delivery/Contact Info -->
                <div>
                    <div class="card-box">
                        <h3><i class="fas fa-store"></i> Choose Order Type</h3>
                        <div class="order-type-options">
                            <label class="type-option">
                                <input type="radio" name="order_type" value="Delivery" checked onclick="toggleOrderType('Delivery')">
                                <div class="row-flex">
                                    <div class="icon-badge delivery"><i class="fas fa-motorcycle"></i></div>
                                    <div>
                                        <span style="font-weight:600;display:block;">Delivery</span>
                                        <span style="font-size:0.75rem;color:#888;">Delivered straight to your address (R35 fee)</span>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="type-option">
                                <input type="radio" name="order_type" value="Pickup" onclick="toggleOrderType('Pickup')">
                                <div class="row-flex">
                                    <div class="icon-badge eft"><i class="fas fa-walking"></i></div>
                                    <div>
                                        <span style="font-weight:600;display:block;">Pickup (Skip the Line)</span>
                                        <span style="font-size:0.75rem;color:#888;">Get an instant order number to cut the queue</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="card-box" id="deliveryDetailsCard">
                        <h3 id="detailsTitle"><i class="fas fa-map-marker-alt"></i> Contact &amp; Delivery Information</h3>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" id="fieldName" class="form-control" value="<?= htmlspecialchars($userName) ?>" required>
                        </div>
                        <div class="form-group" id="addressGroup">
                            <label>Delivery Address (Must be within Isipingo area)</label>
                            <textarea name="address" id="fieldAddress" class="form-control" rows="2" placeholder="Street address, Isipingo..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Customer Phone Number</label>
                            <input type="text" name="phone" id="fieldPhone" class="form-control" value="<?= htmlspecialchars($userPhone) ?>" placeholder="082 123 4567" required>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Order Summary & Secure Payment Options -->
                <div>
                    <div class="card-box">
                        <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                        <div style="max-height: 220px; overflow-y: auto; margin-bottom: 1rem; padding-right: 5px;">
                            <?php foreach($cartItems as $item): ?>
                                <div class="summary-line">
                                    <span><?= $item['quantity'] ?>x <?= htmlspecialchars($item['name']) ?></span>
                                    <span>R<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="summary-line"><span>Subtotal</span> <span>R<?= number_format($subtotal, 2) ?></span></div>
                        <div class="summary-line"><span>VAT (15%)</span> <span>R<?= number_format($vat, 2) ?></span></div>
                        <div class="summary-line">
                            <span>Delivery Fee</span> 
                            <span id="deliveryFeeLabel" style="color:#e67e22;font-weight:600;">R35.00</span>
                        </div>
                        <div class="summary-total"><span>Total</span> <span id="grandTotalLabel">R<?= number_format($subtotal + $vat + 35.00, 2) ?></span></div>
                    </div>

                    <!-- Secure Payment Option Card (Now on the Right Side) -->
                    <div class="payment-section card-box">
                        <h3><i class="fas fa-shield-alt"></i> Secure Direct Bank Transfer</h3>

                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="direct_eft" checked>
                                <div class="row-flex">
                                    <div class="icon-badge eft"><i class="fas fa-university"></i></div>
                                    <div>
                                        <span style="font-weight:600;display:block;">Direct Bank Transfer (EFT)</span>
                                        <span style="font-size:0.75rem;color:#888;">YOUR BANK NAME | Acc: <strong>YOUR ACCOUNT NUMBER</strong></span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="instruction-box">
                            <div style="font-weight: 700; margin-bottom: 4px;"><i class="fas fa-info-circle"></i> Official Banking Details</div>
                            Please make your EFT transfer using our official account credentials below:<br>
                            • <strong>Bank Name:</strong> YOUR BANK NAME<br>
                            • <strong>Account Holder:</strong> Comfort Zone<br>
                            • <strong>Account Number:</strong> YOUR ACCOUNT NUMBER<br>
                            • <strong>Account Type:</strong> Cheque / Savings<br>
                            • <strong>Branch Code:</strong> YOUR BRANCH CODE<br>
                            • <strong>Reference:</strong> Your Order Number (e.g., CZ-XXXXX)<br><br>
                            Once transferred, please follow up with the staff desk at <strong>031 143 4567</strong>.
                        </div>
                    </div>

                    <button type="submit" class="btn-submit"><i class="fas fa-check-circle"></i> Confirm &amp; Get Order Number</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function toggleOrderType(type) {
    const addressGroup = document.getElementById('addressGroup');
    const fieldAddress = document.getElementById('fieldAddress');
    const deliveryFeeLabel = document.getElementById('deliveryFeeLabel');
    const grandTotalLabel = document.getElementById('grandTotalLabel');
    const detailsTitle = document.getElementById('detailsTitle');
    
    let subtotal = <?= $subtotal ?>;
    let vat = <?= $vat ?>;
    let deliveryFee = 35.00;

    if (type === 'Pickup') {
        addressGroup.style.display = 'none';
        fieldAddress.removeAttribute('required');
        fieldAddress.value = 'Walk-In Counter Order';
        detailsTitle.innerHTML = '<i class="fas fa-user-check"></i> Pickup Contact Information';
        deliveryFee = 0.00;
        deliveryFeeLabel.innerText = 'R0.00 (Pickup)';
    } else {
        addressGroup.style.display = 'block';
        fieldAddress.setAttribute('required', 'required');
        fieldAddress.value = '';
        detailsTitle.innerHTML = '<i class="fas fa-map-marker-alt"></i> Contact &amp; Delivery Information';
        deliveryFeeLabel.innerText = 'R35.00';
    }

    let newTotal = subtotal + vat + deliveryFee;
    grandTotalLabel.innerText = 'R' + newTotal.toFixed(2);
}
</script>
</body>
</html>