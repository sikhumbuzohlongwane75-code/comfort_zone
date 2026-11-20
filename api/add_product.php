<?php
session_start();
require_once '../config.php';

// Optional: restrict access to staff/admins if your auth requires it
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
//     header('Location: login.php');
//     exit();
// }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : null;
    $stock = intval($_POST['stock'] ?? 0);
    $min_stock = intval($_POST['min_stock_level'] ?? 5);
    $description = trim($_POST['description'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    $imagePath = '';

    // Handle Image Upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['product_image']['tmp_name'];
        $fileName = $_FILES['product_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = 'uploads/';
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $imagePath = $dest_path;
            } else {
                $error = 'Error moving the uploaded image file.';
            }
        } else {
            $error = 'Invalid file type. Only JPG, JPEG, PNG, and WEBP are allowed.';
        }
    }

    if (empty($error)) {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("
                INSERT INTO products (name, category, price, original_price, stock, min_stock_level, description, is_featured, image, is_available) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$name, $category, $price, $original_price, $stock, $min_stock, $description, $is_featured, $imagePath]);
            
            $success = 'Product added successfully with image!';
        } catch (Exception $e) {
            $error = 'Database error: ' . $e.getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Product - Comfort Zone</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Orbitron:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Inter', sans-serif;
        background: #0d0e1a;
        color: #eee;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }
    .tech-bg {
        position: fixed; inset: 0; z-index: -1;
        background: radial-gradient(circle at 15% 20%, rgba(230,126,34,0.15), transparent 40%),
                    linear-gradient(#0d0e1a, #14162b 60%, #0d0e1a);
    }
    .form-container {
        width: 100%; max-width: 600px;
        background: rgba(255, 255, 255, 0.035);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        padding: 30px;
        backdrop-filter: blur(12px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.35);
    }
    h2 { font-family: 'Orbitron', sans-serif; margin-bottom: 20px; color: #e67e22; font-size: 1.5rem; }
    .form-group { margin-bottom: 16px; }
    label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #ccc; }
    input, select, textarea {
        width: 100%; padding: 12px; border-radius: 10px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: white; font-size: 0.9rem; outline: none; transition: all 0.25s;
    }
    input:focus, select:focus, textarea:focus { border-color: #e67e22; }
    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .checkbox-group { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
    .checkbox-group input { width: auto; }
    .btn-submit {
        background: linear-gradient(135deg, #e67e22, #d35400);
        color: white; border: none; padding: 12px; width: 100%;
        border-radius: 10px; font-weight: 700; cursor: pointer; margin-top: 10px;
        transition: transform 0.2s;
    }
    .btn-submit:hover { transform: translateY(-2px); }
    .alert-success { background: rgba(46,204,113,0.15); border: 1px solid #2ecc71; color: #2ecc71; padding: 12px; border-radius: 10px; margin-bottom: 15px; font-size: 0.9rem; }
    .alert-error { background: rgba(231,76,60,0.15); border: 1px solid #e74c3c; color: #e74c3c; padding: 12px; border-radius: 10px; margin-bottom: 15px; font-size: 0.9rem; }
    .back-link { display: inline-block; margin-bottom: 20px; color: #aaa; text-decoration: none; font-size: 0.85rem; }
    .back-link:hover { color: #e67e22; }
</style>
</head>
<body>
<div class="tech-bg"></div>

<div class="form-container">
    <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
    <h2>Add New Product</h2>

    <?php if (!empty($success)): ?>
        <div class="alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert-error"><?= $error ?></div>
    <?php endif; ?>

    <form action="add_product.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Product Name</label>
            <input type="text" name="name" required>
        </div>

        <div class="row">
            <div class="form-group">
                <label>Category</label>
                <select name="category" required>
                    <option value="meat">Meat</option>
                    <option value="herbal">Herbal</option>
                    <option value="side_dish">Sides</option>
                    <option value="beverage">Beverages</option>
                    <option value="grilled_special">Grilled Specials</option>
                </select>
            </div>
            <div class="form-group">
                <label>Stock Quantity</label>
                <input type="number" name="stock" value="10" required>
            </div>
        </div>

        <div class="row">
            <div class="form-group">
                <label>Price (R)</label>
                <input type="number" step="0.01" name="price" required>
            </div>
            <div class="form-group">
                <label>Original Price (Optional)</label>
                <input type="number" step="0.01" name="original_price">
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"></textarea>
        </div>

        <div class="form-group">
            <label>Product Image</label>
            <input type="file" name="product_image" accept="image/*" required>
        </div>

        <div class="checkbox-group">
            <input type="checkbox" name="is_featured" id="is_featured" value="1">
            <label for="is_featured" style="margin-bottom:0;">Mark as Featured Product</label>
        </div>

        <button type="submit" class="btn-submit">Upload &amp; Save Product</button>
    </form>
</div>

</body>
</html>