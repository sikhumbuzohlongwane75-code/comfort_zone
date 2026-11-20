<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';
requireAdmin();

$pdo = getDB();
$message = '';
$editProduct = null;

// Handle product deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $imgStmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $imgStmt->execute([$id]);
    $oldImg = $imgStmt->fetchColumn();

    if ($oldImg && file_exists('../' . $oldImg)) {
        @unlink('../' . $oldImg);
    }

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");

    if ($stmt->execute([$id])) {
        $message = "Product deleted successfully";
    } else {
        $message = "Error deleting product";
    }
}

// Handle product fetch for editing
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editProduct = $stmt->fetch();
}

// Handle inline stock updates from the table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $productId = intval($_POST['product_id']);
    $newStock = intval($_POST['new_stock']);

    $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");

    if ($stmt->execute([$newStock, $productId])) {
        $message = "Stock level updated successfully!";
    } else {
        $message = "Error updating stock level.";
    }
}

// Handle product add/edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $id = intval($_POST['product_id'] ?? 0);
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);

    $original_price = !empty($_POST['original_price'])
        ? floatval($_POST['original_price'])
        : null;

    $description = trim($_POST['description']);
    $stock = intval($_POST['stock']);
    $min_stock_level = intval($_POST['min_stock_level']);

    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Handle Image Upload
    $imagePath = null;

    if (
        isset($_FILES['product_image']) &&
        $_FILES['product_image']['error'] === UPLOAD_ERR_OK
    ) {
        $fileTmpPath = $_FILES['product_image']['tmp_name'];
        $fileName = $_FILES['product_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $imagePath = 'uploads/' . $newFileName;
            }
        }
    }

    // Update existing product
    if ($id > 0) {
        if ($imagePath) {
            $oldStmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $oldStmt->execute([$id]);

            $existingImg = $oldStmt->fetchColumn();

            if ($existingImg && file_exists('../' . $existingImg)) {
                @unlink('../' . $existingImg);
            }

            $stmt = $pdo->prepare(
                "UPDATE products 
                 SET name=?, category=?, price=?, original_price=?, description=?, 
                     stock=?, min_stock_level=?, is_available=?, is_featured=?, image=? 
                 WHERE id=?"
            );

            $stmt->execute([
                $name,
                $category,
                $price,
                $original_price,
                $description,
                $stock,
                $min_stock_level,
                $is_available,
                $is_featured,
                $imagePath,
                $id
            ]);
        } else {
            $stmt = $pdo->prepare(
                "UPDATE products 
                 SET name=?, category=?, price=?, original_price=?, description=?, 
                     stock=?, min_stock_level=?, is_available=?, is_featured=? 
                 WHERE id=?"
            );

            $stmt->execute([
                $name,
                $category,
                $price,
                $original_price,
                $description,
                $stock,
                $min_stock_level,
                $is_available,
                $is_featured,
                $id
            ]);
        }

        $message = "Product updated successfully";
    } else {
        // Add new product
        $stmt = $pdo->prepare(
            "INSERT INTO products 
            (name, category, price, original_price, description, stock, 
             min_stock_level, is_available, is_featured, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $name,
            $category,
            $price,
            $original_price,
            $description,
            $stock,
            $min_stock_level,
            $is_available,
            $is_featured,
            $imagePath
        ]);

        $message = "Product added successfully";
    }

    // Clear edit state after save
    $editProduct = null;
}

// Get all products for the table list
$stmt = $pdo->query("SELECT * FROM products ORDER BY category, name");
$products = $stmt->fetchAll();

