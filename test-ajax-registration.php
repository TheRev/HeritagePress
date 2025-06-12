<?php
/**
 * Test AJAX Handler Registration
 */

// Load WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';

echo "<h1>AJAX Handler Registration Test</h1>\n";

// Load plugin classes
require_once __DIR__ . '/includes/class-heritagepress-autoloader.php';

// Force instantiate ImportHandler to check registration
echo "<h2>Testing ImportHandler instantiation</h2>\n";

try {
    $import_handler = new \HeritagePress\Admin\ImportExport\ImportHandler();
    echo "<p style='color: green;'>✓ ImportHandler instantiated successfully</p>\n";

    // Check WordPress action hook registration
    global $wp_filter;

    $actions_to_check = [
        'wp_ajax_hp_upload_gedcom',
        'wp_ajax_hp_process_gedcom',
        'wp_ajax_hp_import_progress'
    ];

    foreach ($actions_to_check as $action) {
        if (isset($wp_filter[$action]) && !empty($wp_filter[$action]->callbacks)) {
            echo "<p style='color: green;'>✓ AJAX action '$action' is registered</p>\n";

            // Show details about registered callbacks
            foreach ($wp_filter[$action]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback_id => $callback_data) {
                    $callback_info = '';
                    if (is_array($callback_data['function'])) {
                        $callback_info = get_class($callback_data['function'][0]) . '::' . $callback_data['function'][1];
                    } else {
                        $callback_info = $callback_data['function'];
                    }
                    echo "<p style='margin-left: 20px; color: blue;'>→ Callback: $callback_info (priority: $priority)</p>\n";
                }
            }
        } else {
            echo "<p style='color: red;'>✗ AJAX action '$action' is NOT registered</p>\n";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error instantiating ImportHandler: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Testing AJAX URL and Nonce</h2>\n";

// Test if admin-ajax.php works
$ajax_url = admin_url('admin-ajax.php');
echo "<p>AJAX URL: <a href='$ajax_url'>$ajax_url</a></p>\n";

// Test nonce generation
$nonce = wp_create_nonce('hp_gedcom_upload');
echo "<p>Generated nonce: $nonce</p>\n";

$verified = wp_verify_nonce($nonce, 'hp_gedcom_upload');
echo "<p>Nonce verification: " . ($verified ? "✓ Valid" : "✗ Invalid") . "</p>\n";

echo "<h2>Testing JavaScript Variables</h2>\n";

// Check if asset manager would localize scripts
try {
    $asset_manager = new \HeritagePress\Admin\AssetManager(HERITAGEPRESS_PLUGIN_URL);
    echo "<p style='color: green;'>✓ AssetManager can be instantiated</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error with AssetManager: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Complete</h2>\n";
?>