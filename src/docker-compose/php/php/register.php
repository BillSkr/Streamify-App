<?php
require_once 'config.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName       = sanitizeInput($_POST['first_name']        ?? '');
    $lastName        = sanitizeInput($_POST['last_name']         ?? '');
    $username        = sanitizeInput($_POST['username']          ?? '');
    $emailRaw        = $_POST['email']                           ?? '';
    $email           = sanitizeInput(trim(filter_var($emailRaw, FILTER_SANITIZE_EMAIL)));
    $password        = $_POST['password']                        ?? '';
    $confirmPassword = $_POST['confirm_password']                ?? '';
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password)) {
        $error = 'Όλα τα πεδία είναι υποχρεωτικά.';
    } elseif (!validateEmail($email)) {
        $error = 'Παρακαλώ εισάγετε έγκυρο email.';
    } elseif (strlen($password) < 6) {
        $error = 'Ο κωδικός πρόσβασης πρέπει να έχει τουλάχιστον 6 χαρακτήρες.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Οι κωδικοί πρόσβασης δεν ταιριάζουν.';
    } else {
        try {
            // Έλεγχος username
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Το username υπάρχει ήδη. Παρακαλώ επιλέξτε άλλο.';
            } else {
                // Έλεγχος email
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Το email υπάρχει ήδη. Παρακαλώ χρησιμοποιήστε άλλο.';
                } else {
                    // Εισαγωγή νέου χρήστη
                    $hashedPassword = hashPassword($password);
                    $stmt = $pdo->prepare("
                        INSERT INTO users 
                          (first_name, last_name, username, email, password_hash) 
                        VALUES 
                          (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $firstName, 
                        $lastName, 
                        $username, 
                        $email, 
                        $hashedPassword
                    ]);
                    
                    $success = 'Η εγγραφή ολοκληρώθηκε επιτυχώς! Μπορείτε τώρα να <a href="login.php">συνδεθείτε</a>.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Παρουσιάστηκε σφάλμα κατά την εγγραφή. Παρακαλώ δοκιμάστε ξανά.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Εγγραφή - Streamify</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo">Streamify</h1>
            <div class="nav-menu">
                <a href="../index.html"  class="nav-link">Αρχική</a>
                <a href="../about.html"  class="nav-link">Σκοπός</a>
                <a href="../help.html"   class="nav-link">Βοήθεια</a>
                <a href="register.php"   class="nav-link">Εγγραφή</a>
                <a href="login.php"      class="nav-link">Σύνδεση</a>
                <button id="theme-toggle" class="theme-btn">🌙</button>
            </div>
        </div>
    </nav>

    <main class="auth-container">
        <div class="auth-card">
            <h2>Εγγραφή Χρήστη</h2>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo $success; ?>
                </div>
            <?php else: ?>
            
            <form method="POST" id="registerForm" novalidate>
                <div class="form-group">
                    <label for="first_name">Όνομα *</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="first_name" 
                        name="first_name" 
                        value="<?php echo htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                    <div class="error-message" id="first_name_error"></div>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Επώνυμο *</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="last_name" 
                        name="last_name" 
                        value="<?php echo htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                    <div class="error-message" id="last_name_error"></div>
                </div>
                
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="username" 
                        name="username" 
                        value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                    <div class="error-message" id="username_error"></div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input 
                        type="email" 
                        class="form-control" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                    <div class="error-message" id="email_error"></div>
                </div>
                
                <div class="form-group">
                    <label for="password">Κωδικός Πρόσβασης *</label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        required
                    >
                    <div class="error-message" id="password_error"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Επιβεβαίωση Κωδικού *</label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required
                    >
                    <div class="error-message" id="confirm_password_error"></div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width:100%;">Εγγραφή</button>
            </form>
            
            <?php endif; ?>
            
            <div class="auth-links">
                <p>Έχετε ήδη λογαριασμό; <a href="login.php">Συνδεθείτε εδώ</a></p>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Streamify – Ιόνιο Πανεπιστήμιο</p>
        </div>
    </footer>

    <script src="../js/theme.js"></script>
    <script src="../js/form-validation.js"></script>
</body>
</html>