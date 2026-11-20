<?php
require_once '../config.php';
header('Content-Type: application/json');

$product_id = intval($_POST['product_id'] ?? 0);
$change = intval($_POST['change'] ?? 0);
$remove = isset($_POST['remove']);

if (!$product_id) { echo json_encode(['success' => false]); exit(); }

if (isLoggedIn()) {
    $pdo = getDB();
    $user_id = $_SESSION['user_id'];
    if ($remove) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$change, $user_id, $product_id]);
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ? AND quantity <= 0");
        $stmt->execute([$user_id, $product_id]);
    }
} else {
    if (isset($_SESSION['guest_cart'])) {
        foreach ($_SESSION['guest_cart'] as $key => $item) {
            if ($item['product_id'] == $product_id) {
                if ($remove) unset($_SESSION['guest_cart'][$key]);
                else { $_SESSION['guest_cart'][$key]['quantity'] += $change; if ($_SESSION['guest_cart'][$key]['quantity'] <= 0) unset($_SESSION['guest_cart'][$key]); }
                break;
            }
        }
        $_SESSION['guest_cart'] = array_values($_SESSION['guest_cart']);
    }
}
echo json_encode(['success' => true]);
?>