<?php
require_once 'config.php';
requireLogin();

// Handle list deletion
if (isset($_POST['delete_list'])) {
    $listId = (int)$_POST['list_id'];
    
    // Verify that the list belongs to the current user
    $stmt = $pdo->prepare("SELECT id FROM content_lists WHERE id = ? AND user_id = ?");
    $stmt->execute([$listId, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        try {
            $stmt = $pdo->prepare("DELETE FROM content_lists WHERE id = ? AND user_id = ?");
            $stmt->execute([$listId, $_SESSION['user_id']]);
            $success = 'Η λίστα διαγράφηκε επιτυχώς.';
        } catch (PDOException $e) {
            $error = 'Σφάλμα κατά τη διαγραφή της λίστας.';
        }
    }
}

// Get user's lists with content count
$stmt = $pdo->prepare("
    SELECT cl.*, COUNT(ci.id) as item_count,
           MAX(ci.added_at) as last_activity
    FROM content_lists cl 
    LEFT JOIN content_items ci ON cl.id = ci.list_id 
    WHERE cl.user_id = ? 
    GROUP BY cl.id 
    ORDER BY cl.updated_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$userLists = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Οι Λίστες μου - Streamify</title>
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

    <main class="main-content">
        <div class="container">
            <div class="dashboard-header">
                <h1>Οι Λίστες μου</h1>
                <a href="create-list.php" class="btn btn-primary">+ Νέα Λίστα</a>
            </div>

            <?php if (isset($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (empty($userLists)): ?>
                <div class="list-card">
                    <div class="list-card-body" style="text-align: center; padding: 3rem;">
                        <h3>Δεν έχετε δημιουργήσει ακόμα λίστες</h3>
                        <p style="margin: 1rem 0; color: var(--text-secondary);">
                            Δημιουργήστε την πρώτη σας λίστα και αρχίστε να οργανώνετε τα αγαπημένα σας βίντεο!
                        </p>
                        <a href="create-list.php" class="btn btn-primary">Δημιουργία πρώτης λίστας</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="dashboard-grid">
                    <?php foreach ($userLists as $list): ?>
                        <div class="list-card">
                            <div class="list-card-header">
                                <h3><?php echo htmlspecialchars($list['title']); ?></h3>
                                <span style="font-size: 0.9rem; opacity: 0.9;">
                                    <?php echo $list['is_public'] ? '🌐 Δημόσια' : '🔒 Ιδιωτική'; ?>
                                </span>
                            </div>
                            
                            <div class="list-card-body">
                                <?php if (!empty($list['description'])): ?>
                                    <p style="margin-bottom: 1rem; color: var(--text-secondary);">
                                        <?php echo htmlspecialchars($list['description']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div style="margin-bottom: 1rem;">
                                    <strong><?php echo $list['item_count']; ?></strong> βίντεο
                                    <?php if ($list['last_activity']): ?>
                                        <br><small style="color: var(--text-secondary);">
                                            Τελευταία ενημέρωση: <?php echo date('d/m/Y', strtotime($list['last_activity'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="list-card-footer">
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="view-list.php?id=<?php echo $list['id']; ?>" 
                                       class="btn btn-primary" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                        Προβολή
                                    </a>
                                    <a href="edit-list.php?id=<?php echo $list['id']; ?>" 
                                       class="btn btn-secondary" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                        Επεξεργασία
                                    </a>
                                    <a href="add-video.php?list=<?php echo $list['id']; ?>" 
                                       class="btn btn-secondary" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                        + Βίντεο
                                    </a>
                                </div>
                                
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή τη λίστα; Αυτή η ενέργεια δεν μπορεί να αναιρεθεί.');">
                                    <input type="hidden" name="list_id" value="<?php echo $list['id']; ?>">
                                    <button type="submit" name="delete_list" 
                                            class="btn" style="background: #dc3545; color: white; font-size: 0.9rem; padding: 0.5rem 1rem;">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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