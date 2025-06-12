<?php
// Test script to debug table creation
require_once('wp-config.php');

// Enable WordPress debug mode
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}

echo "Testing HeritagePress table creation...\n";

// Load the plugin files
require_once(ABSPATH . 'wp-content/plugins/heritagepress/HeritagePress/includes/bootstrap.php');
require_once(ABSPATH . 'wp-content/plugins/heritagepress/HeritagePress/includes/Database/Manager.php');
require_once(ABSPATH . 'wp-content/plugins/heritagepress/HeritagePress/includes/Database/WPHelper.php');

// Initialize database manager
$plugin_dir = ABSPATH . 'wp-content/plugins/heritagepress/HeritagePress/';
$db_manager = new \HeritagePress\Database\Manager($plugin_dir, '1.0.0');

echo "Database manager created successfully.\n";

// Check WordPress database connection
global $wpdb;
echo "WordPress database prefix: " . $wpdb->prefix . "\n";
echo "Database name: " . DB_NAME . "\n";

// Test database connection
$result = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}posts");
echo "Posts table has {$result} records - database connection OK.\n";

// Check if HeritagePress tables exist before creation
$existing_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "Existing HeritagePress tables: " . count($existing_tables) . "\n";
if (count($existing_tables) > 0) {
    foreach ($existing_tables as $table) {
        echo "  - $table\n";
    }
}

echo "\nRunning table installation...\n";

try {
    // Run the installation
    $db_manager->install();
    echo "Installation completed successfully.\n";
} catch (Exception $e) {
    echo "Error during installation: " . $e->getMessage() . "\n";
}

// Check tables after creation
$new_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "\nTables after installation: " . count($new_tables) . "\n";
if (count($new_tables) > 0) {
    foreach ($new_tables as $table) {
        echo "  - $table\n";
    }
} else {
    echo "No HeritagePress tables found!\n";
}

// Check WordPress error log
$log_file = ABSPATH . 'wp-content/debug.log';
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $heritage_logs = array_filter(explode("\n", $log_content), function ($line) {
        return strpos($line, 'HeritagePress') !== false;
    });

    if (!empty($heritage_logs)) {
        echo "\nRecent HeritagePress log entries:\n";
        foreach (array_slice($heritage_logs, -10) as $log_line) {
            echo $log_line . "\n";
        }
    }
}

echo "\nTest completed.\n";
?>