<?php
/**
 * Test script to verify table creation
 * Run this from WordPress admin or via wp-cli
 */

// Load WordPress
require_once('../../../wp-config.php');

echo "=== HeritagePress Table Creation Test ===\n";

// Include the Database Manager
require_once('includes/Database/Manager.php');
require_once('includes/Database/WPHelper.php');

use HeritagePress\Database\Manager;

// Create manager instance
$manager = new Manager(__DIR__, '1.0.0');

echo "Testing table creation...\n";

// Try to create tables
try {
    $manager->install();
    echo "✓ Install method completed\n";
} catch (Exception $e) {
    echo "✗ Error during install: " . $e->getMessage() . "\n";
}

// Check which tables exist
global $wpdb;
$tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");

echo "\nTables found:\n";
if (empty($tables)) {
    echo "✗ No HeritagePress tables found\n";
} else {
    foreach ($tables as $table) {
        $table_name = array_values((array) $table)[0];
        echo "✓ " . $table_name . "\n";
    }
}

echo "\n=== Test Complete ===\n";
