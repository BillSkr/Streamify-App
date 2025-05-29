<?php
require_once 'config.php';
requireLogin();

$searchQuery = sanitizeInput($_GET['q'] ?? '');
$searchType = sanitizeInput($_GET['type'] ?? 'content');
$searchUser = sanitizeInput($_GET['user'] ?? '');
$dateFrom = sanitizeInput($_GET['date_from'] ?? '');
$dateTo = sanitizeInput($_GET['date_to'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$results = [];
$totalResults = 0;
$error = '';

if (!empty($searchQuery) || !empty($searchUser) || !empty($dateFrom) || !empty($dateTo)) {
    try {
        if ($searchType === 'users') {
            // Search users
            $sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.email,
                           COUNT(DISTINCT cl.id) as list_count,
                           COUNT(DISTINCT ci.id) as video_count,
                           MAX(cl.updated_at) as last_activity,
                           EXISTS(SELECT 1 FROM user_follows uf WHERE uf.follower_id = ? AND uf.following_id = u.id) as is_following
                    FROM users u
                    LEFT JOIN content_lists cl ON u.id = cl.user_id AND cl.is_public = 1
                    LEFT JOIN content_items ci ON cl.id = ci.list_id
                    WHERE u.id != ?";
            
            $params = [$_SESSION['user_id'], $_SESSION['user_id']];
            
            if (!empty($searchQuery)) {
                $sql .= " AND (u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
                $searchTerm = "%$searchQuery%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            $sql .= " GROUP BY u.id ORDER BY u.username LIMIT ? OFFSET ?";
            $params = array_merge($params, [$perPage, $offset]);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            
            // Get total count
            $countSql = "SELECT COUNT(DISTINCT u.id) FROM users u WHERE u.id != ?";
            $countParams = [$_SESSION['user_id']];
            
            if (!empty($searchQuery)) {
                $countSql .= " AND (u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
                $searchTerm = "%$searchQuery%";
                $countParams = array_merge($countParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($countParams);
            $totalResults = $stmt->fetchColumn();
            
        } elseif ($searchType === 'lists') {
            // Search lists
            $sql = "SELECT cl.*, u.username, u.first_name, u.last_name,
                           COUNT(ci.id) as item_count,
                           MAX(ci.added_at) as last_item_added
                    FROM content_lists cl
                    JOIN users u ON cl.user_id = u.id
                    LEFT JOIN content_items ci ON cl.id = ci.list_id
                    WHERE (cl.is_public = 1 OR cl.user_id = ? OR EXISTS(
                        SELECT 1 FROM user_follows uf WHERE uf.follower_id = ? AND uf.following_id = cl.user_id
                    ))";
            
            $params = [$_SESSION['user_id'], $_SESSION['user_id']];
            
            if (!empty($searchQuery)) {
                $sql .= " AND (cl.title LIKE ? OR cl.description LIKE ?)";
                $searchTerm = "%$searchQuery%";
                $params = array_merge($params, [$searchTerm, $searchTerm]);
            }
            
            if (!empty($searchUser)) {
                $sql .= " AND (u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
                $userTerm = "%$searchUser%";
                $params = array_merge($params, [$userTerm, $userTerm, $userTerm]);
            }
            
            if (!empty($dateFrom)) {
                $sql .= " AND cl.created_at >= ?";
                $params[] = $dateFrom . ' 00:00:00';
            }
            
            if (!empty($dateTo)) {
                $sql .= " AND cl.created_at <= ?";
                $params[] = $dateTo . ' 23:59:59';
            }
            
            $sql .= " GROUP BY cl.id ORDER BY cl.updated_at DESC LIMIT ? OFFSET ?";
            $params = array_merge($params, [$perPage, $offset]);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            
            // Get total count for lists
            $countSql = str_replace("SELECT cl.*, u.username, u.first_name, u.last_name, COUNT(ci.id) as item_count, MAX(ci.added_at) as last_item_added", "SELECT COUNT(DISTINCT cl.id)", $sql);
            $countSql = preg_replace('/GROUP BY.*$/', '', $countSql);
            $countSql = preg_replace('/ORDER BY.*$/', '', $countSql);
            $countSql = preg_replace('/LIMIT.*$/', '', $countSql);
            
            $countParams = array_slice($params, 0, -2); // Remove LIMIT and OFFSET params
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($countParams);
            $totalResults = $stmt->fetchColumn();
            
        } else {
            // Search content (videos)
            $sql = "SELECT ci.*, cl.title as list_title, cl.is_public, cl.user_id as list_owner_id,
                           u.username, u.first_name, u.last_name
                    FROM content_items ci
                    JOIN content_lists cl ON ci.list_id = cl.id
                    JOIN users u ON ci.user_id = u.id
                    WHERE (cl.is_public = 1 OR cl.user_id = ? OR EXISTS(
                        SELECT 1 FROM user_follows uf WHERE uf.follower_id = ? AND uf.following_id = cl.user_id
                    ))";
            
            $params = [$_SESSION['user_id'], $_SESSION['user_id']];
            
            if (!empty($searchQuery)) {
                $sql .= " AND (ci.title LIKE ? OR ci.description LIKE ? OR cl.title LIKE ?)";
                $searchTerm = "%$searchQuery%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            if (!empty($searchUser)) {
                $sql .= " AND (u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
                $userTerm = "%$searchUser%";
                $params = array_merge($params, [$userTerm, $userTerm, $userTerm]);
            }
            
            if (!empty($dateFrom)) {
                $sql .= " AND ci.added_at >= ?";
                $params[] = $dateFrom . ' 00:00:00';
            }
            
            if (!empty($dateTo)) {
                $sql .= " AND ci.added_at <= ?";
                $params[] = $dateTo . ' 23:59:59';
            }
            
            $sql .= " ORDER BY ci.added_at DESC LIMIT ? OFFSET ?";
            $params = array_merge($params, [$perPage, $offset]);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            
            // Get total count for content
            $countSql = str_replace("SELECT ci.*, cl.title as list_title, cl.is_public, cl.user_id as list_owner_id, u.username, u.first_name, u.last_name", "SELECT COUNT(*)", $sql);
            $countSql = preg_replace('/ORDER BY.*$/', '', $countSql);
            $countSql = preg_replace('/LIMIT.*$/', '', $countSql);
            
            $countParams = array_slice($params, 0, -2); // Remove LIMIT and OFFSET params
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($countParams);
            $totalResults = $stmt->fetchColumn();
        }
        
    } catch (PDOException $e) {
        $error = 'Σφάλμα κατά την αναζήτηση. Παρακαλώ δοκιμάστε ξανά.';
    }
}

// Handle follow/unfollow
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['follow_action'])) {
    $targetUserId = (int)$_POST['user_id'];
    $action = $_POST['follow_action'];
    
    if ($targetUserId != $_SESSION['user_id']) {
        try {
            if ($action === 'follow') {
                $stmt = $pdo->prepare("INSERT IGNORE INTO user_follows (follower_id, following_id) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $targetUserId]);
                $followSuccess = "Ακολουθείτε τώρα αυτόν τον χρήστη!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?");
                $stmt->execute([$_SESSION['user_id'], $targetUserId]);
                $followSuccess = "Σταματήσατε να ακολουθείτε αυτόν τον χρήστη.";
            }
            
            // Refresh results to update follow status
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } catch (PDOException $e) {
            $error = 'Σφάλμα κατά την ενημέρωση της ακολούθησης.';
        }
    }
}

$totalPages = ceil($totalResults / $perPage);
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Αναζήτηση - Streamify</title>
    <link rel="stylesheet" href=".././css/style.css">
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
            <h1>Αναζήτηση</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($followSuccess)): ?>
                <div class="success-message"><?php echo $followSuccess; ?></div>
            <?php endif; ?>

            <!-- Search Form -->
            <div class="form-container" style="margin-bottom: 2rem;">
                <form method="GET" class="search-form">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label for="type">Τύπος αναζήτησης</label>
                            <select class="form-control" id="type" name="type" onchange="toggleSearchFields()">
                                <option value="content" <?php echo $searchType === 'content' ? 'selected' : ''; ?>>Περιεχόμενο</option>
                                <option value="lists" <?php echo $searchType === 'lists' ? 'selected' : ''; ?>>Λίστες</option>
                                <option value="users" <?php echo $searchType === 'users' ? 'selected' : ''; ?>>Χρήστες</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="q">Αναζήτηση κειμένου</label>
                            <input type="text" class="form-control" id="q" name="q" 
                                   value="<?php echo htmlspecialchars($searchQuery); ?>" 
                                   placeholder="Εισάγετε λέξεις κλειδιά...">
                        </div>
                        
                        <div class="form-group" id="user-search-field">
                            <label for="user">Χρήστης</label>
                            <input type="text" class="form-control" id="user" name="user" 
                                   value="<?php echo htmlspecialchars($searchUser); ?>" 
                                   placeholder="Όνομα, επώνυμο ή username">
                        </div>
                    </div>
                    
                    <div class="date-search-fields" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label for="date_from">Από ημερομηνία</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_to">Έως ημερομηνία</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">🔍 Αναζήτηση</button>
                        <a href="search.php" class="btn btn-secondary">Καθαρισμός</a>
                    </div>
                </form>
            </div>

            <!-- Search Results -->
            <?php if (!empty($searchQuery) || !empty($searchUser) || !empty($dateFrom) || !empty($dateTo)): ?>
                <div class="search-results">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h2>Αποτελέσματα Αναζήτησης</h2>
                        <span style="color: var(--text-secondary);">
                            <?php echo $totalResults; ?> αποτελέσματα
                            <?php if ($totalPages > 1): ?>
                                (Σελίδα <?php echo $page; ?> από <?php echo $totalPages; ?>)
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if (empty($results)): ?>
                        <div class="list-card">
                            <div class="list-card-body" style="text-align: center; padding: 3rem;">
                                <h3>Δεν βρέθηκαν αποτελέσματα</h3>
                                <p style="color: var(--text-secondary);">Δοκιμάστε διαφορετικούς όρους αναζήτησης ή αλλάξτε τα κριτήρια.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        
                        <?php if ($searchType === 'users'): ?>
                            <!-- User Results -->
                            <div class="results-grid" style="display: grid; gap: 1.5rem;">
                                <?php foreach ($results as $user): ?>
                                    <div class="list-card">
                                        <div class="list-card-header">
                                            <div>
                                                <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                                                <span style="color: var(--text-secondary);">@<?php echo htmlspecialchars($user['username']); ?></span>
                                            </div>
                                        </div>
                                        <div class="list-card-body">
                                            <div style="margin-bottom: 1rem;">
                                                <span><strong><?php echo $user['list_count']; ?></strong> δημόσιες λίστες • </span>
                                                <span><strong><?php echo $user['video_count']; ?></strong> βίντεο</span>
                                                <?php if ($user['last_activity']): ?>
                                                    <br><small style="color: var(--text-secondary);">
                                                        Τελευταία δραστηριότητα: <?php echo date('d/m/Y', strtotime($user['last_activity'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div style="display: flex; gap: 0.5rem;">
                                                <a href="user-profile.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary">Προβολή Προφίλ</a>
                                                
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <?php if ($user['is_following']): ?>
                                                        <button type="submit" name="follow_action" value="unfollow" class="btn btn-secondary">
                                                            ✓ Ακολουθείτε
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" name="follow_action" value="follow" class="btn btn-primary">
                                                            + Ακολούθηση
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                        <?php elseif ($searchType === 'lists'): ?>
                            <!-- List Results -->
                            <div class="results-grid" style="display: grid; gap: 1.5rem;">
                                <?php foreach ($results as $list): ?>
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
                                                    <?php echo htmlspecialchars(substr($list['description'], 0, 150)); ?>
                                                    <?php if (strlen($list['description']) > 150): ?>...<?php endif; ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div style="margin-bottom: 1rem; color: var(--text-secondary); font-size: 0.9rem;">
                                                <strong><?php echo $list['item_count']; ?></strong> βίντεο • 
                                                Από @<?php echo htmlspecialchars($list['username']); ?> • 
                                                <?php echo date('d/m/Y', strtotime($list['created_at'])); ?>
                                                <?php if ($list['last_item_added']): ?>
                                                    <br>Τελευταία ενημέρωση: <?php echo date('d/m/Y', strtotime($list['last_item_added'])); ?>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div style="display: flex; gap: 0.5rem;">
                                                <a href="view-list.php?id=<?php echo $list['id']; ?>" class="btn btn-primary">Προβολή Λίστας</a>
                                                <a href="user-profile.php?id=<?php echo $list['user_id']; ?>" class="btn btn-secondary">Προφίλ Δημιουργού</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                        <?php else: ?>
                            <!-- Content Results -->
                            <div class="results-grid" style="display: grid; gap: 1.5rem;">
                                <?php foreach ($results as $item): ?>
                                    <div class="video-item" style="display: flex; gap: 1rem; background: var(--card-bg); padding: 1.5rem; border-radius: 10px; box-shadow: var(--shadow);">
                                        <div style="flex-shrink: 0;">
                                            <img src="<?php echo htmlspecialchars($item['thumbnail_url'] ?: 'https://img.youtube.com/vi/' . $item['youtube_id'] . '/mqdefault.jpg'); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                                                 style="width: 160px; height: 120px; object-fit: cover; border-radius: 8px;">
                                        </div>
                                        
                                        <div style="flex: 1; min-width: 0;">
                                            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem;">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </h3>
                                            
                                            <?php if (!empty($item['description'])): ?>
                                                <p style="margin: 0 0 1rem 0; color: var(--text-secondary); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                    <?php echo htmlspecialchars(substr($item['description'], 0, 150)); ?>
                                                    <?php if (strlen($item['description']) > 150): ?>...<?php endif; ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div style="margin-bottom: 1rem; color: var(--text-secondary); font-size: 0.9rem;">
                                                Στη λίστα "<a href="view-list.php?id=<?php echo $item['list_id']; ?>" style="color: var(--accent-primary); text-decoration: none;"><?php echo htmlspecialchars($item['list_title']); ?></a>" • 
                                                Από @<?php echo htmlspecialchars($item['username']); ?> • 
                                                <?php echo date('d/m/Y', strtotime($item['added_at'])); ?>
                                                <?php if (!empty($item['duration'])): ?>
                                                    <br>⏱️ <?php echo htmlspecialchars($item['duration']); ?>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div style="display: flex; gap: 0.5rem;">
                                                <a href="view-list.php?id=<?php echo $item['list_id']; ?>#video-<?php echo $item['youtube_id']; ?>" 
                                                   class="btn btn-primary" style="font-size: 0.9rem;">▶️ Αναπαραγωγή</a>
                                                <a href="<?php echo htmlspecialchars($item['youtube_url']); ?>" target="_blank" 
                                                   class="btn btn-secondary" style="font-size: 0.9rem;">YouTube</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination" style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 2rem;">
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                       class="btn btn-secondary">← Προηγούμενη</a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                       class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                       class="btn btn-secondary">Επόμενη →</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Streamify - Ιόνιο Πανεπιστήμιο</p>
        </div>
    </footer>

    <script src="./js/theme.js"></script>
    
    <script>
    function toggleSearchFields() {
        const searchType = document.getElementById('type').value;
        const userField = document.getElementById('user-search-field');
        const dateFields = document.querySelectorAll('.date-search-fields .form-group');
        
        if (searchType === 'users') {
            userField.style.display = 'none';
            dateFields.forEach(field => field.style.display = 'none');
        } else {
            userField.style.display = 'block';
            dateFields.forEach(field => field.style.display = 'block');
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleSearchFields();
    });
    </script>
</body>
</html>