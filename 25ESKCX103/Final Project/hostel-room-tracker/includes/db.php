<?php
// Database helper and PDO initializer

$configFile = dirname(__DIR__) . '/config.php';

if (!file_exists($configFile)) {
    // If not installed, redirect to install.php
    $requestUri = $_SERVER['REQUEST_URI'];
    if (strpos($requestUri, 'install.php') === false) {
        header("Location: " . (strpos($requestUri, '/student/') !== false || strpos($requestUri, '/admin/') !== false ? '../' : '') . "install.php");
        exit;
    }
} else {
    require_once $configFile;
}

try {
    if (defined('DB_HOST')) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage() . ". Please verify credentials in config.php or run the installer again.");
}
?>
