<?php
session_start();
require_once '../config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all password fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            $db = getDB();
            
            // Check if token exists and is valid (e.g., created within the last hour)
            $stmt = $db->prepare("SELECT email FROM password_resets WHERE token = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)");
            $stmt->execute([$token]);
            $resetRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resetRecord) {
                $email = $resetRecord['email'];
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Update user's password
                $update = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
                $update->execute([$hashedPassword, $email]);

                // Clear out used token
                $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

                $success = 'Your password has been successfully updated! You can now sign in with your new password.';
            } else {
                $error = 'This password reset link is invalid or has expired.';
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
<title>Create New Password - Comfort Zone</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Orbitron:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Inter', sans-serif; background: #0d0e1a; color: #eee;
        min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden;
    }
    .tech-bg {
        position: fixed; inset: 0; z-index: -1;
        background: radial-gradient(circle at 15% 20%, rgba(230,126,34,0.15), transparent 40%),
                    radial-gradient(circle at 85% 75%, rgba(52,152,219,0.12), transparent 45%),
                    linear-gradient(#0d0e1a, #14162b 60%, #0d0e1a);
    }
    .tech-bg::before {
        content: ""; position: absolute; inset: 0;
        background-image: linear-gradient(rgba(230,126,34,0.06) 1px, transparent 1px),
                            linear-gradient(90deg, rgba(230,126,34,0.06) 1px, transparent 1px);
        background-size: 42px 42px;
        mask-image: radial-gradient(ellipse 80% 60% at 50% 20%, black 40%, transparent 90%);
    }
    .main-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 18px 40px; background: rgba(13,14,26,0.85); backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(230,126,34,0.15); position: sticky; top: 0; z-index: 50;
    }
    .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: white; }
    .brand .logo-icon {
        width: 42px; height: 42px; background: linear-gradient(135deg, #e67e22, #d35400);
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        font-size: 22px; box-shadow: 0 0 20px rgba(230,126,34,0.5);
    }
    .brand h2 { font-family: 'Orbitron', sans-serif; font-size: 1.15rem; letter-spacing: 1px; }
    .brand span { display: block; font-size: 0.7rem; color: #888; }
    .nav-links a {
        color: #ccc; text-decoration: none; font-weight: 600; font-size: 0.9rem;
        padding: 9px 16px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1);
        display: flex; align-items: center; gap: 8px; transition: all 0.25s;
    }
    .nav-links a:hover { border-color: #e67e22; color: #e67e22; background: rgba(230,126,34,0.05); }
    .auth-wrapper { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
    .auth-card {
        background: rgba(255, 255, 255, 0.035); border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px; padding: 40px; width: 100%; max-width: 440px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.4); backdrop-filter: blur(10px);
    }
    .auth-card h2 { font-family: 'Orbitron', sans-serif; font-size: 1.6rem; margin-bottom: 8px; color: #fff; }
    .auth-card p { color: #999; font-size: 0.9rem; margin-bottom: 25px; }
    .alert-error {
        background: rgba(231, 76, 60, 0.15); border: 1px solid rgba(231, 76, 60, 0.3);
        color: #e74c3c; padding: 12px 16px; border-radius: 10px; font-size: 0.85rem; margin-bottom: 20px;
    }
    .alert-success {
        background: rgba(46, 204, 113, 0.15); border: 1px solid rgba(46, 204, 113, 0.3);
        color: #2ecc71; padding: 12px 16px; border-radius: 10px; font-size: 0.85rem; margin-bottom: 20px;
    }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #ccc; margin-bottom: 8px; }
    .form-control {
        width: 100%; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px; padding: 12px 16px; color: white; font-size: 0.95rem; transition: all 0.25s;
    }
    .form-control:focus { outline: none; border-color: #e67e22; background: rgba(230, 126, 34, 0.03); box-shadow: 0 0 10px rgba(230, 126, 34, 0.2); }
    .btn-submit {
        width: 100%; background: linear-gradient(135deg, #e67e22, #d35400); color: white; border: none;
        padding: 13px; border-radius: 10px; font-weight: 700; font-size: 0.95rem; cursor: pointer;
        transition: all 0.3s; box-shadow: 0 6px 20px rgba(230, 126, 34, 0.35); margin-top: 10px;
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(230, 126, 34, 0.5); }
    .auth-footer-text { text-align: center; margin-top: 20px; font-size: 0.85rem; color: #888; }
    .auth-footer-text a { color: #e67e22; text-decoration: none; font-weight: 600; }
    .auth-footer-text a:hover { text-decoration: underline; }
    footer { text-align: center; padding: 20px; color: #666; font-size: 0.8rem; border-top: 1px solid rgba(255,255,255,0.06); }
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
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Sign In</a>
    </nav>
</header>

<div class="auth-wrapper">
    <div class="auth-card">
        <h2>New Password</h2>
        <p>Please enter your new password below.</p>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <div style="margin-top: 20px; text-align: center;">
                <a href="../includes/login.php" class="btn-submit" style="display: block; text-decoration: none; line-height: normal;">Proceed to Login</a>
            </div>
        <?php elseif (empty($token)): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> No reset token provided. Please request a new link.</div>
            <div class="auth-footer-text"><a href="forgot-password.php">Request new link</a></div>
        <?php else: ?>
            <form action="reset-password.php?token=<?= htmlspecialchars($token) ?>" method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="At least 6 characters">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Re-enter password">
                </div>

                <button type="submit" class="btn-submit">Update Password</button>
            </form>
        <?php endif; ?>

        <?php if (empty($success)): ?>
            <div class="auth-footer-text">
                Remember your password? <a href="../includes/login.php">Sign in</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer>&copy; <?= date('Y'); ?> Comfort Zone. All rights reserved.</footer>
</body>
</html>