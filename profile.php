<?php
require_once 'config.php';
requireLogin();

$error = '';
$success = '';

// Get current user data
$stmt = $pdo->prepare("SELECT first_name, last_name, username, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($firstName) || empty($lastName) || empty($username) || empty($email)) {
        $error = 'Όλα τα πεδία είναι υποχρεωτικά.';
    } elseif (!validateEmail($email)) {
        $error = 'Παρακαλώ εισάγετε έγκυρο email.';
    } else {
        try {
            // Check if username is taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $error = 'Το username υπάρχει ήδη. Παρακαλώ επιλέξτε άλλο.';
            } else {
                // Check if email is taken by another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    $error = 'Το email υπάρχει ήδη. Παρακαλώ χρησιμοποιήστε άλλο.';
                } else {
                    // Update user
                    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ? WHERE id = ?");
                    $stmt->execute([$firstName, $lastName, $username, $email, $_SESSION['user_id']]);
                    
                    // Update session username
                    $_SESSION['username'] = $username;
                    
                    $success = 'Το προφίλ ενημερώθηκε επιτυχώς!';
                    
                    // Refresh user data
                    $user = [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'username' => $username,
                        'email' => $email
                    ];
                }
            }
        } catch (PDOException $e) {
            $error = 'Παρουσιάστηκε σφάλμα κατά την ενημέρωση. Παρακαλώ δοκιμάστε ξανά.';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Παρακαλώ συμπληρώστε όλα τα πεδία κωδικού.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Ο νέος κωδικός πρέπει να έχει τουλάχιστον 6 χαρακτήρες.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Οι νέοι κωδικοί δεν ταιριάζουν.';
    } else {
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $currentHash = $stmt->fetchColumn();
            
            if (!verifyPassword($currentPassword, $currentHash)) {
                $error = 'Ο τρέχων κωδικός είναι λάθος.';
            } else {
                // Update password
                $newHash = hashPassword($newPassword);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$newHash, $_SESSION['user_id']]);
                
                $success = 'Ο κωδικός πρόσβασης άλλαξε επιτυχώς!';
            }
        } catch (PDOException $e) {
            $error = 'Παρουσιάστηκε σφάλμα κατά την αλλαγή κωδικού.';
        }
    }
}

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $confirmPassword = $_POST['delete_password'] ?? '';
    $confirmText = $_POST['delete_confirm'] ?? '';
    
    if ($confirmText !== 'DELETE') {
        $error = 'Για επιβεβαίωση διαγραφής, πληκτρολογήστε "DELETE".';
    } elseif (empty($confirmPassword)) {
        $error = 'Παρακαλώ εισάγετε τον κωδικό σας για επιβεβαίωση.';
    } else {
        try {
            // Verify password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $currentHash = $stmt->fetchColumn();
            
            if (!verifyPassword($confirmPassword, $currentHash)) {
                $error = 'Λάθος κωδικός πρόσβασης.';
            } else {
                // Delete user account (CASCADE will delete related data)
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                // Destroy session and redirect
                session_destroy();
                header('Location: index.html?deleted=1');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Παρουσιάστηκε σφάλμα κατά τη διαγραφή του λογαριασμού.';
        }
    }
}

