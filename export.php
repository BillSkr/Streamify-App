<?php
require_once 'config.php';
requireLogin();

// Get all public lists and their content
$stmt = $pdo->prepare("
    SELECT cl.id, cl.title, cl.description, cl.created_at, cl.updated_at,
           u.username, 
           MD5(CONCAT(u.id, u.username)) as user_hash
    FROM content_lists cl
    JOIN users u ON cl.user_id = u.id
    WHERE cl.is_public = 1
    ORDER BY cl.created_at DESC
");
$stmt->execute();
$lists = $stmt->fetchAll();

// Get content for each list
$exportData = [];
foreach ($lists as $list) {
    $stmt = $pdo->prepare("
        SELECT ci.title, ci.youtube_url, ci.youtube_id, ci.description, ci.duration, ci.added_at,
               MD5(CONCAT(ci.user_id, u.username)) as added_by_hash
        FROM content_items ci
        JOIN users u ON ci.user_id = u.id
        WHERE ci.list_id = ?
        ORDER BY ci.added_at ASC
    ");
    $stmt->execute([$list['id']]);
    $items = $stmt->fetchAll();
    
    $exportData[] = [
        'list_id' => $list['id'],
        'title' => $list['title'],
        'description' => $list['description'],
        'created_at' => $list['created_at'],
        'updated_at' => $list['updated_at'],
        'creator_hash' => $list['user_hash'],
        'items_count' => count($items),
        'items' => $items
    ];
}

// Generate YAML
function arrayToYaml($array, $indent = 0) {
    $yaml = '';
    $indentStr = str_repeat('  ', $indent);
    
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $yaml .= $indentStr . $key . ":\n";
            $yaml .= arrayToYaml($value, $indent + 1);
        } else {
            $value = is_null($value) ? 'null' : $value;
            if (is_string($value) && (strpos($value, "\n") !== false || strpos($value, '"') !== false || strpos($value, ':') !== false)) {
                // Multi-line or special characters - use literal style
                $value = str_replace("\n", "\n" . $indentStr . "  ", $value);
                $yaml .= $indentStr . $key . ': |' . "\n" . $indentStr . '  ' . $value . "\n";
            } else {
                // Simple value
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif (is_numeric($value)) {
                    // Keep numeric values as-is
                } else {
                    // Quote strings that might need it
                    if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}/', $value) || 
                        preg_match('/[:\[\]{}",\'|>]/', $value)) {
                        $value = '"' . str_replace('"', '\"', $value) . '"';
                    }
                }
                $yaml .= $indentStr . $key . ': ' . $value . "\n";
            }
        }
    }
    
    return $yaml;
}

