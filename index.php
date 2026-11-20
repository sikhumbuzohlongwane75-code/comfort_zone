<?php
session_start();
require_once 'config.php';
$db = getDB();

$products = [];
try {
$stmt = $db->query("SELECT id, name, category, price, original_price, description, image, stock, min_stock_level, is_featured FROM products WHERE is_available = 1 AND image IS NOT NULL AND image != '' AND name NOT LIKE '%Coleslaw%' ORDER BY is_featured DESC, category, name");    $products = $stmt->fetchAll();
} catch (Exception $e) {
    // Graceful fallback if query fails
}

// Calculate cart count for navbar badge
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $db_cart_stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $db_cart_stmt->execute([$_SESSION['user_id']]);
    $cartCount = intval($db_cart_stmt->fetch()['total'] ?? 0);
} elseif (isset($_SESSION['guest_cart'])) {
    foreach ($_SESSION['guest_cart'] as $g_item) {
        $cartCount += $g_item['quantity'];
    }
}

$categoryLabels = [
    'meat'            => ['label' => 'Meat',            'icon' => '🥩'],
    'herbal'          => ['label' => 'Herbal',          'icon' => '🌿'],
    'side_dish'       => ['label' => 'Sides',           'icon' => '🍟'],
    'beverage'        => ['label' => 'Beverages',       'icon' => '🥤'],
    'grilled_special' => ['label' => 'Grilled Specials',  'icon' => '🔥'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comfort Zone - Flame-Grilled & Herbal Wellness</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Orbitron:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Inter', sans-serif;
        background: #0d0e1a;
        color: #eee;
        min-height: 100vh;
        overflow-x: hidden;
    }

    .tech-bg {
        position: fixed;
        inset: 0;
        z-index: -1;
        background:
            radial-gradient(circle at 15% 20%, rgba(230,126,34,0.15), transparent 40%),
            radial-gradient(circle at 85% 75%, rgba(52,152,219,0.12), transparent 45%),
            linear-gradient(#0d0e1a, #14162b 60%, #0d0e1a);
    }
    .tech-bg::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(230,126,34,0.06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(230,126,34,0.06) 1px, transparent 1px);
        background-size: 42px 42px;
        mask-image: radial-gradient(ellipse 80% 60% at 50% 20%, black 40%, transparent 90%);
    }

    .main-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 40px;
        background: rgba(13,14,26,0.85);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(230,126,34,0.15);
        position: sticky;
        top: 0;
        z-index: 50;
        flex-wrap: wrap;
        gap: 15px;
    }

    .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: white; }
    .brand .logo-icon {
        width: 42px; height: 42px;
        background: linear-gradient(135deg, #e67e22, #d35400);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px;
        box-shadow: 0 0 20px rgba(230,126,34,0.5);
    }
    .brand h2 { font-family: 'Orbitron', sans-serif; font-size: 1.15rem; letter-spacing: 1px; }
    .brand span { display: block; font-size: 0.7rem; color: #888; }

    .nav-links { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
    .nav-links a {
        color: #ccc;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        padding: 9px 16px;
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.1);
        transition: all 0.25s;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .nav-links a:hover, .nav-links a.active {
        border-color: #e67e22;
        color: #e67e22;
        background: rgba(230,126,34,0.05);
    }

    .cart-btn-nav {
        background: rgba(230, 126, 34, 0.1) !important;
        border-color: rgba(230, 126, 34, 0.3) !important;
        color: #e67e22 !important;
    }

    .cart-badge {
        background: #e67e22;
        color: white;
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 50px;
        font-weight: 700;
    }

    .header-actions { display: flex; align-items: center; gap: 12px; }
    .btn-outline-custom {
        background: transparent;
        border: 1.5px solid #e67e22;
        color: white;
        padding: 8px 16px;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.3s;
    }
    .btn-outline-custom:hover { background: rgba(230, 126, 34, 0.1); }

    .btn-solid-custom {
        background: linear-gradient(135deg, #e67e22, #d35400);
        color: white;
        padding: 9px 18px;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(230, 126, 34, 0.3);
    }
    .btn-solid-custom:hover { transform: translateY(-2px); }

    /* Hero & Smart Search */
    .hero { text-align: center; padding: 35px 20px 20px; }
    .hero h1 {
        font-family: 'Orbitron', sans-serif;
        font-size: 2.2rem;
        font-weight: 800;
        margin-bottom: 8px;
        background: linear-gradient(135deg, #fff, #e67e22);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .hero p { color: #999; font-size: 0.95rem; max-width: 520px; margin: 0 auto 22px; }

    .search-bar-container { max-width: 520px; margin: 0 auto 20px; padding: 0 20px; position: relative; }
    .search-input {
        width: 100%;
        padding: 14px 20px 14px 46px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(230,126,34,0.3);
        border-radius: 30px;
        color: #fff;
        font-size: 0.95rem;
        outline: none;
        transition: all 0.3s;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }
    .search-input:focus { border-color: #e67e22; background: rgba(230,126,34,0.08); }
    .search-icon { position: absolute; left: 36px; top: 50%; transform: translateY(-50%); color: #888; font-size: 1rem; }

    /* Categories Bar */
    .categories { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; padding: 0 20px 30px; }
    .cat-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.1);
        color: #ccc;
        padding: 9px 18px;
        border-radius: 30px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.25s;
    }
    .cat-btn:hover { border-color: rgba(230,126,34,0.5); }
    .cat-btn.active {
        background: linear-gradient(135deg, #e67e22, #d35400);
        color: white;
        border-color: transparent;
        box-shadow: 0 6px 20px rgba(230,126,34,0.35);
    }

    /* Product Grid */
    .products {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 30px 80px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 22px;
    }
    .p-card {
        background: rgba(255,255,255,0.035);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 18px;
        overflow: hidden;
        transition: all 0.3s;
        position: relative;
        display: flex;
        flex-direction: column;
    }
    .p-card:hover {
        transform: translateY(-4px);
        border-color: rgba(230,126,34,0.4);
        box-shadow: 0 12px 30px rgba(0,0,0,0.35);
    }

    .product-img-banner { width: 100%; height: 155px; object-fit: cover; display: block; }
    .p-card-body { padding: 20px; display: flex; flex-direction: column; flex: 1; position: relative; }
    .p-card .icon { font-size: 2.5rem; padding: 20px 0 5px; text-align: center; }
    .p-card h3 { font-size: 1.05rem; margin-bottom: 6px; color: #fff; }
    .p-card .desc { color: #999; font-size: 0.85rem; line-height: 1.5; flex: 1; margin-bottom: 14px; }
    .p-card .price-row { display: flex; align-items: baseline; gap: 8px; margin-bottom: 12px; }
    .p-card .price { font-size: 1.25rem; font-weight: 800; color: #e67e22; }
    .p-card .old-price { font-size: 0.85rem; color: #666; text-decoration: line-through; }

    .stock-badge {
        position: absolute;
        top: 14px;
        right: 14px;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 12px;
        z-index: 10;
        backdrop-filter: blur(6px);
    }
    .stock-badge.low { background: rgba(231,76,60,0.85); color: #fff; }
    .stock-badge.featured { background: rgba(230,126,34,0.9); color: #fff; }

    .btn-add-cart {
        background: rgba(230,126,34,0.12);
        border: 1px solid rgba(230,126,34,0.4);
        color: #e67e22;
        padding: 10px;
        border-radius: 10px;
        font-weight: 700;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.25s;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-add-cart:hover { background: #e67e22; color: white; }
    .empty-msg { text-align: center; color: #777; padding: 60px 0; grid-column: 1/-1; }

    /* Chatbot Floating Widget */
    .chatbot-fab {
        position: fixed;
        bottom: 30px;
        left: 30px;
        width: 55px;
        height: 55px;
        background: linear-gradient(135deg, #e67e22, #d35400);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.4rem;
        cursor: pointer;
        box-shadow: 0 5px 20px rgba(230,126,34,0.4);
        z-index: 100;
        transition: transform 0.2s;
    }
    .chatbot-fab:hover { transform: scale(1.08); }

    .chatbot-window {
        position: fixed;
        bottom: 100px;
        left: 30px;
        width: 320px;
        height: 400px;
        background: rgba(13, 14, 26, 0.96);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(230, 126, 34, 0.4);
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.6);
        display: none;
        flex-direction: column;
        z-index: 100;
        overflow: hidden;
    }
    .chatbot-header {
        background: rgba(230,126,34,0.18);
        padding: 15px;
        font-weight: 700;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(230,126,34,0.2);
    }
    .chatbot-body {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        font-size: 0.85rem;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .chat-msg { background: rgba(255,255,255,0.06); padding: 10px 14px; border-radius: 12px; max-width: 82%; line-height: 1.4; }
    .chat-msg.bot { align-self: flex-start; color: #ddd; }
    .chat-msg.user { align-self: flex-end; background: #e67e22; color: white; }
    .chatbot-footer { padding: 10px 15px; border-top: 1px solid rgba(255,255,255,0.08); display: flex; gap: 8px; }
    .chatbot-footer input {
        flex: 1;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.12);
        padding: 9px 12px;
        border-radius: 8px;
        color: white;
        font-size: 0.85rem;
        outline: none;
    }

    /* Toast Notification */
    #toast-container { position: fixed; bottom: 30px; right: 30px; z-index: 9999; display: flex; flex-direction: column; gap: 12px; }
    .custom-toast {
        background: rgba(13, 14, 26, 0.95);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(230, 126, 34, 0.4);
        color: #fff;
        padding: 14px 22px;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(230, 126, 34, 0.2);
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.9rem;
        font-weight: 500;
        animation: toastSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        max-width: 350px;
    }
    .custom-toast.success { border-color: rgba(46, 204, 113, 0.5); }
    .custom-toast.success i { color: #2ecc71; font-size: 1.2rem; }
    .custom-toast.error { border-color: rgba(231, 76, 60, 0.5); }
    .custom-toast.error i { color: #e74c3c; font-size: 1.2rem; }

    @keyframes toastSlideIn {
        from { opacity: 0; transform: translateY(20px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes toastFadeOut {
        to { opacity: 0; transform: translateY(10px) scale(0.95); }
    }

    footer { text-align: center; padding: 30px; color: #666; font-size: 0.8rem; border-top: 1px solid rgba(255,255,255,0.06); }
</style>
</head>
<body>
<div class="tech-bg"></div>
<div id="toast-container"></div>

<header class="main-header">
    <a href="index.php" class="brand">
        <div class="logo-icon">🔥</div>
        <div>
            <h2>COMFORT ZONE</h2>
            <span>Flame-grilled &amp; herbal goods</span>
        </div>
    </a>

    <nav class="nav-links">
        <a href="index.php" class="active"><i class="fas fa-home"></i> Home</a>

        <a href="cart.php" class="cart-btn-nav">
            <i class="fas fa-shopping-cart"></i> Cart
            <span class="cart-badge" style="<?= $cartCount > 0 ? '' : 'display: none;' ?>"><?= $cartCount ?></span>
        </a>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
            <a href="staff/orders.php" class="btn-staff-dashboard" style="background: linear-gradient(135deg, #e67e22, #d35400); color: white; border: none;">
                <i class="fas fa-chart-line"></i> Staff Dashboard
            </a>
        <?php endif; ?>

        <a href="my_orders.php"><i class="fas fa-box"></i> My Orders</a>
    </nav>

    <div class="header-actions">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-welcome" style="color: #ccc; font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-user-circle" style="color: #e67e22; font-size: 1.1rem;"></i>
                Hello, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>
            </div>
            <a href="includes/logout.php" class="btn-outline-custom">Logout</a>
        <?php else: ?>
            <a href="includes/login.php" class="btn-outline-custom">Login</a>
            <a href="includes/signup.php" class="btn-solid-custom">Sign Up</a>
        <?php endif; ?>
    </div>
</header>

<div class="hero">
    <h1>Fire-Grilled Meats &amp; Herbal Wellness</h1>
    <p>Browse our menu of flame-grilled specials and natural herbal blends, made fresh daily.</p>

    <div class="search-bar-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="smartSearch" class="search-input" placeholder="Search steaks, chakalaka, herbal teas...">
    </div>
</div>

<div class="categories" id="categoryBar">
    <div class="cat-btn active" data-cat="all">✨ All</div>
    <?php foreach ($categoryLabels as $key => $c): ?>
        <div class="cat-btn" data-cat="<?php echo htmlspecialchars($key); ?>">
            <?php echo $c['icon']; ?> <?php echo htmlspecialchars($c['label']); ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="products" id="productGrid">
    <?php if (empty($products)): ?>
        <div class="empty-msg">No products available right now — check back soon.</div>
    <?php else: ?>
        <?php foreach ($products as $p):
            $isLow = $p['stock'] <= $p['min_stock_level'];
            $catIcon = $categoryLabels[$p['category']]['icon'] ?? '🍽️';

            // Smart Path Resolver for uploads/
            $rawImage = trim((string) ($p['image'] ?? ''));
            $safeImage = '';

            if ($rawImage !== '') {
                if (preg_match('/^https?:\/\//', $rawImage)) {
                    $safeImage = $rawImage;
                } elseif (preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $rawImage)) {
                    $cleanFile = ltrim(str_replace('../', '', $rawImage), '/');
                    $safeImage = (strpos($cleanFile, 'uploads/') === 0) ? $cleanFile : 'uploads/' . $cleanFile;
                }
            }
        ?>
        <div class="p-card"
             data-category="<?php echo htmlspecialchars($p['category']); ?>"
             data-search="<?php echo strtolower(htmlspecialchars($p['name'] . ' ' . $p['description'])); ?>">
            <?php if ($isLow): ?>
                <div class="stock-badge low">Low stock</div>
            <?php elseif ($p['is_featured']): ?>
                <div class="stock-badge featured">Featured</div>
            <?php endif; ?>

            <?php if ($safeImage !== ''): ?>
                <img src="<?php echo htmlspecialchars($safeImage, ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?>"
                     class="product-img-banner"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div class="icon" style="display:none;"><?php echo $catIcon; ?></div>
            <?php else: ?>
                <div class="icon"><?php echo $catIcon; ?></div>
            <?php endif; ?>

            <div class="p-card-body">
                <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                <div class="desc"><?php echo htmlspecialchars($p['description']); ?></div>
                <div class="price-row">
                    <span class="price">R<?php echo number_format($p['price'], 2); ?></span>
                    <?php if ($p['original_price'] && $p['original_price'] > $p['price']): ?>
                        <span class="old-price">R<?php echo number_format($p['original_price'], 2); ?></span>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-add-cart" onclick="addToCart(<?= intval($p['id']) ?>)">
                    <i class="fas fa-cart-plus"></i> Add to Cart
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="empty-msg" id="noResultsMsg" style="display: none;">No matching items found. Try another search term!</div>
    <?php endif; ?>
</div>

<!-- Floating Chatbot Widget -->
<div class="chatbot-fab" onclick="toggleChatbot()"><i class="fas fa-comment-dots"></i></div>
<div class="chatbot-window" id="chatbotWindow">
    <div class="chatbot-header">
        <span><i class="fas fa-robot" style="color:#e67e22;"></i> Comfort Bot</span>
        <i class="fas fa-times" style="cursor:pointer;" onclick="toggleChatbot()"></i>
    </div>
    <div class="chatbot-body" id="chatBody">
        <div class="chat-msg bot">Hi there! Welcome to Comfort Zone. Ask about our hours, delivery, or menu specials!</div>
    </div>
    <div class="chatbot-footer">
        <input type="text" id="chatInput" placeholder="Ask a question..." onkeypress="handleChat(event)">
    </div>
</div>

<footer>&copy; <?php echo date('Y'); ?> Comfort Zone. All rights reserved.</footer>

<script>
// Smart Search & Category Filter
const searchInput = document.getElementById('smartSearch');
const categoryBtns = document.querySelectorAll('.cat-btn');
const noResultsMsg = document.getElementById('noResultsMsg');
let activeCategory = 'all';

function filterProducts() {
    const query = searchInput.value.toLowerCase().trim();
    let visibleCount = 0;

    document.querySelectorAll('.p-card').forEach(card => {
        const searchData = card.dataset.search || '';
        const category = card.dataset.category;
        const matchesCategory = (activeCategory === 'all' || category === activeCategory);
        const matchesSearch = searchData.includes(query);

        if (matchesCategory && matchesSearch) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    if (noResultsMsg) {
        noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

searchInput.addEventListener('input', filterProducts);

categoryBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        categoryBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeCategory = btn.dataset.cat;
        filterProducts();
    });
});

// Chatbot Widget
function toggleChatbot() {
    const win = document.getElementById('chatbotWindow');
    win.style.display = (win.style.display === 'flex') ? 'none' : 'flex';
}

function handleChat(e) {
    if (e.key === 'Enter') {
        const input = document.getElementById('chatInput');
        const text = input.value.trim();
        if (!text) return;

        const chatBody = document.getElementById('chatBody');
        const userMsg = document.createElement('div');
        userMsg.className = 'chat-msg user';
        userMsg.textContent = text;
        chatBody.appendChild(userMsg);
        input.value = '';
        chatBody.scrollTop = chatBody.scrollHeight;

        setTimeout(() => {
            let reply = "You can browse our menu above, open your Cart at the top right, and proceed to checkout anytime!";
            const lower = text.toLowerCase();
            if (lower.includes('time') || lower.includes('open') || lower.includes('hours')) {
                reply = "We are open daily from 10:00 AM to 9:00 PM!";
            } else if (lower.includes('contact') || lower.includes('phone') || lower.includes('call')) {
                reply = "You can reach our staff desk at 031 143 4567 or management at 071 455 8678.";
            } else if (lower.includes('delivery') || lower.includes('pickup')) {
                reply = "We offer both fast local delivery and in-store pickup at checkout.";
            }
            const botMsg = document.createElement('div');
            botMsg.className = 'chat-msg bot';
            botMsg.textContent = reply;
            chatBody.appendChild(botMsg);
            chatBody.scrollTop = chatBody.scrollHeight;
        }, 400);
    }
}

// Toast & Add to Cart (Restored from original index.php)
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `custom-toast ${type}`;

    const iconClass = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
    toast.innerHTML = `<i class="fas ${iconClass}"></i> <span>${message}</span>`;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'toastFadeOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

async function addToCart(productId) {
    const endpoint = 'api/add_to_cart.php';

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                product_id: productId,
                quantity: 1
            })
        });

        if (!response.ok) {
            throw new Error(`Server returned status ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            const badge = document.querySelector('.cart-badge');
            if (badge) {
                badge.textContent = data.cart_count;
                badge.style.display = data.cart_count > 0 ? 'inline-block' : 'none';
            }
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Unable to add product to cart.', 'error');
        }
    } catch (error) {
        console.error('Add to cart error:', error);
        showToast('Connection error. Please check your backend API route path.', 'error');
    }
}
</script>
</body>
</html>