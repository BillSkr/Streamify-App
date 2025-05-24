<?php
require_once 'config.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    
    if (empty($title)) {
        $error = 'Ο τίτλος της λίστας είναι υποχρεωτικός.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO content_lists (user_id, title, description, is_public) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $description, $isPublic]);
            
            $listId = $pdo->lastInsertId();
            $success = 'Η λίστα δημιουργήθηκε επιτυχώς!';
            
            // Redirect to the new list after 2 seconds
            header("refresh:2;url=view-list.php?id=$listId");
        } catch (PDOException $e) {
            $error = 'Παρουσιάστηκε σφάλμα κατά τη δημιουργία της λίστας.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Νέα Λίστα - Streamify</title>
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
            <div class="form-container">
                <h2>Δημιουργία Νέας Λίστας</h2>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php else: ?>
                
                <form method="POST" data-validate="true">
                    <div class="form-group">
                        <label for="title">Τίτλος Λίστας *</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                               required maxlength="255">
                        <div class="error-message" id="title_error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Περιγραφή (προαιρετική)</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="4" maxlength="1000"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <div class="error-message" id="description_error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="is_public" <?php echo isset($_POST['is_public']) ? 'checked' : ''; ?>>
                            Δημόσια Λίστα
                        </label>
                        <small style="color: var(--text-secondary); margin-top: 0.25rem; display: block;">
                            Οι δημόσιες λίστες είναι ορατές σε όλους τους χρήστες που σας ακολουθούν
                        </small>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">Δημιουργία Λίστας</button>
                        <a href="my-lists.php" class="btn btn-secondary">Ακύρωση</a>
                    </div>
                </form>
                
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Streamify - Ιόνιο Πανεπιστήμιο</p>
        </div>
    </footer>

    <script src="theme.js"></script>
    <script src="form-validation.js"></script>
</body>
</html>