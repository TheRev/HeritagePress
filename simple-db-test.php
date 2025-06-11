<?php
/**
 * Simple Database Manager Test
 */

// WordPress environment
require_once '../../../../wp-config.php';

echo "WordPress loaded: " . (defined('ABSPATH') ? 'YES' : 'NO') . "\n";

global $wpdb;
echo "Database prefix: " . $wpdb->prefix . "\n";

// Check if we can read the core schema file
$schema_file = 'includes/Database/schema/core-tables.sql';
if (file_exists($schema_file)) {
    echo "Schema file exists: YES\n";
    $sql_content = file_get_contents($schema_file);
    echo "Schema file size: " . strlen($sql_content) . " bytes\n";
} else {
    echo "Schema file exists: NO\n";
}

// Try to include the Database Manager
try {
    require_once 'includes/Database/WPHelper.php';
    echo "WPHelper loaded: YES\n";
} catch (Exception $e) {
    echo "WPHelper error: " . $e->getMessage() . "\n";
}

try {
    require_once 'includes/Database/Manager.php';
    echo "Manager loaded: YES\n";
} catch (Exception $e) {
    echo "Manager error: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
?>