// Handle download request
if (isset($_GET['download']) && $_GET['download'] === 'yaml') {
    $exportMetadata = [
        'export_info' => [
            'platform' => 'Streamify',
            'export_date' => date('Y-m-d H:i:s'),
            'export_type' => 'public_lists_only',
            'total_lists' => count($exportData),
            'total_items' => array_sum(array_column($exportData, 'items_count')),
            'privacy_note' => 'Only public lists are included. User identities are anonymized with hash values.',
            'data_format' => 'YAML',
            'version' => '1.0'
        ]
    ];
    
    $fullExport = array_merge($exportMetadata, ['lists' => $exportData]);
    
    $yaml = "# Streamify Public Lists Export\n";
    $yaml .= "# Generated on: " . date('Y-m-d H:i:s') . "\n";
    $yaml .= "# This file contains only public lists with anonymized user data\n\n";
    $yaml .= arrayToYaml($fullExport);
    
    // Set headers for download
    header('Content-Type: application/x-yaml');
    header('Content-Disposition: attachment; filename="streamify_export_' . date('Y-m-d_H-i-s') . '.yaml"');
    header('Content-Length: ' . strlen($yaml));
    
    echo $yaml;
    exit;
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Εξαγωγή Δεδομένων - Streamify</title>
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
                <h2>Εξαγωγή Δεδομένων</h2>
                
                <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                    <h3>Πληροφορίες Εξαγωγής</h3>
                    <p>Η εξαγωγή περιλαμβάνει:</p>
                    <ul style="margin: 1rem 0; padding-left: 2rem;">
                        <li><strong>Δημόσιες λίστες μόνο:</strong> Οι ιδιωτικές λίστες δεν εξάγονται για λόγους ιδιωτικότητας</li>
                        <li><strong>Ανώνυμα δεδομένα:</strong> Τα στοιχεία χρηστών αντικαθίστανται με hash values</li>
                        <li><strong>Open Data Format:</strong> Τα δεδομένα εξάγονται σε μορφή YAML</li>
                        <li><strong>Περιεχόμενο:</strong> Τίτλοι, περιγραφές, YouTube links και metadata</li>
                    </ul>
                </div>

                <div style="background: var(--card-bg); padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow);">
                    <h3>Στατιστικά Εξαγωγής</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0;">
                        <div style="text-align: center; padding: 1rem; background: var(--bg-secondary); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: bold; color: var(--accent-primary);">
                                <?php echo count($exportData); ?>
                            </div>
                            <div style="color: var(--text-secondary);">Δημόσιες Λίστες</div>
                        </div>
                        
                        <div style="text-align: center; padding: 1rem; background: var(--bg-secondary); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: bold; color: var(--accent-primary);">
                                <?php echo array_sum(array_column($exportData, 'items_count')); ?>
                            </div>
                            <div style="color: var(--text-secondary);">Συνολικά Βίντεο</div>
                        </div>
                        
                        <div style="text-align: center; padding: 1rem; background: var(--bg-secondary); border-radius: 8px;">
                            <div style="font-size: 2rem; font-weight: bold; color: var(--accent-primary);">
                                <?php echo count(array_unique(array_column($exportData, 'creator_hash'))); ?>
                            </div>
                            <div style="color: var(--text-secondary);">Δημιουργοί</div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="?download=yaml" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">
                            📥 Λήψη YAML Αρχείου
                        </a>
                    </div>
                </div>

                <?php if (!empty($exportData)): ?>
                    <div style="background: var(--card-bg); padding: 1.5rem; border-radius: 8px; margin-top: 2rem; box-shadow: var(--shadow);">
                        <h3>Προεπισκόπηση Δεδομένων</h3>
                        <details style="margin-top: 1rem;">
                            <summary style="cursor: pointer; font-weight: bold; margin-bottom: 1rem;">
                                Προβολή δομής δεδομένων (κλικ για ανάπτυγμα)
                            </summary>
                            
                            <div style="max-height: 400px; overflow-y: auto; background: var(--bg-secondary); padding: 1rem; border-radius: 4px; font-family: monospace; font-size: 0.9rem; white-space: pre-wrap;">
<?php
// Show preview of first few lists
$preview = array_slice($exportData, 0, 2);
foreach ($preview as &$list) {
    $list['items'] = array_slice($list['items'], 0, 3);
    if (count($exportData[array_search($list, $exportData)]['items']) > 3) {
        $list['items'][] = ['...more items...'];
    }
}

echo htmlspecialchars(arrayToYaml(['preview' => $preview]));
if (count($exportData) > 2) {
    echo "\n# ... και " . (count($exportData) - 2) . " ακόμα λίστες ...";
}
?>
                            </div>
                        </details>
                    </div>
                <?php else: ?>
                    <div style="background: var(--bg-secondary); padding: 2rem; border-radius: 8px; text-align: center; margin-top: 2rem;">
                        <h3>Δεν υπάρχουν δεδομένα για εξαγωγή</h3>
                        <p style="color: var(--text-secondary); margin: 1rem 0;">
                            Δεν βρέθηκαν δημόσιες λίστες στην πλατφόρμα για εξαγωγή.
                        </p>
                        <a href="my-lists.php" class="btn btn-primary">Δημιουργία Δημόσιας Λίστας</a>
                    </div>
                <?php endif; ?>

                <div style="background: var(--bg-secondary); padding: 1rem; border-radius: 8px; margin-top: 2rem;">
                    <h4>Σημειώσεις:</h4>
                    <ul style="margin: 0.5rem 0; padding-left: 1.5rem; font-size: 0.9rem; color: var(--text-secondary);">
                        <li>Το αρχείο είναι σε μορφή YAML (YAML Ain't Markup Language)</li>
                        <li>Μπορεί να ανοιχθεί με οποιονδήποτε text editor</li>
                        <li>Είναι συμβατό με πολλές γλώσσες προγραμματισμού</li>
                        <li>Τα στοιχεία ιδιωτικότητας έχουν αφαιρεθεί ή κρυπτογραφηθεί</li>
                        <li>Η εξαγωγή ενημερώνεται σε πραγματικό χρόνο</li>
                    </ul>
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