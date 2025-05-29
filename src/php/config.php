<?php
// Database configuration - try multiple connection methods
$servername = '127.0.0.1'; // Use IP instead of localhost to avoid socket issues
$username = 'root';
$password = '';
$dbname = 'di_internet_technologies_project';
$port = 3306; // Specify port explicitly

// Define database constants
define('DB_HOST', $servername);
define('DB_USER', $username);
define('DB_PASS', $password);
define('DB_NAME', $dbname);
define('DB_PORT', $port);

// Try to connect with explicit port
$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: {$conn->connect_error}");
} else {
    mysqli_set_charset($conn, 'utf8');	
}

// First, create database if it doesn't exist
try {
    $pdo_setup = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8", DB_USER, DB_PASS);
    $pdo_setup->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_setup->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8 COLLATE utf8_general_ci");
} catch(PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Session configuration
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// YouTube API configuration (REPLACE WITH YOUR OWN!)
define('YOUTUBE_API_KEY', 'AIzaSyCu3hRyHBBikQW158aR5MXGkQScOX88COs');
define('YOUTUBE_CLIENT_ID', '378754135872-v7ull544ibccmovrppds20346bij1p7j.apps.googleusercontent.com');
define('YOUTUBE_CLIENT_SECRET', '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');

// Helper functions - wrap in function_exists to prevent redeclaration errors
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: login.php');
            exit;
        }
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (!function_exists('hashPassword')) {
    function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('verifyPassword')) {
    function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

// Error handling
if (!function_exists('showError')) {
    function showError($message) {
        return "<div class='error-message'>" . htmlspecialchars($message) . "</div>";
    }
}

if (!function_exists('showSuccess')) {
    function showSuccess($message) {
        return "<div class='success-message'>" . htmlspecialchars($message) . "</div>";
    }
}

// Create tables if they don't exist
try {
    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_email (email)
        ) ENGINE=InnoDB
    ");

    // Content lists table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content_lists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            is_public BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_public (is_public)
        ) ENGINE=InnoDB
    ");

    // Content items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            list_id INT NOT NULL,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            youtube_url VARCHAR(500) NOT NULL,
            youtube_id VARCHAR(50) NOT NULL,
            description TEXT,
            duration VARCHAR(20),
            thumbnail_url VARCHAR(500),
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (list_id) REFERENCES content_lists(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_list_id (list_id),
            INDEX idx_user_id (user_id),
            INDEX idx_youtube_id (youtube_id)
        ) ENGINE=InnoDB
    ");

    // User follows table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_follows (
            id INT AUTO_INCREMENT PRIMARY KEY,
            follower_id INT NOT NULL,
            following_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_follow (follower_id, following_id),
            INDEX idx_follower (follower_id),
            INDEX idx_following (following_id)
        ) ENGINE=InnoDB
    ");

    // Insert sample users (only if none exist)
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        
        $pdo->exec("
            INSERT INTO users (first_name, last_name, username, email, password_hash) VALUES
            ('Admin', 'User', 'admin', 'admin@streamify.gr', '$hashedPassword'),
            ('Test', 'User', 'test', 'test@streamify.gr', '$hashedPassword')
        ");
    }

} catch(PDOException $e) {
    // Tables might already exist, that's okay
    error_log("Database setup note: " . $e->getMessage());
}
?>
