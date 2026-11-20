<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'cart_count' => 0];
$quantity = max(1, intval($_POST['quantity'] ?? 1));
$product_id = intval($_POST['product_id'] ?? 0);

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit();
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT stock, name, price FROM products WHERE id = ? AND is_available = 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not available']);
    exit();
}

if ($product['stock'] < $quantity) {
    echo json_encode(['success' => false, 'out_of_stock' => true, 'message' => 'Product out of stock']);
    exit();
}

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
    $stmt->execute([$user_id, $product_id, $quantity, $quantity]);

    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cartCount = intval($stmt->fetch()['total'] ?? 0);

    $response = ['success' => true, 'cart_count' => $cartCount, 'message' => $product['name'] . ' added to cart'];
} else {
    if (!isset($_SESSION['guest_cart'])) {
        $_SESSION['guest_cart'] = [];
    }

    $found = false;
    foreach ($_SESSION['guest_cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    unset($item);

    if (!$found) {
        $_SESSION['guest_cart'][] = ['product_id' => $product_id, 'quantity' => $quantity];
    }

    $cartCount = array_sum(array_column($_SESSION['guest_cart'], 'quantity'));
    $response = ['success' => true, 'cart_count' => $cartCount, 'message' => $product['name'] . ' added to cart'];
}

echo json_encode($response);
?>
