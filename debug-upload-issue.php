<?php
/**
 * Debug GEDCOM Upload Issues
 */

// WordPress environment
define('WP_USE_THEMES', false);
require_once('c:\MAMP\htdocs\wordpress\wp-config.php');

// Load plugin autoloader
require_once(__DIR__ . '/includes/class-heritagepress-autoloader.php');

echo "<h1>GEDCOM Upload Debug Test</h1>\n";

// Test 1: Check if ImportHandler is properly registered
echo "<h3>1. AJAX Handler Registration Test</h3>\n";

try {
    $import_handler = new \HeritagePress\Admin\ImportExport\ImportHandler();
    echo "<p style='color: green;'>✓ ImportHandler instantiated successfully</p>\n";

    // Check if AJAX actions are registered
    global $wp_filter;

    $ajax_actions = [
        'wp_ajax_hp_upload_gedcom',
        'wp_ajax_hp_process_gedcom',
        'wp_ajax_hp_import_progress'
    ];

    foreach ($ajax_actions as $action) {
        if (isset($wp_filter[$action])) {
            echo "<p style='color: green;'>✓ AJAX action '$action' is registered</p>\n";
        } else {
            echo "<p style='color: red;'>✗ AJAX action '$action' is NOT registered</p>\n";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 2: Check nonce generation
echo "<h3>2. Nonce Generation Test</h3>\n";

$nonce = wp_create_nonce('hp_gedcom_upload');
echo "<p>Generated nonce: " . htmlspecialchars($nonce) . "</p>\n";

$verified = wp_verify_nonce($nonce, 'hp_gedcom_upload');
echo "<p>Nonce verification: " . ($verified ? "✓ Valid" : "✗ Invalid") . "</p>\n";

// Test 3: Upload directory check
echo "<h3>3. Upload Directory Test</h3>\n";

$upload_dir = wp_upload_dir();
echo "<p>WordPress upload dir: " . htmlspecialchars($upload_dir['basedir']) . "</p>\n";

$gedcom_dir = $upload_dir['basedir'] . '/heritagepress/gedcom';
echo "<p>GEDCOM upload dir: " . htmlspecialchars($gedcom_dir) . "</p>\n";

if (!file_exists($gedcom_dir)) {
    if (wp_mkdir_p($gedcom_dir)) {
        echo "<p style='color: green;'>✓ Created GEDCOM upload directory</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Failed to create GEDCOM upload directory</p>\n";
    }
} else {
    echo "<p style='color: green;'>✓ GEDCOM upload directory exists</p>\n";
}

// Check permissions
if (is_writable($gedcom_dir)) {
    echo "<p style='color: green;'>✓ GEDCOM directory is writable</p>\n";
} else {
    echo "<p style='color: red;'>✗ GEDCOM directory is NOT writable</p>\n";
}

// Test 4: Simulate upload parameters
echo "<h3>4. Upload Parameters Test</h3>\n";

echo "<p>Expected POST parameters:</p>\n";
echo "<ul>\n";
echo "<li>hp_gedcom_nonce (with action: hp_gedcom_upload)</li>\n";
echo "<li>gedcom_file (file upload)</li>\n";
echo "<li>tree_id (destination tree)</li>\n";
echo "<li>import_option (replace/add/merge)</li>\n";
echo "</ul>\n";

// Test 5: Check if AssetManager is loading JavaScript
echo "<h3>5. JavaScript Loading Test</h3>\n";

try {
    $asset_manager = new \HeritagePress\Admin\AssetManager(HERITAGEPRESS_PLUGIN_URL);
    echo "<p style='color: green;'>✓ AssetManager instantiated</p>\n";

    // Check if scripts would be enqueued for import/export page
    echo "<p>JavaScript files should be loaded on import/export page:</p>\n";
    echo "<ul>\n";
    echo "<li>assets/js/admin.js (base admin scripts)</li>\n";
    echo "<li>assets/js/import-export.js (import/export functionality)</li>\n";
    echo "</ul>\n";

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ AssetManager Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<h3>Next Steps for Debugging:</h3>\n";
echo "<ol>\n";
echo "<li>Open browser console on import page and check for JavaScript errors</li>\n";
echo "<li>Verify hp_vars object is properly loaded</li>\n";
echo "<li>Check network tab for failed AJAX requests</li>\n";
echo "<li>Try uploading a small test file to see specific error</li>\n";
echo "</ol>\n";

echo "<p><a href='http://localhost/wordpress/wp-admin/admin.php?page=heritagepress-importexport'>Go to Import/Export Page →</a></p>\n";
?>