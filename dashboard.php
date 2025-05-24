<?php
require_once 'config.php';
requireLogin();

// Get user information
$stmt = $pdo->prepare("SELECT first_name, last_name, username, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user's lists
$stmt = $pdo->prepare("
    SELECT cl.*, COUNT(ci.id) as item_count 
    FROM content_lists cl 
    LEFT JOIN content_items ci ON cl.id = ci.list_id 
    WHERE cl.user_id = ? 
    GROUP BY cl.id 
    ORDER BY cl.updated_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$userLists = $stmt->fetchAll();

// Get lists from followed users (public only)
$stmt = $pdo->prepare("
    SELECT cl.*, u.username, u.first_name, u.last_name, COUNT(ci.id) as item_count
    FROM content_lists cl
    JOIN users u ON cl.user_id = u.id
    JOIN user_follows uf ON u.id = uf.following_id
    LEFT JOIN content_items ci ON cl.id = ci.list_id
    WHERE uf.follower_id = ? AND cl.is_public = 1
    GROUP BY cl.id
    ORDER BY cl.updated_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$followedLists = $stmt->fetchAll();

// Get recent activity
$stmt = $pdo->prepare("
    SELECT ci.*, cl.title as list_title
    FROM content_items ci
    JOIN content_lists cl ON ci.list_id = cl.id
    WHERE ci.user_id = ?
    ORDER BY ci.added_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recentActivity = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Streamify</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">Streamify</h1>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="my-lists.php" class="nav-link">Οι Λίστες μου</a>
                <a href="search.php" class="nav-link">Αναζήτηση</a>
                <a href="profile.php" class="nav-link">Προφίλ</a>
                <a href="logout.php" class="nav-link">Αποσύνδεση</a>
                <button id="theme-toggle" class="theme-btn">🌙</button>
            </div>
        </div>
    </nav>

    <main class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1>Καλώς ήρθες, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                <a href="create-list.php" class="btn btn-primary">Νέα Λίστα</a>
            </div>

            <div class="dashboard-grid">
                <!-- User Stats -->
                <div class="list-card">
                    <div class="list-card-header">
                        <h3>Στατιστικά</h3>
                    </div>
                    <div class="list-card-body">
                        <p><strong>Οι Λίστες μου:</strong> <?php echo count($userLists); ?></p>
                        <p><strong>Συνολικά βίντεο:</strong> 
                           <?php echo array_sum(array_column($userLists, 'item_count')); ?></p>
                        <p><strong>Πρόσφατη δραστηριότητα:</strong> 
                           <?php echo count($recentActivity); ?> βίντεο</p>
                    </div>
                </div>

                <!-- Recent Lists -->
                <div class="list-card">
                    <div class="list-card-header">
                        <h3>Οι Λίστες μου</h3>
                    </div>
                    <div class="list-card-body">
                        <?php if (empty($userLists)): ?>
                            <p>Δεν έχετε δημιουργήσει ακόμα λίστες.</p>
                            <a href="create-list.php" class="btn btn-primary">Δημιουργία πρώτης λίστας</a>
                        <?php else: ?>
                            <?php foreach (array_slice($userLists, 0, 3) as $list): ?>
                                <div style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color);">
                                    <h4><a href="view-list.php?id=<?php echo $list['id']; ?>" style="text-decoration: none; color: var(--accent-primary);">
                                        <?php echo htmlspecialchars($list['title']); ?>
                                    </a></h4>
                                    <p style="font-size: 0.9rem; color: var(--text-secondary);">
                                        <?php echo $list['item_count']; ?> βίντεο • 
                                        <?php echo $list['is_public'] ? 'Δημόσια' : 'Ιδιωτική'; ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                            <a href="my-lists.php" class="btn btn-secondary">Προβολή όλων</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Followed Users' Lists -->
                <div class="list-card">
                    <div class="list-card-header">
                        <h3>Λίστες που ακολουθώ</h3>
                    </div>
                    <div class="list-card-body">
                        <?php if (empty($followedLists)): ?>
                            <p>Δεν ακολουθείτε κανέναν χρήστη ακόμα.</p>
                            <a href="search.php" class="btn btn-primary">Αναζήτηση χρηστών</a>
                        <?php else: ?>
                            <?php foreach (array_slice($followedLists, 0, 3) as $list): ?>
                                <div style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color);">
                                    <h4><a href="view-list.php?id=<?php echo $list['id']; ?>" style="text-decoration: none; color: var(--accent-primary);">
                                        <?php echo htmlspecialchars($list['title']); ?>
                                    </a></h4>
                                    <p style="font-size: 0.9rem; color: var(--text-secondary);">
                                        Από <?php echo htmlspecialchars($list['username']); ?> • 
                                        <?php echo $list['item_count']; ?> βίντεο
                                    </p>
                                </div>
                            <?php endforeach; ?>
                            <a href="following.php" class="btn btn-secondary">Προβολή όλων</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="list-card">
                    <div class="list-card-header">
                        <h3>Πρόσφατη δραστηριότητα</h3>
                    </div>
                    <div class="list-card-body">
                        <?php if (empty($recentActivity)): ?>
                            <p>Δεν έχετε προσθέσει βίντεο ακόμα.</p>
                        <?php else: ?>
                            <?php foreach ($recentActivity as $item): ?>
                                <div style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color);">
                                    <h4 style="font-size: 1rem;"><?php echo htmlspecialchars($item['title']); ?></h4>
                                    <p style="font-size: 0.9rem; color: var(--text-secondary);">
                                        Στη λίστα "<?php echo htmlspecialchars($item['list_title']); ?>" • 
                                        <?php echo date('d/m/Y H:i', strtotime($item['added_at'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="list-card">
                    <div class="list-card-header">
                        <h3>Γρήγορες ενέργειες</h3>
                    </div>
                    <div class="list-card-body">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <a href="create-list.php" class="btn btn-primary">Νέα Λίστα</a>
                            <a href="search.php" class="btn btn-secondary">Αναζήτηση</a>
                            <a href="export.php" class="btn btn-secondary">Εξαγωγή Δεδομένων</a>
                        </div>
                    </div>
                </div>

                <!-- Help & Info -->
                <div class="list-card">
                    <div class="list-card-header">
                        <h3>Βοήθεια</h3>
                    </div>
                    <div class="list-card-body">
                        <p>Χρειάζεστε βοήθεια;</p>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <a href="help.html" class="btn btn-secondary">Οδηγίες χρήσης</a>
                            <a href="about.html" class="btn btn-secondary">Σχετικά με το Streamify</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Streamify - Ιόνιο Πανεπιστήμιο</p>
        </div>
    </footer>

    <script src="theme.js"></script>
</body>
</html>