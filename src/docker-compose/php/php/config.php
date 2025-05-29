<?php 
// Database configuration
define('DB_HOST', 'di_inter_tech_mysql');        // ή '127.0.0.1' αν τρέχει το MySQL τοπικά
define('DB_NAME', 'di_internet_technologies_project');
define('DB_USER', 'webuser');
define('DB_PASS', 'webpass');

// Δημιουργία βάσης (αν δεν υπάρχει)
try {
    $pdo_setup = new PDO("mysql:host=" . DB_HOST . ";charset=utf8", DB_USER, DB_PASS);
    $pdo_setup->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_setup->exec(
        "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8 COLLATE utf8_general_ci"
    );
} catch(PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}

// Σύνδεση με τη βάση
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC ]
    );
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Έναρξη session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// YouTube API (αλλάξτε με τα δικά σας κλειδιά!)
define('YOUTUBE_API_KEY',        'AIzaSyCu3hRyHBBikQW158aR5MXGkQScOX88COs');
define('YOUTUBE_CLIENT_ID',      '378754135872-v7ull544ibccmovrppds20346bij1p7j.apps.googleusercontent.com');
define('YOUTUBE_CLIENT_SECRET',  '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');

// Βοηθητικές συναρτήσεις
function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function showError($message) {
    return "<div class='error-message'>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</div>";
}

function showSuccess($message) {
    return "<div class='success-message'>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</div>";
}

// Δημιουργία πινάκων αν δεν υπάρχουν
try {
    // users
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
            INDEX (username),
            INDEX (email)
        ) ENGINE=InnoDB
    ");
    // content_lists
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
            INDEX (user_id),
            INDEX (is_public)
        ) ENGINE=InnoDB
    ");
    // content_items
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
            INDEX (list_id),
            INDEX (user_id),
            INDEX (youtube_id)
        ) ENGINE=InnoDB
    ");
    // user_follows
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_follows (
            id INT AUTO_INCREMENT PRIMARY KEY,
            follower_id INT NOT NULL,
            following_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY (follower_id, following_id),
            INDEX (follower_id),
            INDEX (following_id)
        ) ENGINE=InnoDB
    ");

    // Δείγμα χρηστών
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        $pw = password_hash('password', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT INTO users (first_name, last_name, username, email, password_hash) VALUES
            ('Admin','User','admin','admin@streamify.gr','$pw'),
            ('Test','User','test','test@streamify.gr','$pw')
        ");
    }

} catch (PDOException $e) {
    error_log("DB setup note: " . $e->getMessage());
}
