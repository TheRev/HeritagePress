<?php
// Activate HeritagePress plugin and test table creation
require_once('../../../wp-config.php');
require_once('../../../wp-admin/includes/plugin.php');

echo "HeritagePress Plugin Activation Test\n";
echo "=====================================\n";

global $wpdb;
echo "Database prefix: " . $wpdb->prefix . "\n";

// Check current tables before activation
$before_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "Tables before activation: " . count($before_tables) . "\n";

// Check if plugin is already active
$plugin_file = 'heritagepress/HeritagePress/HeritagePress.php';
if (is_plugin_active($plugin_file)) {
    echo "Plugin is already active. Deactivating first...\n";
    deactivate_plugins($plugin_file);
}

echo "Activating HeritagePress plugin...\n";

// Activate the plugin
$result = activate_plugin($plugin_file);

if (is_wp_error($result)) {
    echo "Error activating plugin: " . $result->get_error_message() . "\n";
} else {
    echo "Plugin activated successfully.\n";
}

// Check tables after activation
$after_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "Tables after activation: " . count($after_tables) . "\n";

echo "\nTables created:\n";
foreach ($after_tables as $table) {
    echo "- $table\n";
}

// Expected table count
$expected_count = 32;
if (count($after_tables) === $expected_count) {
    echo "\nSUCCESS: All $expected_count tables created!\n";
} else {
    echo "\nWARNING: Expected $expected_count tables, but found " . count($after_tables) . "\n";
}

echo "\nTest completed.\n";
?>