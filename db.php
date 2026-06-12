<?php
// Database Configuration Constants
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1' || $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    // Local development configuration
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3307');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'salvin_db');
    define('IS_DEV', true);
} else {
    // Production (Hostinger) configuration - change these to match your Hostinger database details
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_USER', 'u123456789_salvin_user');
    define('DB_PASS', 'ProductionPasswordHere');
    define('DB_NAME', 'u123456789_salvin_db');
    define('IS_DEV', false);
}

try {
    if (IS_DEV) {
        // Connect to MySQL (without selecting DB first, to auto-create DB if not exists)
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS, [
            PDO::ATTR_TIMEOUT => 2,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Create database if it does not exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    }
    
    // Connect to the specific database
    $db = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_TIMEOUT => 2,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Auto-create directories
    $uploadCategoryDir = __DIR__ . '/uploads/category';
    $uploadProductDir = __DIR__ . '/uploads/products';
    
    if (!file_exists($uploadCategoryDir)) {
        mkdir($uploadCategoryDir, 0777, true);
    }
    if (!file_exists($uploadProductDir)) {
        mkdir($uploadProductDir, 0777, true);
    }

    // Auto-create Categories Table
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        image VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Auto-create Products Table
    $db->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        image VARCHAR(255) NOT NULL,
        description TEXT NULL,
        rate VARCHAR(255) NULL,
        CONSTRAINT fk_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Auto-create Inquiries Table
    $db->exec("CREATE TABLE IF NOT EXISTS inquiries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NULL,
        subject VARCHAR(255) NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Helper sanitization function for image renaming
    function sanitize_filename($name) {
        $name = strtolower($name);
        $name = str_replace(' ', '_', $name);
        $name = preg_replace('/[^a-z0-9_]/', '', $name); // remove special chars except alphanumeric and underscores
        $name = preg_replace('/_+/', '_', $name); // remove multiple underscores
        return $name;
    }


} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>
