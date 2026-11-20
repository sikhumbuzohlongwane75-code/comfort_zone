<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_role($required_role) {
    $base_url = defined('SITE_URL') ? SITE_URL : '/comfort_zoned/';

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        header('Location: ' . $base_url . 'includes/login.php?error=unauthorized');
        exit();
    }

    $current_role = $_SESSION['user_role'];

    // Admin can access everything
    if ($current_role === 'admin') {
        return;
    }

    // If staff is required, allow staff
    if ($required_role === 'staff' && $current_role === 'staff') {
        return;
    }

    // Otherwise unauthorized
    header('Location: ' . $base_url . '../includes/login.php?error=access_denied');
    exit();
}
?>