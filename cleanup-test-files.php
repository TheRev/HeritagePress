<?php
/**
 * Clean up test files from HeritagePress plugin
 */

// Test files to remove from root
$root_test_files = [
    'test-gedcom-service-loading.php',
    'test-gedcom-service-initialization-fix.php',
    'test-tools-menu.php',
    'debug-autoloader.php',
    'final-verification.php',
    'GEDCOM-SERVICE-INITIALIZATION-FIX.php',
    'DATECONVERTER-INTEGRATION-COMPLETE.md',
    'ERROR-RESOLUTION-COMPLETE.md',
    'INSTRUCTIONS.md'
];

echo "<h1>HeritagePress Cleanup - Removing Test Files</h1>\n";

$removed_count = 0;

foreach ($root_test_files as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        if (unlink($full_path)) {
            echo "<p>✅ Removed: {$file}</p>\n";
            $removed_count++;
        } else {
            echo "<p>❌ Failed to remove: {$file}</p>\n";
        }
    }
}

// Remove backup files
$backup_patterns = ['*.bak', '*.new', '*.old'];
foreach ($backup_patterns as $pattern) {
    $files = glob(__DIR__ . '/' . $pattern);
    foreach ($files as $file) {
        if (unlink($file)) {
            echo "<p>✅ Removed backup: " . basename($file) . "</p>\n";
            $removed_count++;
        }
    }
}

// Remove specific backup files in includes/Admin/
$admin_backups = [
    'includes/Admin/MenuManager.php.bak',
    'includes/Admin/MenuManager.php.new',
    'includes/Admin/ImportExportManagerOld.php',
    'includes/Admin/ImportExportManagerNew.php'
];

foreach ($admin_backups as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        if (unlink($full_path)) {
            echo "<p>✅ Removed: {$file}</p>\n";
            $removed_count++;
        }
    }
}

echo "<h2>Cleanup Summary</h2>\n";
echo "<p><strong>Total files removed:</strong> {$removed_count}</p>\n";
echo "<p><strong>Plugin structure is now cleaner and production-ready!</strong></p>\n";

if ($removed_count > 0) {
    echo "<h3>Remaining Core Files:</h3>\n";
    echo "<ul>\n";
    echo "<li>heritagepress.php (main plugin file)</li>\n";
    echo "<li>includes/ (core functionality)</li>\n";
    echo "<li>assets/ (CSS/JS files)</li>\n";
    echo "<li>languages/ (translation files)</li>\n";
    echo "<li>README.md (documentation)</li>\n";
    echo "</ul>\n";
}
?>