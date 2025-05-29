<?php
require_once 'config.php';

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = sanitizeInput($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usernameOrEmail) || empty($password)) {
        $error = 'Παρακαλώ συμπληρώστε όλα τα πεδία.';
    } else {
        try {
            // Check if input is email or username
            $sql = "SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password_hash'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Redirect to originally requested page or dashboard
                $redirectTo = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirectTo");
                exit;
            } else {
                $error = 'Λάθος στοιχεία σύνδεσης. Παρακαλώ δοκιμάστε ξανά.';
            }
        } catch (PDOException $e) {
            $error = 'Παρουσιάστηκε σφάλμα κατά τη σύνδεση. Παρακαλώ δοκιμάστε ξανά.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Σύνδεση - Streamify</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">Streamify</h1>
            <div class="nav-menu">
                <a href="../index.html" class="nav-link">Αρχική</a>
                <a href="../about.html" class="nav-link">Σκοπός</a>
                <a href="../help.html" class="nav-link">Βοήθεια</a>
                <a href="register.php" class="nav-link">Εγγραφή</a>
                <a href="login.php" class="nav-link">Σύνδεση</a>
                <button id="theme-toggle" class="theme-btn">🌙</button>
            </div>
        </div>
    </nav>

    <main class="auth-container">
        <div class="auth-card">
            <h2>Σύνδεση</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="username_email">Username ή Email *</label>
                    <input type="text" class="form-control" id="username_email" name="username_email" 
                           value="<?php echo htmlspecialchars($_POST['username_email'] ?? ''); ?>" required>
                    <div class="error-message" id="username_email_error"></div>
                </div>
                
                <div class="form-group">
                    <label for="password">Κωδικός Πρόσβασης *</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="error-message" id="password_error"></div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Σύνδεση</button>
            </form>
            
            <div class="auth-links">
                <p>Δεν έχετε λογαριασμό; <a href="register.php">Εγγραφείτε εδώ</a></p>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Streamify - Ιόνιο Πανεπιστήμιο</p>
        </div>
    </footer>

    <script src="../js/theme.js"></script>
    <script src="../js/form-validation.js"></script>
</body>
</html>
