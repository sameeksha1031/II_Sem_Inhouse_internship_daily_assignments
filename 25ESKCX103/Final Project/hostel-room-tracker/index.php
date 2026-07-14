<?php
// Main Router Redirect Page

$configFile = __DIR__ . '/config.php';

if (!file_exists($configFile)) {
    header("Location: install.php");
    exit;
}

require_once __DIR__ . '/includes/auth.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user = get_logged_in_user();
if ($user['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit;
} else {
    header("Location: student/dashboard.php");
    exit;
}
?>
