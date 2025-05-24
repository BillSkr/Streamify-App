<?php
require_once 'config.php';
require_once 'youtube-api.php';
requireLogin();

$listId = isset($_GET['list']) ? (int)$_GET['list'] : 0;
$error = '';
$success = '';

// Verify list ownership
if ($listId > 0) {
    $stmt = $pdo->prepare("SELECT title FROM content_lists WHERE id = ? AND user_id = ?");
    $stmt->execute([$listId, $_SESSION['user_id']]);
    $list = $stmt->fetch();
    
    if (!$list) {
        $error = 'Η λίστα δεν βρέθηκε ή δεν έχετε δικαίωμα πρόσβασης.';
        $listId = 0;
    }
} else {
    // Get user's lists for selection
    $stmt = $pdo->prepare("SELECT id, title FROM content_lists WHERE user_id = ? ORDER BY title");
    $stmt->execute([$_SESSION['user_id']]);
    $userLists = $stmt->fetchAll();
}

// Handle video addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_video'])) {
    $videoUrl = sanitizeInput($_POST['video_url'] ?? '');
    $selectedListId = (int)($_POST['list_id'] ?? $listId);
    
    if (empty($videoUrl)) {
        $error = 'Παρακαλώ εισάγετε URL ή ID βίντεο.';
    } elseif ($selectedListId <= 0) {
        $error = 'Παρακαλώ επιλέξτε λίστα.';
    } else {
        // Extract video ID from URL
        $videoId = YouTubeAPI::extractVideoId($videoUrl);
        
        if (!$videoId) {
            $error = 'Μη έγκυρο YouTube URL ή ID βίντεο.';
        } else {
            // Verify list ownership
            $stmt = $pdo->prepare("SELECT title FROM content_lists WHERE id = ? AND user_id = ?");
            $stmt->execute([$selectedListId, $_SESSION['user_id']]);
            $listInfo = $stmt->fetch();
            
            if (!$listInfo) {
                $error = 'Μη έγκυρη λίστα.';
            } else {
                // Check if video already exists in this list
                $stmt = $pdo->prepare("SELECT id FROM content_items WHERE list_id = ? AND youtube_id = ?");
                $stmt->execute([$selectedListId, $videoId]);
                
                if ($stmt->fetch()) {
                    $error = 'Το βίντεο υπάρχει ήδη σε αυτή τη λίστα.';
                } else {
                    // Get video details from YouTube API
                    $videoDetails = getYouTubeVideoDetails($videoId);
                    
                    if (isset($videoDetails['error'])) {
                        $error = 'Σφάλμα κατά την ανάκτηση στοιχείων βίντεο: ' . $videoDetails['error'];
                    } else {
                        try {
                            // Insert video into database
                            $stmt = $pdo->prepare("
                                INSERT INTO content_items (list_id, user_id, title, youtube_url, youtube_id, description, duration, thumbnail_url) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $selectedListId,
                                $_SESSION['user_id'],
                                $videoDetails['title'],
                                $videoDetails['url'],
                                $videoId,
                                substr($videoDetails['description'], 0, 1000), // Limit description length
                                $videoDetails['duration'],
                                $videoDetails['thumbnail']
                            ]);
                            
                            $success = 'Το βίντεο προστέθηκε επιτυχώς στη λίστα "' . htmlspecialchars($listInfo['title']) . '"!';
                            
                            // Clear form
                            unset($_POST);
                        } catch (PDOException $e) {
                            $error = 'Σφάλμα κατά την προσθήκη του βίντεο στη βάση δεδομένων.';
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προσθήκη Βίντεο - Streamify</title>
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
                <h2>Προσθήκη Βίντεο σε Λίστα</h2>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if ($listId > 0 && isset($list)): ?>
                    <div class="info-message" style="background: var(--bg-secondary); border: 1px solid var(--border-color); padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                        <strong>Προσθήκη στη λίστα:</strong> <?php echo htmlspecialchars($list['title']); ?>
                    </div>
                <?php endif; ?>

                <!-- Manual Video Addition -->
                <div style="background: var(--card-bg); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                    <h3>Προσθήκη με URL ή ID</h3>
                    
                    <form method="POST">
                        <?php if ($listId <= 0): ?>
                            <div class="form-group">
                                <label for="list_id">Επιλογή Λίστας *</label>
                                <select class="form-control" id="list_id" name="list_id" required>
                                    <option value="">-- Επιλέξτε λίστα --</option>
                                    <?php if (isset($userLists)): ?>
                                        <?php foreach ($userLists as $userList): ?>
                                            <option value="<?php echo $userList['id']; ?>" 
                                                    <?php echo (isset($_POST['list_id']) && $_POST['list_id'] == $userList['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($userList['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="list_id" value="<?php echo $listId; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="video_url">YouTube URL ή Video ID *</label>
                            <input type="text" class="form-control" id="video_url" name="video_url" 
                                   value="<?php echo htmlspecialchars($_POST['video_url'] ?? ''); ?>" 
                                   placeholder="π.χ. https://www.youtube.com/watch?v=dQw4w9WgXcQ ή dQw4w9WgXcQ" required>
                            <small style="color: var(--text-secondary); margin-top: 0.25rem; display: block;">
                                Υποστηρίζονται όλα τα YouTube URL formats και τα Video IDs
                            </small>
                        </div>
                        
                        <button type="submit" name="add_video" class="btn btn-primary">Προσθήκη Βίντεο</button>
                    </form>
                </div>

                <!-- YouTube Search -->
                <div style="background: var(--card-bg); padding: 1.5rem; border-radius: 8px;">
                    <h3>Αναζήτηση στο YouTube</h3>
                    
                    <div class="form-group">
                        <label for="search_query">Αναζήτηση βίντεο</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" class="form-control" id="search_query" 
                                   placeholder="Εισάγετε λέξεις κλειδιά...">
                            <button type="button" id="search_btn" class="btn btn-secondary">Αναζήτηση</button>
                        </div>
                    </div>
                    
                    <div id="search_results" style="margin-top: 1rem;"></div>
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
    <script src="youtube-search.js"></script>
    
    <script>
    // Initialize YouTube search functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchBtn = document.getElementById('search_btn');
        const searchQuery = document.getElementById('search_query');
        const searchResults = document.getElementById('search_results');
        
        // Search on button click
        searchBtn.addEventListener('click', performSearch);
        
        // Search on Enter key
        searchQuery.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
        
        function performSearch() {
            const query = searchQuery.value.trim();
            if (!query) {
                searchResults.innerHTML = '<p>Παρακαλώ εισάγετε λέξεις κλειδιά για αναζήτηση.</p>';
                return;
            }
            
            // Show loading
            searchResults.innerHTML = '<div class="spinner"></div><p>Αναζήτηση...</p>';
            searchBtn.disabled = true;
            searchBtn.textContent = 'Αναζήτηση...';
            
            // Make API call
            fetch(`youtube-api.php?action=search&q=${encodeURIComponent(query)}&max=10`)
                .then(response => response.json())
                .then(data => {
                    displaySearchResults(data);
                })
                .catch(error => {
                    searchResults.innerHTML = '<div class="error-message">Σφάλμα κατά την αναζήτηση. Παρακαλώ δοκιμάστε ξανά.</div>';
                })
                .finally(() => {
                    searchBtn.disabled = false;
                    searchBtn.textContent = 'Αναζήτηση';
                });
        }
        
        function displaySearchResults(data) {
            if (data.error) {
                searchResults.innerHTML = `<div class="error-message">${data.error}</div>`;
                return;
            }
            
            if (!data.results || data.results.length === 0) {
                searchResults.innerHTML = '<p>Δεν βρέθηκαν αποτελέσματα.</p>';
                return;
            }
            
            let html = '<div class="search-results-grid" style="display: grid; gap: 1rem; margin-top: 1rem;">';
            
            data.results.forEach(video => {
                html += `
                    <div class="search-result-item" style="display: flex; gap: 1rem; padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-secondary);">
                        <img src="${video.thumbnail}" alt="${video.title}" 
                             style="width: 120px; height: 90px; object-fit: cover; border-radius: 4px; flex-shrink: 0;">
                        <div style="flex: 1; min-width: 0;">
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; line-height: 1.2;">${video.title}</h4>
                            <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: var(--text-secondary); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                ${video.description}
                            </p>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                                <small style="color: var(--text-secondary);">
                                    ${video.channel} • ${new Date(video.published).toLocaleDateString('el-GR')}
                                </small>
                                <button type="button" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.25rem 0.75rem;" 
                                        onclick="addVideoToList('${video.id}', '${video.title.replace(/'/g, "\\'")}')">
                                    Προσθήκη
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            searchResults.innerHTML = html;
        }
        
        // Global function to add video to list
        window.addVideoToList = function(videoId, videoTitle) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const videoUrlInput = document.createElement('input');
            videoUrlInput.type = 'hidden';
            videoUrlInput.name = 'video_url';
            videoUrlInput.value = videoId;
            
            const listIdInput = document.createElement('input');
            listIdInput.type = 'hidden';
            listIdInput.name = 'list_id';
            listIdInput.value = '<?php echo $listId; ?>';
            
            const addVideoInput = document.createElement('input');
            addVideoInput.type = 'hidden';
            addVideoInput.name = 'add_video';
            addVideoInput.value = '1';
            
            form.appendChild(videoUrlInput);
            form.appendChild(listIdInput);
            form.appendChild(addVideoInput);
            
            document.body.appendChild(form);
            form.submit();
        };
    });
    </script>
</body>
</html>