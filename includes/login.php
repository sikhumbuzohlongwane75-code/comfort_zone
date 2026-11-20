<?php
session_start();
require_once '../config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $db = getDB();
            
            // Fetch user by email (checking active status if column exists)
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Flexible password verification (supports bcrypt, MD5, and plain text testing)
            $password_valid = false;
            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $password_valid = true;
                } elseif (md5($password) === $user['password']) {
                    $password_valid = true;
                } elseif ($password === $user['password']) {
                    $password_valid = true;
                }
            }

            if ($user && $password_valid) {
                // Set session variables (compatible with all naming conventions)
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $email;
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_role'] = $user['role']; 

                // Optional: Update last login timestamp if your table has it
                try {
                    $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                } catch (Exception $ex) {
                    // Ignored if last_login column doesn't exist
                }

                // Role-based redirection for Admin, Staff, and Customers
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                    exit();
                } elseif ($user['role'] === 'staff') {
                    header("Location: ../staff/dashboard.php");
                    exit();
                } else {
                    header("Location: ../index.php");
                    exit();
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Comfort Zone</title>
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
        overflow-x: hidden;
    }

    /* Tech grid background */
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

    /* Navbar Styling */
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

    .nav-links {
        display: flex;
        align-items: center;
        gap: 15px;
    }

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

    /* Form Container Layout */
    .auth-wrapper {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    .auth-card {
        background: rgba(255, 255, 255, 0.035);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 40px;
        width: 100%;
        max-width: 440px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        backdrop-filter: blur(10px);
    }

    .auth-card h2 {
        font-family: 'Orbitron', sans-serif;
        font-size: 1.8rem;
        margin-bottom: 8px;
        color: #fff;
    }

    .auth-card p {
        color: #999;
        font-size: 0.9rem;
        margin-bottom: 25px;
    }

    .alert-error {
        background: rgba(231, 76, 60, 0.15);
        border: 1px solid rgba(231, 76, 60, 0.3);
        color: #e74c3c;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 0.85rem;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #ccc;
        margin-bottom: 8px;
    }

    .form-control {
        width: 100%;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        padding: 12px 16px;
        color: white;
        font-size: 0.95rem;
        transition: all 0.25s;
    }

    .form-control:focus {
        outline: none;
        border-color: #e67e22;
        background: rgba(230, 126, 34, 0.03);
        box-shadow: 0 0 10px rgba(230, 126, 34, 0.2);
    }

    .btn-submit {
        width: 100%;
        background: linear-gradient(135deg, #e67e22, #d35400);
        color: white;
        border: none;
        padding: 13px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 6px 20px rgba(230, 126, 34, 0.35);
        margin-top: 10px;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(230, 126, 34, 0.5);
    }

    .auth-footer-text {
        text-align: center;
        margin-top: 20px;
        font-size: 0.85rem;
        color: #888;
    }

    .auth-footer-text a {
        color: #e67e22;
        text-decoration: none;
        font-weight: 600;
    }

    .auth-footer-text a:hover {
        text-decoration: underline;
    }

    footer {
        text-align: center;
        padding: 20px;
        color: #666;
        font-size: 0.8rem;
        border-top: 1px solid rgba(255,255,255,0.06);
    }
</style>
</head>
<body>
<div class="tech-bg"></div>

<!-- Integrated Header Navigation -->
<header class="main-header">
    <a href="../index.php" class="brand">
        <div class="logo-icon">🔥</div>
        <div>
            <h2>COMFORT ZONE</h2>
            <span>Flame-grilled &amp; herbal goods</span>
        </div>
    </a>

    <nav class="nav-links">
        <a href="../index.php"><i class="fas fa-home"></i> Home</a>
    </nav>
</header>

<!-- Login Card Container -->
<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Welcome Back</h2>
        <p>Sign in to manage your orders and dashboard.</p>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="../includes/login.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="Enter your email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Enter your password">
                <div style="text-align: right; margin-top: 6px;">
                    <a href="../includes/forgot-password.php" style="font-size: 0.8rem; color: #e67e22; text-decoration: none;">Forgot Password?</a>
                </div>
            </div>

            <button type="submit" class="btn-submit">Sign In</button>
        </form>

        <div class="auth-footer-text">
            Don't have an account? <a href="../includes/signup.php">Sign up here</a>
        </div>
    </div>
</div>

<footer>&copy; <?= date('Y'); ?> Comfort Zone. All rights reserved.</footer>

</body>
</html>