<?php
session_start();
require_once '../config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            $db = getDB();
            
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                // Insert new user including phone number
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $db->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, 'customer')");
                
                if ($insertStmt->execute([$full_name, $email, $hashed_password, $phone])) {
                    $_SESSION['user_id'] = $db->lastInsertId();
                    $_SESSION['user_name'] = $full_name;
                    $_SESSION['role'] = 'customer';

                    header("Location: ../index.php");
                    exit;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        } catch (Exception $e) {
            $error = 'Database Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up - Comfort Zone</title>
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

    .auth-wrapper {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px 20px;
    }

    .auth-card {
        background: rgba(255, 255, 255, 0.035);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 28px 35px;
        width: 100%;
        max-width: 520px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        backdrop-filter: blur(10px);
    }

    .auth-card h2 {
        font-family: 'Orbitron', sans-serif;
        font-size: 1.5rem;
        margin-bottom: 4px;
        color: #fff;
    }

    .auth-card p {
        color: #999;
        font-size: 0.85rem;
        margin-bottom: 16px;
    }

    .alert-error {
        background: rgba(231, 76, 60, 0.15);
        border: 1px solid rgba(231, 76, 60, 0.3);
        color: #e74c3c;
        padding: 10px 14px;
        border-radius: 10px;
        font-size: 0.85rem;
        margin-bottom: 15px;
    }

    .form-group {
        margin-bottom: 12px;
    }

    .form-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #ccc;
        margin-bottom: 5px;
    }

    .form-control {
        width: 100%;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        padding: 9px 14px;
        color: white;
        font-size: 0.9rem;
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
        padding: 11px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 6px 20px rgba(230, 126, 34, 0.35);
        margin-top: 6px;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(230, 126, 34, 0.5);
    }

    .auth-footer-text {
        text-align: center;
        margin-top: 14px;
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

<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Create Account</h2>
        <p>Sign up to start placing orders at Comfort Zone.</p>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="signup.php" method="POST">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-control" required placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="Enter your email">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" class="form-control" placeholder="Enter your phone number">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Create a password (min. 6 chars)">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Confirm your password">
            </div>

            <button type="submit" class="btn-submit">Create Account</button>
        </form>

        <div class="auth-footer-text">
            Already have an account? <a href="../includes/login.php">Sign in here</a>
        </div>
    </div>
</div>

<footer>&copy; <?= date('Y'); ?> Comfort Zone. All rights reserved.</footer>

</body>
</html>
```[cite: 10, 11, 12]

<FollowUp label="Want me to check your login.php file next to ensure sign-in validation matches this user structure?" query="Yes, check my login.php file next to ensure sign-in validation matches this user structure."/>