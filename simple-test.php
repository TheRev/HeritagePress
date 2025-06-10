<?php
/**
 * Simple Table Creation Test
 * Run this from command line to test table creation
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load WordPress
$wp_load_path = realpath(__DIR__ . '/../../../wp-load.php');
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
    echo "✓ WordPress loaded successfully\n";
} else {
    die("✗ WordPress not found at: $wp_load_path\n");
}

// Load plugin classes
require_once(__DIR__ . '/includes/Database/WPHelper.php');
require_once(__DIR__ . '/includes/Database/Manager.php');

echo "✓ Plugin classes loaded\n";

// Test database connection
global $wpdb;
echo "Database prefix: " . $wpdb->prefix . "\n";

// Check existing tables
$existing_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "Existing HeritagePress tables: " . count($existing_tables) . "\n";

// Create database manager
echo "Creating Database Manager...\n";
try {
    $db_manager = new HeritagePress\Database\Manager(__DIR__, '1.0.0');
    echo "✓ Database Manager created\n";

    // Run installation
    echo "Running table installation...\n";
    $db_manager->install();
    echo "✓ Installation completed\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "✗ PHP Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Check tables after installation
$new_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "HeritagePress tables after installation: " . count($new_tables) . "\n";

$created_tables = array_diff($new_tables, $existing_tables);
if (count($created_tables) > 0) {
    echo "✓ Created " . count($created_tables) . " new tables:\n";
    foreach ($created_tables as $table) {
        echo "  + $table\n";
    }
} else {
    echo "⚠️ No new tables were created\n";
}

echo "\nTest completed.\n";
?>