USE di_internet_technologies_project;

CREATE TABLE `content_items` (
  `id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `youtube_url` varchar(500) NOT NULL,
  `youtube_id` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` varchar(20) DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `content_items` (`id`, `list_id`, `user_id`, `title`, `youtube_url`, `youtube_id`, `description`, `duration`, `thumbnail_url`, `added_at`) VALUES
(1, 1, 1, 'Εισαγωγή στη JavaScript', 'https://www.youtube.com/watch?v=W6NZfCO5SIk', 'W6NZfCO5SIk', 'Βασικές έννοιες JavaScript για αρχάριους', NULL, NULL, '2025-05-28 20:11:32'),
(2, 1, 1, 'HTML και CSS Basics', 'https://www.youtube.com/watch?v=UB1O30fR-EE', 'UB1O30fR-EE', 'Μάθετε HTML και CSS από την αρχή', NULL, NULL, '2025-05-28 20:11:32'),
(3, 2, 1, 'Lofi Hip Hop Mix', 'https://www.youtube.com/watch?v=jfKfPfyJRdk', 'jfKfPfyJRdk', '24/7 Lofi hip hop για διάβασμα', NULL, NULL, '2025-05-28 20:11:32'),
(4, 3, 2, 'React Conference 2024', 'https://www.youtube.com/watch?v=8pDqJVdNa44', '8pDqJVdNa44', 'Τα νέα του React για το 2024', NULL, NULL, '2025-05-28 20:11:32');

CREATE TABLE `content_lists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `content_lists` (`id`, `user_id`, `title`, `description`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 1, 'Εκπαιδευτικά Βίντεο Προγραμματισμού', 'Συλλογή εκπαιδευτικών βίντεο για προγραμματισμό', 1, '2025-05-28 20:11:32', '2025-05-28 20:11:32'),
(2, 1, 'Μουσική για Διάβασμα', 'Ήρεμη μουσική για συγκέντρωση', 0, '2025-05-28 20:11:32', '2025-05-28 20:11:32'),
(3, 2, 'Tech Talks 2024', 'Ενδιαφέρουσες παρουσιάσεις τεχνολογίας', 1, '2025-05-28 20:11:32', '2025-05-28 20:11:32'),
(4, 3, 'Ταξιδιωτικά Vlogs', 'Ταξίδια γύρω από τον κόσμο', 1, '2025-05-28 20:11:32', '2025-05-28 20:11:32');

CREATE TABLE `search_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `search_query` varchar(500) DEFAULT NULL,
  `search_type` enum('content','user','list') DEFAULT 'content',
  `results_count` int(11) DEFAULT 0,
  `searched_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `email`, `password_hash`, `created_at`, `updated_at`) VALUES
(1, 'Δημήτρης', 'Ρίγγας', 'admin', 'admin@streamify.gr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-05-28 20:11:32', '2025-05-28 20:11:32'),
(2, 'Ελένη', 'Χριστοπούλου', 'eleni', 'eleni@streamify.gr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-05-28 20:11:32', '2025-05-28 20:11:32'),
(3, 'Γιάννης', 'Παπαδόπουλος', 'giannis', 'giannis@example.gr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-05-28 20:11:32', '2025-05-28 20:11:32');

CREATE TABLE `user_follows` (
  `id` int(11) NOT NULL,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user_follows` (`id`, `follower_id`, `following_id`, `created_at`) VALUES
(1, 2, 1, '2025-05-28 20:11:32'),
(2, 3, 1, '2025-05-28 20:11:32'),
(3, 3, 2, '2025-05-28 20:11:32'),
(4, 1, 2, '2025-05-28 20:11:32');

ALTER TABLE `content_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_list_id` (`list_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_youtube_id` (`youtube_id`),
  ADD KEY `idx_added_at` (`added_at`);

ALTER TABLE `content_lists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_public` (`is_public`),
  ADD KEY `idx_created_at` (`created_at`);

ALTER TABLE `search_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_search_type` (`search_type`),
  ADD KEY `idx_searched_at` (`searched_at`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

ALTER TABLE `user_follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_follow` (`follower_id`,`following_id`),
  ADD KEY `idx_follower` (`follower_id`),
  ADD KEY `idx_following` (`following_id`);

ALTER TABLE `content_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `content_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `search_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `content_items`
  ADD CONSTRAINT `content_items_ibfk_1` FOREIGN KEY (`list_id`) REFERENCES `content_lists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_items_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `content_lists`
  ADD CONSTRAINT `content_lists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `search_logs`
  ADD CONSTRAINT `search_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `user_follows`
  ADD CONSTRAINT `user_follows_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_follows_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;
