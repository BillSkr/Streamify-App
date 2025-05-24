-- Streamify Database Initialization
CREATE DATABASE IF NOT EXISTS streamify CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE streamify;

-- Users table
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
) ENGINE=InnoDB;

-- Content lists table
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
    INDEX idx_public (is_public),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Content items table
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
    INDEX idx_youtube_id (youtube_id),
    INDEX idx_added_at (added_at)
) ENGINE=InnoDB;

-- User follows table
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
) ENGINE=InnoDB;

-- Search logs table (optional, for analytics)
CREATE TABLE IF NOT EXISTS search_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    search_query VARCHAR(500),
    search_type ENUM('content', 'user', 'list') DEFAULT 'content',
    results_count INT DEFAULT 0,
    searched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_search_type (search_type),
    INDEX idx_searched_at (searched_at)
) ENGINE=InnoDB;

-- Insert sample data for testing
INSERT INTO users (first_name, last_name, username, email, password_hash) VALUES
('Δημήτρης', 'Ρίγγας', 'admin', 'admin@streamify.gr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Ελένη', 'Χριστοπούλου', 'eleni', 'eleni@streamify.gr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Γιάννης', 'Παπαδόπουλος', 'giannis', 'giannis@example.gr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Sample content lists
INSERT INTO content_lists (user_id, title, description, is_public) VALUES
(1, 'Εκπαιδευτικά Βίντεο Προγραμματισμού', 'Συλλογή εκπαιδευτικών βίντεο για προγραμματισμό', TRUE),
(1, 'Μουσική για Διάβασμα', 'Ήρεμη μουσική για συγκέντρωση', FALSE),
(2, 'Tech Talks 2024', 'Ενδιαφέρουσες παρουσιάσεις τεχνολογίας', TRUE),
(3, 'Ταξιδιωτικά Vlogs', 'Ταξίδια γύρω από τον κόσμο', TRUE);

-- Sample content items (you would need real YouTube video IDs)
INSERT INTO content_items (list_id, user_id, title, youtube_url, youtube_id, description) VALUES
(1, 1, 'Εισαγωγή στη JavaScript', 'https://www.youtube.com/watch?v=W6NZfCO5SIk', 'W6NZfCO5SIk', 'Βασικές έννοιες JavaScript για αρχάριους'),
(1, 1, 'HTML και CSS Basics', 'https://www.youtube.com/watch?v=UB1O30fR-EE', 'UB1O30fR-EE', 'Μάθετε HTML και CSS από την αρχή'),
(2, 1, 'Lofi Hip Hop Mix', 'https://www.youtube.com/watch?v=jfKfPfyJRdk', 'jfKfPfyJRdk', '24/7 Lofi hip hop για διάβασμα'),
(3, 2, 'React Conference 2024', 'https://www.youtube.com/watch?v=8pDqJVdNa44', '8pDqJVdNa44', 'Τα νέα του React για το 2024');

-- Sample follows
INSERT INTO user_follows (follower_id, following_id) VALUES
(2, 1),
(3, 1),
(3, 2),
(1, 2);