// Determine whether the modal form should be visible on load (if editing or adding via URL)
$showModal = ($editProduct !== null) || isset($_GET['add']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #fef8f0;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: #1a1a2e;
            color: white;
            padding: 2rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar h3 {
            margin-bottom: 2rem;
            color: #e67e22;
        }

        .sidebar nav ul {
            list-style: none;
        }

        .sidebar nav ul li {
            margin-bottom: 1rem;
        }

        .sidebar nav ul li a {
            color: #aaa;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .sidebar nav ul li a:hover,
        .sidebar nav ul li a.active {
            background: #e67e22;
            color: white;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #eee;
            border-radius: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #e67e22;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        th {
            background: #f8f9fa;
        }

        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            background: #d4edda;
            color: #155724;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input {
            width: auto;
        }

        .product-thumb {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
            background: #eee;
        }

        .stock-input {
            width: 70px;
            padding: 6px;
            border: 2px solid #eee;
            border-radius: 6px;
            text-align: center;
            font-weight: bold;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .add-product-btn {
            background: #e67e22;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            transition: 0.3s;
        }

        .add-product-btn:hover {
            background: #d35400;
        }

        /* MODAL OVERLAY STYLES */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
            padding: 2rem 1rem;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            width: 100%;
            max-width: 700px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .close-form {
            color: #666;
            text-decoration: none;
            font-size: 20px;
            cursor: pointer;
            background: none;
            border: none;
        }

        .close-form:hover {
            color: #e74c3c;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }

            .main-content {
                margin-left: 0;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>

<body>

<div class="admin-container">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h3>
            <i class="fas fa-fire"></i>
            Comfort Zone Admin
        </h3>
        <nav>
            <ul>
                <li>
                    <a href="dashboard.php">
                        <i class="fas fa-chart-line"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="products.php" class="active">
                        <i class="fas fa-utensils"></i>
                        Products
                    </a>
                </li>
                <li>
                    <a href="orders.php">
                        <i class="fas fa-truck"></i>
                        Orders
                    </a>
                </li>
                <li>
                    <a href="inventory.php">
                        <i class="fas fa-boxes"></i>
                        Inventory
                    </a>
                </li>
                <li>
                    <a href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        Reports
                    </a>
                </li>
                <li>
                    <a href="../index.php">
                        <i class="fas fa-arrow-left"></i>
                        Back to Site
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- PAGE HEADER -->
        <div class="page-header">
            <h2>Manage Products</h2>
            <!-- Button is now always rendered and triggers the modal via JavaScript -->
            <button type="button" class="add-product-btn" id="openAddModalBtn">
                <i class="fas fa-plus"></i>
                Add Product
            </button>
        </div>

        <!-- MESSAGE -->
        <?php if ($message): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- INLINE MODAL FORM OVERLAY -->
        <div class="modal-overlay <?php echo $showModal ? 'active' : ''; ?>" id="productModal">
            <div class="modal-card">
                <div class="form-header">
                    <h3>
                        <?php
                        echo $editProduct
                            ? 'Edit Product'
                            : 'Add New Product';
                        ?>
                    </h3>
                    <button type="button" class="close-form" id="closeModalBtn" title="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input
                        type="hidden"
                        name="product_id"
                        value="<?php echo $editProduct['id'] ?? 0; ?>"
                    >

                    <!-- NAME / CATEGORY -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input
                                type="text"
                                name="name"
                                required
                                value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" required>
                                <option value="Meat" <?php echo (($editProduct['category'] ?? '') == 'Meat') ? 'selected' : ''; ?>>Meat</option>
                                <option value="Side Dish" <?php echo (($editProduct['category'] ?? '') == 'Side Dish') ? 'selected' : ''; ?>>Side Dish</option>
                                <option value="Beverage" <?php echo (($editProduct['category'] ?? '') == 'Beverage') ? 'selected' : ''; ?>>Beverage</option>
                                <option value="Herbal" <?php echo (($editProduct['category'] ?? '') == 'Herbal') ? 'selected' : ''; ?>>Herbal</option>
                                <option value="Grilled special" <?php echo (($editProduct['category'] ?? '') == 'Grilled special') ? 'selected' : ''; ?>>Grilled special</option>
                            </select>
                        </div>
                    </div>

                    <!-- PRICE -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Price (R)</label>
                            <input
                                type="number"
                                step="0.01"
                                name="price"
                                required
                                value="<?php echo $editProduct['price'] ?? ''; ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label>Original Price (R)</label>
                            <input
                                type="number"
                                step="0.01"
                                name="original_price"
                                value="<?php echo $editProduct['original_price'] ?? ''; ?>"
                            >
                        </div>
                    </div>

                    <!-- STOCK -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Stock Quantity</label>
                            <input
                                type="number"
                                name="stock"
                                required
                                min="0"
                                value="<?php echo $editProduct['stock'] ?? 0; ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label>Min Stock Level</label>
                            <input
                                type="number"
                                name="min_stock_level"
                                required
                                min="0"
                                value="<?php echo $editProduct['min_stock_level'] ?? 5; ?>"
                            >
                        </div>
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="form-group">
                        <label>Description</label>
                        <textarea
                            name="description"
                            rows="3"
                        ><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
                    </div>

                    <!-- IMAGE -->
                    <div class="form-group">
                        <label>Product Image</label>
                        <?php if (!empty($editProduct['image'])): ?>
                            <div style="margin-bottom: 8px;">
                                <img
                                    src="../<?php echo htmlspecialchars($editProduct['image']); ?>"
                                    width="60"
                                    style="border-radius:6px; object-fit:cover;"
                                >
                                <span style="font-size:0.8rem; color:#666;">Current image</span>
                            </div>
                        <?php endif; ?>

                        <input
                            type="file"
                            name="product_image"
                            accept="image/*"
                        >
                    </div>

                    <!-- CHECKBOXES -->
                    <div class="form-row" style="margin-bottom: 1rem;">
                        <div class="checkbox-group">
                            <input
                                type="checkbox"
                                name="is_available"
                                <?php echo (($editProduct['is_available'] ?? 1) == 1) ? 'checked' : ''; ?>
                            >
                            <label>Available</label>
                        </div>

                        <div class="checkbox-group">
                            <input
                                type="checkbox"
                                name="is_featured"
                                <?php echo (($editProduct['is_featured'] ?? 0) == 1) ? 'checked' : ''; ?>
                            >
                            <label>Featured Product</label>
                        </div>
                    </div>

                    <!-- FORM BUTTONS -->
                    <button
                        type="submit"
                        name="save_product"
                        class="btn btn-primary"
                    >
                        <?php
                        echo $editProduct
                            ? 'Update Product'
                            : 'Add Product';
                        ?>
                    </button>

                    <button
                        type="button"
                        class="btn"
                        id="cancelModalBtn"
                        style="background:#666;color:white;"
                    >
                        Cancel
                    </button>
                </form>
            </div>
        </div>

        <!-- PRODUCT TABLE -->
        <div class="card">
            <h3 style="margin-bottom: 1rem;">
                Product Catalog
            </h3>

            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Quick Stock Update</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; color:#777;">
                            No products found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <!-- IMAGE -->
                            <td>
                                <?php if (!empty($product['image'])): ?>
                                    <img
                                        src="../<?php echo htmlspecialchars($product['image']); ?>"
                                        class="product-thumb"
                                        alt="Product"
                                    >
                                <?php else: ?>
                                    <div
                                        class="product-thumb"
                                        style="display:flex;align-items:center;justify-content:center;color:#aaa;font-size:1.2rem;"
                                    >
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- ID -->
                            <td><?php echo $product['id']; ?></td>

                            <!-- NAME -->
                            <td><?php echo htmlspecialchars($product['name']); ?></td>

                            <!-- CATEGORY -->
                            <td><?php echo htmlspecialchars($product['category']); ?></td>

                            <!-- PRICE -->
                            <td>R<?php echo number_format($product['price'], 2); ?></td>

                            <!-- STOCK -->
                            <td>
                                <form method="POST" style="display:flex;gap:6px;align-items:center;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input
                                        type="number"
                                        name="new_stock"
                                        value="<?php echo $product['stock']; ?>"
                                        class="stock-input"
                                        required
                                        min="0"
                                    >
                                    <button
                                        type="submit"
                                        name="update_stock"
                                        class="btn btn-primary"
                                        style="padding:6px 10px;font-size:0.8rem;"
                                    >
                                        Update
                                    </button>
                                </form>
                            </td>

                            <!-- STATUS -->
                            <td>
                                <?php
                                echo $product['is_available']
                                    ? '✓ Available'
                                    : '✗ Unavailable';
                                ?>
                            </td>

                            <!-- ACTIONS -->
                            <td>
                                <a
                                    href="products.php?edit=<?php echo $product['id']; ?>"
                                    class="btn btn-primary"
                                    style="padding:5px 10px;margin-right:5px;font-size:0.85rem;"
                                >
                                    Edit
                                </a>

                                <a
                                    href="products.php?delete=<?php echo $product['id']; ?>"
                                    class="btn btn-danger"
                                    style="padding:5px 10px;font-size:0.85rem;"
                                    onclick="return confirm('Delete this product?')"
                                >
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- JAVASCRIPT FOR MODAL CONTROLS -->
<script>
    const modal = document.getElementById('productModal');
    const openBtn = document.getElementById('openAddModalBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const cancelBtn = document.getElementById('cancelModalBtn');

    // Open modal when clicking "Add Product"
    openBtn.addEventListener('click', () => {
        // Clear query parameters if any (optional clean up)
        window.history.replaceState({}, document.title, window.location.pathname);
        modal.classList.add('active');
    });

    // Close modal functions
    const closeModal = () => {
        modal.classList.remove('active');
        window.location.href = 'products.php'; // Resets state parameters like edit/add
    };

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // Close when clicking outside modal box content
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
</script>

</body>
</html>