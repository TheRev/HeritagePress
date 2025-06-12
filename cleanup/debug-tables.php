<?php
/**
 * Simple debug script to test table creation
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "=== HeritagePress Debug Test ===\n";
echo "WordPress loaded: " . (defined('ABSPATH') ? 'YES' : 'NO') . "\n";

global $wpdb;
echo "Database prefix: " . $wpdb->prefix . "\n";

// Test database manager
require_once('includes/Database/Manager.php');
require_once('includes/Helpers/WPHelper.php');

use HeritagePress\Database\Manager;

try {
    $manager = new Manager();
    echo "Manager created successfully\n";

    // Try to create tables
    $result = $manager->create_database_tables();
    echo "Table creation result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";

    // Check which tables exist
    $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
    echo "Found " . count($tables) . " HeritagePress tables:\n";
    foreach ($tables as $table) {
        $table_name = array_values((array) $table)[0];
        echo "  - $table_name\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "=== End Debug Test ===\n";
