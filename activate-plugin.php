<?php
/**
 * Heritage Press Plugin Activation Script
 * Run this to activate the Heritage Press plugin programmatically
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('C:\\xampp\\htdocs\\wordpress\\wp-load.php');

echo "<h1>Heritage Press Plugin Activation</h1>\n";

// Check if plugin files exist
$plugin_file = 'heritage-press/heritage-press.php';
$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;

if (!file_exists($plugin_path)) {
    echo "<p style='color: red;'>❌ Plugin file not found: $plugin_path</p>\n";
    exit;
}

echo "<p>✅ Plugin file found: $plugin_path</p>\n";

// Check if already active
if (is_plugin_active($plugin_file)) {
    echo "<p style='color: orange;'>⚠️ Plugin is already active!</p>\n";
} else {
    echo "<p>Attempting to activate plugin...</p>\n";
    
    // Activate the plugin
    $result = activate_plugin($plugin_file);
    
    if (is_wp_error($result)) {
        echo "<p style='color: red;'>❌ Activation failed: " . $result->get_error_message() . "</p>\n";
    } else {
        echo "<p style='color: green;'>✅ Plugin activated successfully!</p>\n";
    }
}

// Verify activation
if (is_plugin_active($plugin_file)) {
    echo "<p style='color: green; font-size: 18px;'><strong>✅ Heritage Press Plugin is now ACTIVE!</strong></p>\n";
    
    // Get plugin information
    $plugin_data = get_plugin_data($plugin_path);
    echo "<h3>Plugin Information:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Name:</strong> " . $plugin_data['Name'] . "</li>\n";
    echo "<li><strong>Version:</strong> " . $plugin_data['Version'] . "</li>\n";
    echo "<li><strong>Description:</strong> " . $plugin_data['Description'] . "</li>\n";
    echo "<li><strong>Author:</strong> " . $plugin_data['Author'] . "</li>\n";
    echo "</ul>\n";
    
    // Check for Heritage Press menus/capabilities
    echo "<h3>Plugin Integration Check:</h3>\n";
    
    // Check if plugin classes are loaded
    if (class_exists('HeritagePress\\Core\\Plugin')) {
        echo "<p style='color: green;'>✅ Core Plugin class loaded</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ Core Plugin class not found</p>\n";
    }
    
    // Check database tables (common genealogy plugin tables)
    global $wpdb;
    $tables_to_check = [
        $wpdb->prefix . 'heritage_individuals',
        $wpdb->prefix . 'heritage_families',
        $wpdb->prefix . 'heritage_events'
    ];
    
    echo "<h3>Database Tables:</h3>\n";
    foreach ($tables_to_check as $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
        if ($exists) {
            echo "<p style='color: green;'>✅ Table exists: $table</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ Table not found: $table (may be created on first use)</p>\n";
        }
    }
    
} else {
    echo "<p style='color: red; font-size: 18px;'><strong>❌ Plugin activation failed or not confirmed</strong></p>\n";
}

echo "<hr>\n";
echo "<p><a href='/wordpress/wp-admin/plugins.php'>View Plugins Page</a></p>\n";
echo "<p><a href='/wordpress/wp-admin/'>WordPress Admin Dashboard</a></p>\n";
?>
