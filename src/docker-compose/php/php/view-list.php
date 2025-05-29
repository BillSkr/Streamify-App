<?php
require_once 'config.php';
requireLogin();

$listId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

if ($listId <= 0) {
    $error = 'Μη έγκυρο ID λίστας.';
} else {
    // Get list details
    $stmt = $pdo->prepare("
        SELECT cl.*, u.username, u.first_name, u.last_name,
               (cl.user_id = ?) as is_owner
        FROM content_lists cl
        JOIN users u ON cl.user_id = u.id
        WHERE cl.id = ? AND (cl.is_public = 1 OR cl.user_id = ? OR EXISTS(
            SELECT 1 FROM user_follows uf WHERE uf.follower_id = ? AND uf.following_id = cl.user_id
        ))
    ");
    $stmt->execute([$_SESSION['user_id'], $listId, $_SESSION['user_id'], $_SESSION['user_id']]);
    $list = $stmt->fetch();
    
    if (!$list) {
        $error = 'Η λίστα δεν βρέθηκε ή δεν έχετε δικαίωμα πρόσβασης.';
    } else {
        // Get list items
        $stmt = $pdo->prepare("
            SELECT ci.*, u.username as added_by_username
            FROM content_items ci
            JOIN users u ON ci.user_id = u.id
            WHERE ci.list_id = ?
            ORDER BY ci.added_at DESC
        ");
        $stmt->execute([$listId]);
        $items = $stmt->fetchAll();
    }
}

// Handle item deletion (only for list owner)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item']) && isset($list) && $list['is_owner']) {
    $itemId = (int)$_POST['item_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM content_items WHERE id = ? AND list_id = ?");
        $stmt->execute([$itemId, $listId]);
        
        // Refresh items
        $stmt = $pdo->prepare("
            SELECT ci.*, u.username as added_by_username
            FROM content_items ci
            JOIN users u ON ci.user_id = u.id
            WHERE ci.list_id = ?
            ORDER BY ci.added_at DESC
        ");
        $stmt->execute([$listId]);
        $items = $stmt->fetchAll();
        
        $success = 'Το βίντεο αφαιρέθηκε από τη λίστα.';
    } catch (PDOException $e) {
        $error = 'Σφάλμα κατά την αφαίρεση του βίντεο.';
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($list) ? htmlspecialchars($list['title']) . ' - ' : ''; ?>Streamify</title>
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
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="my-lists.php" class="btn btn-primary">Επιστροφή στις λίστες μου</a>
                </div>
            <?php else: ?>
                
                <?php if (isset($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- List Header -->
                <div class="list-header" style="background: var(--card-bg); padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: var(--shadow);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                        <div style="flex: 1; min-width: 300px;">
                            <h1 style="margin: 0 0 0.5rem 0; color: var(--text-primary);">
                                <?php echo htmlspecialchars($list['title']); ?>
                            </h1>
                            
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                                <span style="background: <?php echo $list['is_public'] ? 'var(--accent-primary)' : 'var(--accent-secondary)'; ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem;">
                                    <?php echo $list['is_public'] ? '🌐 Δημόσια' : '🔒 Ιδιωτική'; ?>
                                </span>
                                
                                <span style="color: var(--text-secondary); font-size: 0.9rem;">
                                    <?php echo count($items); ?> βίντεο
                                </span>
                                
                                <span style="color: var(--text-secondary); font-size: 0.9rem;">
                                    Δημιουργήθηκε: <?php echo date('d/m/Y', strtotime($list['created_at'])); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($list['description'])): ?>
                                <p style="margin: 0 0 1rem 0; color: var(--text-secondary); line-height: 1.5;">
                                    <?php echo nl2br(htmlspecialchars($list['description'])); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div style="color: var(--text-secondary); font-size: 0.9rem;">
                                Δημιουργός: <?php echo htmlspecialchars($list['first_name'] . ' ' . $list['last_name']); ?> 
                                (@<?php echo htmlspecialchars($list['username']); ?>)
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <?php if ($list['is_owner']): ?>
                                <a href="add-video.php?list=<?php echo $listId; ?>" class="btn btn-primary">+ Προσθήκη Βίντεο</a>
                                <a href="edit-list.php?id=<?php echo $listId; ?>" class="btn btn-secondary">Επεξεργασία</a>
                            <?php endif; ?>
                            
                            <?php if (!empty($items)): ?>
                                <button id="play-all-btn" class="btn btn-secondary">▶️ Αναπαραγωγή Όλων</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Video Player -->
                <div id="video-player" class="video-player" style="display: none;">
                    <iframe id="youtube-iframe" width="100%" height="450" frameborder="0" allowfullscreen></iframe>
                    <div class="video-info">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <div>
                                <h3 id="current-video-title" class="video-title"></h3>
                                <div id="current-video-meta" class="video-meta"></div>
                            </div>
                            <button id="close-player-btn" class="btn btn-secondary">✕ Κλείσιμο</button>
                        </div>
                        
                        <div style="display: flex; justify-content: center; gap: 1rem; align-items: center;">
                            <button id="prev-btn" class="btn btn-secondary">⏮️ Προηγούμενο</button>
                            <span id="playlist-position" style="color: var(--text-secondary);"></span>
                            <button id="next-btn" class="btn btn-secondary">Επόμενο ⏭️</button>
                        </div>
                    </div>
                </div>

                <!-- Video List -->
                <?php if (empty($items)): ?>
                    <div class="list-card">
                        <div class="list-card-body" style="text-align: center; padding: 3rem;">
                            <h3>Η λίστα είναι κενή</h3>
                            <p style="margin: 1rem 0; color: var(--text-secondary);">
                                <?php if ($list['is_owner']): ?>
                                    Προσθέστε το πρώτο βίντεο στη λίστα σας!
                                <?php else: ?>
                                    Δεν έχουν προστεθεί βίντεο σε αυτή τη λίστα ακόμα.
                                <?php endif; ?>
                            </p>
                            <?php if ($list['is_owner']): ?>
                                <a href="add-video.php?list=<?php echo $listId; ?>" class="btn btn-primary">Προσθήκη πρώτου βίντεο</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="video-grid" style="display: grid; gap: 1.5rem;">
                        <?php foreach ($items as $index => $item): ?>
                            <div class="video-item" style="display: flex; gap: 1rem; background: var(--card-bg); padding: 1.5rem; border-radius: 10px; box-shadow: var(--shadow);">
                                <!-- Thumbnail -->
                                <div style="flex-shrink: 0;">
                                    <img src="<?php echo htmlspecialchars($item['thumbnail_url'] ?: 'https://img.youtube.com/vi/' . $item['youtube_id'] . '/mqdefault.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['title']); ?>"
                                         style="width: 200px; height: 150px; object-fit: cover; border-radius: 8px; cursor: pointer;"
                                         onclick="playVideo('<?php echo $item['youtube_id']; ?>', '<?php echo htmlspecialchars($item['title']); ?>', <?php echo $index; ?>)">
                                    <div style="text-align: center; margin-top: 0.5rem;">
                                        <button class="btn btn-primary" style="font-size: 0.9rem; padding: 0.5rem 1rem;"
                                                onclick="playVideo('<?php echo $item['youtube_id']; ?>', '<?php echo htmlspecialchars($item['title']); ?>', <?php echo $index; ?>)">
                                            ▶️ Αναπαραγωγή
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Video Info -->
                                <div style="flex: 1; min-width: 0;">
                                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.2rem; line-height: 1.3;">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </h3>
                                    
                                    <?php if (!empty($item['description'])): ?>
                                        <p style="margin: 0 0 1rem 0; color: var(--text-secondary); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                            <?php echo htmlspecialchars(substr($item['description'], 0, 200)); ?>
                                            <?php if (strlen($item['description']) > 200): ?>...<?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                                        <div style="color: var(--text-secondary); font-size: 0.9rem;">
                                            <?php if (!empty($item['duration'])): ?>
                                                <span>⏱️ <?php echo htmlspecialchars($item['duration']); ?></span> • 
                                            <?php endif; ?>
                                            <span>Προστέθηκε: <?php echo date('d/m/Y H:i', strtotime($item['added_at'])); ?></span>
                                            <?php if (!$list['is_owner']): ?>
                                                • <span>από @<?php echo htmlspecialchars($item['added_by_username']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="<?php echo htmlspecialchars($item['youtube_url']); ?>" target="_blank" 
                                               class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.25rem 0.75rem;">
                                                YouTube
                                            </a>
                                            
                                            <?php if ($list['is_owner']): ?>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Είστε σίγουροι ότι θέλετε να αφαιρέσετε αυτό το βίντεο από τη λίστα;');">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" name="delete_item" 
                                                            class="btn" style="background: #dc3545; color: white; font-size: 0.8rem; padding: 0.25rem 0.75rem;">
                                                        🗑️
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
    // Video player functionality
    let currentPlaylist = <?php echo json_encode($items ?? []); ?>;
    let currentVideoIndex = 0;
    
    function playVideo(videoId, title, index = 0) {
        currentVideoIndex = index;
        
        const player = document.getElementById('video-player');
        const iframe = document.getElementById('youtube-iframe');
        const titleEl = document.getElementById('current-video-title');
        const metaEl = document.getElementById('current-video-meta');
        const positionEl = document.getElementById('playlist-position');
        
        // Show player
        player.style.display = 'block';
        player.scrollIntoView({ behavior: 'smooth' });
        
        // Load video
        iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
        titleEl.textContent = title;
        
        // Update meta info
        const video = currentPlaylist[index];
        if (video) {
            metaEl.innerHTML = `
                ${video.duration ? `⏱️ ${video.duration} • ` : ''}
                Προστέθηκε: ${new Date(video.added_at).toLocaleDateString('el-GR')}
            `;
        }
        
        // Update position
        positionEl.textContent = `${index + 1} / ${currentPlaylist.length}`;
        
        // Update button states
        document.getElementById('prev-btn').disabled = index === 0;
        document.getElementById('next-btn').disabled = index === currentPlaylist.length - 1;
    }
    
    function playNext() {
        if (currentVideoIndex < currentPlaylist.length - 1) {
            const nextVideo = currentPlaylist[currentVideoIndex + 1];
            playVideo(nextVideo.youtube_id, nextVideo.title, currentVideoIndex + 1);
        }
    }
    
    function playPrevious() {
        if (currentVideoIndex > 0) {
            const prevVideo = currentPlaylist[currentVideoIndex - 1];
            playVideo(prevVideo.youtube_id, prevVideo.title, currentVideoIndex - 1);
        }
    }
    
    function closePlayer() {
        const player = document.getElementById('video-player');
        const iframe = document.getElementById('youtube-iframe');
        
        player.style.display = 'none';
        iframe.src = '';
    }
    
    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        const playAllBtn = document.getElementById('play-all-btn');
        const closeBtn = document.getElementById('close-player-btn');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        
        if (playAllBtn) {
            playAllBtn.addEventListener('click', function() {
                if (currentPlaylist.length > 0) {
                    playVideo(currentPlaylist[0].youtube_id, currentPlaylist[0].title, 0);
                }
            });
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', closePlayer);
        }
        
        if (prevBtn) {
            prevBtn.addEventListener('click', playPrevious);
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', playNext);
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            const player = document.getElementById('video-player');
            if (player.style.display === 'block') {
                switch(e.key) {
                    case 'ArrowLeft':
                        e.preventDefault();
                        playPrevious();
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        playNext();
                        break;
                    case 'Escape':
                        e.preventDefault();
                        closePlayer();
                        break;
                }
            }
        });
    });
    </script>
</body>
</html>