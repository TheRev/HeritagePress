<?php
/**
 * Debug Asset Loading
 */

// Set up WordPress environment
define('WP_USE_THEMES', false);
require_once('../../../../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h2>Asset Loading Debug</h2>\n";

// Check if AssetManagerService is available
if (class_exists('HeritagePress\Services\AssetManagerService')) {
    echo "<p>✅ AssetManagerService class found</p>\n";

    // Try to create an instance
    $container = new HeritagePress\Core\ServiceContainer();
    $plugin_url = plugin_dir_url(dirname(dirname(__FILE__))) . 'HeritagePress/';
    $asset_manager = new HeritagePress\Services\AssetManagerService($container, $plugin_url, '1.0.0');

    echo "<p>✅ AssetManagerService instance created</p>\n";
    echo "<p>Plugin URL: " . $plugin_url . "</p>\n";

    // Get the registered assets
    $assets = $asset_manager->get_assets();
    echo "<h3>Registered Assets:</h3>\n";
    echo "<pre>" . print_r($assets, true) . "</pre>\n";

} else {
    echo "<p>❌ AssetManagerService class NOT found</p>\n";
}

// Check what's enqueued on the current page
echo "<h3>Currently Enqueued Scripts:</h3>\n";
global $wp_scripts;
if ($wp_scripts) {
    echo "<pre>";
    foreach ($wp_scripts->queue as $handle) {
        echo "Script: $handle\n";
        if (isset($wp_scripts->registered[$handle])) {
            $script = $wp_scripts->registered[$handle];
            echo "  URL: {$script->src}\n";
            echo "  Deps: " . implode(', ', $script->deps) . "\n";

            // Check for localized data
            if (isset($wp_scripts->registered[$handle]->extra['data'])) {
                echo "  Localized data: YES\n";
                echo "  Data: " . substr($wp_scripts->registered[$handle]->extra['data'], 0, 200) . "...\n";
            } else {
                echo "  Localized data: NO\n";
            }
        }
        echo "\n";
    }
    echo "</pre>\n";
} else {
    echo "<p>No scripts enqueued</p>\n";
}

// Check if we're on the right page
$current_page = isset($_GET['page']) ? $_GET['page'] : 'unknown';
echo "<h3>Current Page:</h3>\n";
echo "<p>Page: $current_page</p>\n";
echo "<p>Should load import-export assets: " . ($current_page === 'heritagepress-importexport' ? 'YES' : 'NO') . "</p>\n";

?>