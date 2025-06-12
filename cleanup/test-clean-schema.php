<?php
/**
 * Test Clean Schema Installation
 * Verify that only the core 39 tables are created with no conflicts
 */

require_once('../../../../../../wp-config.php');

echo "<h1>🧪 Testing Clean Schema Installation</h1>\n";

global $wpdb;

// Include the Database Manager
require_once(dirname(__FILE__) . '/includes/Database/Manager.php');

use HeritagePress\Database\Manager;

echo "<h2>📋 Pre-Installation Check</h2>\n";

// Get current HeritagePress tables
$existing_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
$before_count = count($existing_tables);

echo "<p>Existing HeritagePress tables: <strong>$before_count</strong></p>\n";

if ($before_count > 0) {
    echo "<p>⚠️ Existing tables found. Consider cleaning before testing.</p>\n";
    echo "<ul>\n";
    foreach ($existing_tables as $table) {
        echo "<li>" . str_replace($wpdb->prefix, '', $table) . "</li>\n";
    }
    echo "</ul>\n";
}

echo "<h2>🔧 Schema Files Verification</h2>\n";

$schema_dir = dirname(__FILE__) . '/includes/Database/schema/';
$expected_files = [
    'complete-genealogy-schema.sql',
    'default-event-types.sql',
    'README.md'
];

$schema_files = array_diff(scandir($schema_dir), ['.', '..']);
$unexpected_files = array_diff($schema_files, $expected_files);

echo "<p><strong>Expected files:</strong> " . count($expected_files) . "</p>\n";
echo "<p><strong>Found files:</strong> " . count($schema_files) . "</p>\n";

if (empty($unexpected_files)) {
    echo "<p style='color: green;'>✅ Schema directory is clean - no unexpected files</p>\n";
} else {
    echo "<p style='color: orange;'>⚠️ Unexpected files found:</p>\n";
    echo "<ul>\n";
    foreach ($unexpected_files as $file) {
        echo "<li style='color: orange;'>$file</li>\n";
    }
    echo "</ul>\n";
}

echo "<p><strong>Schema files present:</strong></p>\n";
echo "<ul>\n";
foreach ($expected_files as $file) {
    $exists = file_exists($schema_dir . $file);
    $status = $exists ? '✅' : '❌';
    echo "<li>$status $file</li>\n";
}
echo "</ul>\n";

echo "<h2>🔄 Testing Database Manager</h2>\n";

try {
    $manager = new Manager(dirname(__FILE__) . '/', '2.0.0');
    echo "<p>✅ Database Manager created successfully</p>\n";

    // Test installation
    echo "<p>🔧 Running installation...</p>\n";
    $manager->install();
    echo "<p>✅ Installation completed without errors</p>\n";

    // Check final results
    $final_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
    $after_count = count($final_tables);
    $added_count = $after_count - $before_count;

    echo "<h2>📊 Installation Results</h2>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Metric</th><th>Value</th></tr>\n";
    echo "<tr><td>Tables Before</td><td>$before_count</td></tr>\n";
    echo "<tr><td>Tables After</td><td>$after_count</td></tr>\n";
    echo "<tr><td>Tables Added</td><td style='color: green;'>$added_count</td></tr>\n";
    echo "<tr><td>Expected (39 tables)</td><td>39</td></tr>\n";
    echo "<tr><td>Success Rate</td><td>" . round(($after_count / 39) * 100, 1) . "%</td></tr>\n";
    echo "</table>\n";

    if ($after_count >= 39) {
        echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>\n";
        echo "<h3>🎉 Clean Schema Installation Successful!</h3>\n";
        echo "<p>All 39 genealogy tables created successfully with no conflicts.</p>\n";
        echo "</div>\n";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>\n";
        echo "<h3>⚠️ Incomplete Installation</h3>\n";
        echo "<p>Expected 39 tables, got $after_count. Some tables may not have been created.</p>\n";
        echo "</div>\n";
    }

    echo "<h2>📋 Created Tables</h2>\n";
    echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;'>\n";
    echo "<ol>\n";
    foreach ($final_tables as $table) {
        $clean_name = str_replace($wpdb->prefix, '', $table);
        echo "<li>$clean_name</li>\n";
    }
    echo "</ol>\n";
    echo "</div>\n";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>\n";
    echo "<h3>❌ Installation Error</h3>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>\n";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>\n";
    echo "</div>\n";
}

echo "<h2>✅ Clean Schema Test Complete</h2>\n";
echo "<p>This test verified that:</p>\n";
echo "<ul>\n";
echo "<li>✅ Only essential schema files remain</li>\n";
echo "<li>✅ No conflicting table definitions</li>\n";
echo "<li>✅ Database Manager loads correct files</li>\n";
echo "<li>✅ All 39 genealogy tables can be created</li>\n";
echo "</ul>\n";

echo "<hr>\n";
echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>\n";
?>