// Get user statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM content_lists WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalLists = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM content_items WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalVideos = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_follows WHERE follower_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$following = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_follows WHERE following_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$followers = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Το Προφίλ μου - Streamify</title>
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
            <h1>Το Προφίλ μου</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- User Stats -->
            <div style="background: var(--card-bg); padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: var(--shadow);">
                <h2>Στατιστικά Λογαριασμού</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <div style="text-align: center; padding: 1rem; background: var(--bg-secondary); border-radius: 8px;">
                        <div style="font-size: 2rem; font-weight: bold; color: var(--accent-primary);"><?php echo $totalLists; ?></div>
                        <div style="color: var(--text-secondary);">Λίστες</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: var(--bg-secondary); border-radius: 8px;">
                        <div style="font-size: 2rem; font-weight: bold; color: var(--accent-primary);"><?php echo $totalVideos; ?></div>
                        <div style="color: var(--text-secondary);">Βίντεο</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: var(--bg-secondary); border-radius: 8px;">
                        <div style="font-size: 2rem; font-weight: bold; color: var(--accent-primary);"><?php echo $following; ?></div>
                        <div style="color: var(--text-secondary);">Ακολουθώ</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: var(--bg-secondary); border-radius: 8px;">
                        <div style="font-size: 2rem; font-weight: bold; color: var(--accent-primary);"><?php echo $followers; ?></div>
                        <div style="color: var(--text-secondary);">Με ακολουθούν</div>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
                
                <!-- Profile Information -->
                <div class="form-container">
                    <h2>Στοιχεία Προφίλ</h2>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="first_name">Όνομα *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Επώνυμο *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%;">
                            Ενημέρωση Προφίλ
                        </button>
                    </form>
                </div>

                <!-- Password Change -->
                <div class="form-container">
                    <h2>Αλλαγή Κωδικού</h2>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password">Τρέχων Κωδικός *</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Νέος Κωδικός *</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   minlength="6" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Επιβεβαίωση Νέου Κωδικού *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   minlength="6" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary" style="width: 100%;">
                            Αλλαγή Κωδικού
                        </button>
                    </form>
                </div>
            </div>

            <!-- Account Deletion -->
            <div class="form-container" style="margin-top: 2rem; border: 2px solid #dc3545;">
                <h2 style="color: #dc3545;">⚠️ Διαγραφή Λογαριασμού</h2>
                
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    <strong>Προσοχή:</strong> Η διαγραφή του λογαριασμού σας θα:
                    <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                        <li>Διαγράψει όλες τις λίστες σας (δημόσιες και ιδιωτικές)</li>
                        <li>Διαγράψει όλα τα βίντεο που έχετε προσθέσει</li>
                        <li>Αφαιρέσει όλες τις ακολουθίες (followers/following)</li>
                        <li>Διαγράψει μόνιμα τον λογαριασμό σας</li>
                    </ul>
                    <strong>Αυτή η ενέργεια δεν μπορεί να αναιρεθεί!</strong>
                </div>
                
                <details>
                    <summary style="cursor: pointer; font-weight: bold; color: #dc3545; margin-bottom: 1rem;">
                        Κλικ για διαγραφή λογαριασμού
                    </summary>
                    
                    <form method="POST" onsubmit="return confirmDeletion()">
                        <div class="form-group">
                            <label for="delete_confirm">Πληκτρολογήστε "DELETE" για επιβεβαίωση:</label>
                            <input type="text" class="form-control" id="delete_confirm" name="delete_confirm" 
                                   placeholder="DELETE" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="delete_password">Κωδικός πρόσβασης για επιβεβαίωση:</label>
                            <input type="password" class="form-control" id="delete_password" name="delete_password" required>
                        </div>
                        
                        <button type="submit" name="delete_account" 
                                style="width: 100%; background: #dc3545; color: white; border: none; padding: 0.75rem; border-radius: 5px; font-size: 1rem; cursor: pointer;">
                            🗑️ ΔΙΑΓΡΑΦΗ ΛΟΓΑΡΙΑΣΜΟΥ
                        </button>
                    </form>
                </details>
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
    
    <script>
    function confirmDeletion() {
        const confirmText = document.getElementById('delete_confirm').value;
        if (confirmText !== 'DELETE') {
            alert('Παρακαλώ πληκτρολογήστε "DELETE" για επιβεβαίωση.');
            return false;
        }
        
        return confirm('Είστε απόλυτα σίγουροι ότι θέλετε να διαγράψετε τον λογαριασμό σας?\n\nΑυτή η ενέργεια θα διαγράψει ΜΟΝΙΜΑ:\n- Όλες τις λίστες σας\n- Όλα τα βίντεο σας\n- Όλες τις ακολουθίες σας\n- Τον λογαριασμό σας\n\nΔΕΝ ΜΠΟΡΕΙ ΝΑ ΑΝΑΙΡΕΘΕΙ!\n\nΠατήστε OK για συνέχεια ή Cancel για ακύρωση.');
    }
    
    // Password confirmation validation
    document.addEventListener('DOMContentLoaded', function() {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePasswordMatch() {
            if (newPassword.value && confirmPassword.value) {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Οι κωδικοί δεν ταιριάζουν');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
        }
        
        if (newPassword && confirmPassword) {
            newPassword.addEventListener('input', validatePasswordMatch);
            confirmPassword.addEventListener('input', validatePasswordMatch);
        }
    });
    </script>
</body>