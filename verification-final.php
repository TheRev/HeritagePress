<?php
/**
 * Final verification script for HeritagePress activation
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>HeritagePress Final Verification</h1>";

// Check if plugin is active
$plugin_path = 'heritagepress/HeritagePress/heritagepress.php';
$is_active = is_plugin_active($plugin_path);

echo "<h2>Plugin Status</h2>";
echo "<p>Plugin active: " . ($is_active ? "✓ YES" : "✗ NO") . "</p>";

// Check database tables
global $wpdb;
$tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "<p>Database tables: " . count($tables) . "</p>";

// List all tables
if (count($tables) > 0) {
    echo "<h3>HeritagePress Tables:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
}

// Check for required core tables
$core_tables = [
    'hp_trees',
    'hp_people',
    'hp_families',
    'hp_children',
    'hp_events',
    'hp_places',
    'hp_sources',
    'hp_repositories',
    'hp_citations',
    'hp_media'
];

echo "<h3>Core Tables Check:</h3>";
$missing_core = [];
foreach ($core_tables as $table) {
    $full_name = $wpdb->prefix . $table;
    if (in_array($full_name, $tables)) {
        echo "<p style='color: green;'>✓ $table</p>";
    } else {
        echo "<p style='color: red;'>✗ $table</p>";
        $missing_core[] = $table;
    }
}

if (empty($missing_core)) {
    echo "<p style='color: green; font-weight: bold;'>🎉 All core tables present!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Missing core tables: " . implode(', ', $missing_core) . "</p>";
}

// Check WordPress integration
echo "<h2>WordPress Integration</h2>";
echo "<p>WordPress Version: " . get_bloginfo('version') . "</p>";
echo "<p>Database Prefix: {$wpdb->prefix}</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check if admin menu is available
if (current_user_can('manage_options')) {
    echo "<p style='color: green;'>✓ Admin capabilities available</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Admin capabilities not available (may need to log in)</p>";
}

// Test basic database operations
echo "<h2>Database Operations Test</h2>";
try {
    // Test a simple query
    $tree_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_trees");
    echo "<p style='color: green;'>✓ Database queries working (Trees: $tree_count)</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database query failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Summary</h2>";
if (count($tables) >= 35 && empty($missing_core)) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px;'>";
    echo "<h3>✅ SUCCESS: HeritagePress is properly installed!</h3>";
    echo "<ul>";
    echo "<li>" . count($tables) . " database tables created</li>";
    echo "<li>All core functionality available</li>";
    echo "<li>Ready for GEDCOM import</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
    echo "<h3>❌ ISSUE: Installation incomplete</h3>";
    echo "<ul>";
    echo "<li>Expected 35+ tables, found " . count($tables) . "</li>";
    if (!empty($missing_core)) {
        echo "<li>Missing core tables: " . implode(', ', $missing_core) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<p><a href='" . admin_url('plugins.php') . "'>← Back to Plugins</a></p>";
?>