<?php
/**
 * Check database schema for missing external_id columns
 */

require_once('../../../../../../wp-config.php');

echo "<h1>🔍 Database Schema Check - Missing external_id Columns</h1>\n";

global $wpdb;

echo "<h2>📋 Current Error Analysis:</h2>\n";
echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>\n";
echo "<p><strong>Error:</strong> Unknown column 'external_id' in 'field list'</p>\n";
echo "<p><strong>Tables Affected:</strong> wp_hp_individuals, wp_hp_families</p>\n";
echo "<p><strong>Cause:</strong> Database tables missing external_id column needed for GEDCOM import</p>\n";
echo "</div>\n";

// Check if tables exist and their structure
$tables_to_check = [
    'hp_individuals',
    'hp_families'
];

foreach ($tables_to_check as $table) {
    $full_table_name = $wpdb->prefix . $table;
    echo "<h3>🔍 Table: {$full_table_name}</h3>\n";

    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");

    if ($table_exists) {
        echo "<p style='color: green;'>✅ Table exists</p>\n";

        // Get table structure
        $columns = $wpdb->get_results("DESCRIBE {$full_table_name}");

        echo "<h4>Current Columns:</h4>\n";
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";

        $has_external_id = false;
        foreach ($columns as $column) {
            if ($column->Field === 'external_id') {
                $has_external_id = true;
            }
            echo "<tr>\n";
            echo "<td>{$column->Field}</td>\n";
            echo "<td>{$column->Type}</td>\n";
            echo "<td>{$column->Null}</td>\n";
            echo "<td>{$column->Key}</td>\n";
            echo "<td>{$column->Default}</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";

        if ($has_external_id) {
            echo "<p style='color: green;'>✅ external_id column exists</p>\n";
        } else {
            echo "<p style='color: red;'>❌ external_id column is MISSING</p>\n";
            echo "<p><strong>Fix needed:</strong> Add external_id column to {$full_table_name}</p>\n";
        }

    } else {
        echo "<p style='color: red;'>❌ Table does not exist</p>\n";
    }
    echo "<hr>\n";
}

echo "<h2>🔧 Required Fix:</h2>\n";
echo "<div style='background: #d1ecf1; padding: 15px; border-left: 4px solid #bee5eb;'>\n";
echo "<p>The database tables need to be updated to include the <code>external_id</code> column:</p>\n";
echo "<ul>\n";
echo "<li><strong>wp_hp_individuals:</strong> Add external_id VARCHAR(50) for GEDCOM individual IDs</li>\n";
echo "<li><strong>wp_hp_families:</strong> Add external_id VARCHAR(50) for GEDCOM family IDs</li>\n";
echo "</ul>\n";
echo "<p>This column stores the original GEDCOM record IDs (like @I123@ or @F456@) for mapping purposes.</p>\n";
echo "</div>\n";

echo "<h2>📊 Next Steps:</h2>\n";
echo "<ol>\n";
echo "<li>Add missing external_id columns to database tables</li>\n";
echo "<li>Update any indexes if needed</li>\n";
echo "<li>Test import process again</li>\n";
echo "</ol>\n";
?>