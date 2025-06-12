<?php
/**
 * Install Genealogy-Based Schema
 * 
 * This script installs the complete genealogy-based schema for HeritagePress
 */

// WordPress environment
require_once '../../../../wp-config.php';

// Include Database Manager
require_once 'includes/Database/Manager.php';
require_once 'includes/Database/WPHelper.php';

use HeritagePress\Database\Manager;

echo "<h1>Installing Genealogy-Based Schema</h1>\n";

global $wpdb;

// Check current tables before installation
$before_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "<p><strong>Current tables:</strong> " . count($before_tables) . "</p>\n";

try {
    // Create database manager
    $db_manager = new Manager(__DIR__, '1.0.0');
    echo "<p>✅ Database Manager created</p>\n";

    // Run installation
    echo "<p>Running installation...</p>\n";
    $db_manager->install();
    echo "<p>✅ Installation completed</p>\n";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
    exit;
}

// Check tables after installation
$after_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
$new_tables = array_diff($after_tables, $before_tables);

echo "<h2>Installation Results</h2>\n";
echo "<p><strong>Total tables after installation:</strong> " . count($after_tables) . "</p>\n";

if (count($new_tables) > 0) {
    echo "<p><strong>New tables created (" . count($new_tables) . "):</strong></p>\n";
    echo "<ul>\n";
    foreach ($new_tables as $table) {
        echo "<li>" . str_replace($wpdb->prefix, '', $table) . "</li>\n";
    }
    echo "</ul>\n";
} else {
    echo "<p style='color: orange;'>⚠️ No new tables were created</p>\n";
}

// Verify key genealogy tables exist
$genealogy_tables = [
    'hp_people',
    'hp_families',
    'hp_children',
    'hp_events',
    'hp_sources',
    'hp_repositories',
    'hp_citations',
    'hp_media',
    'hp_xnotes',
    'hp_associations'
];

echo "<h2>Genealogy Core Tables Status</h2>\n";
foreach ($genealogy_tables as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    if ($exists) {
        echo "<p style='color: green;'>✅ {$table}</p>\n";
    } else {
        echo "<p style='color: red;'>❌ {$table} missing</p>\n";
    }
}

echo "<h2>Success!</h2>\n";
echo "<p>The genealogy-based schema has been installed. You can now test GEDCOM imports.</p>\n";
echo "<p><a href='test-new-schema.php'>Run Test Import</a></p>\n";
?>