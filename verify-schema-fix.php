<?php
/**
 * Quick Database Schema Verification
 * Check if the missing columns were actually added
 */

require_once('../../../wp-config.php');

global $wpdb;

echo "<h1>🔍 Database Schema Verification</h1>\n";
echo "<p>Checking if the missing columns were added successfully...</p>\n";

$tables_to_check = [
    'hp_sources' => 'type',
    'hp_repositories' => 'addressID',
    'hp_media' => 'mediakey'
];

$all_good = true;

foreach ($tables_to_check as $table => $expected_column) {
    $full_table_name = $wpdb->prefix . $table;

    echo "<h2>Table: {$full_table_name}</h2>\n";

    // Check if column exists
    $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$full_table_name} LIKE '{$expected_column}'");

    if ($column_exists) {
        echo "<p style='color: green; font-weight: bold;'>✅ Column '{$expected_column}' EXISTS</p>\n";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Column '{$expected_column}' MISSING</p>\n";
        $all_good = false;
    }
}

echo "<hr>\n";

if ($all_good) {
    echo "<div style='background: #d4edda; padding: 20px; border-left: 4px solid #28a745; margin: 20px 0;'>\n";
    echo "<h2 style='color: #155724;'>🎉 SUCCESS!</h2>\n";
    echo "<p><strong>All required columns are present in the database.</strong></p>\n";
    echo "<p>The GEDCOM import should now work without database errors.</p>\n";
    echo "<p><a href='http://localhost:8888/wordpress/wp-admin/admin.php?page=heritagepress-import-export&tab=import' style='background: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>🚀 TEST GEDCOM IMPORT NOW</a></p>\n";
    echo "</div>\n";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-left: 4px solid #dc3545; margin: 20px 0;'>\n";
    echo "<h2 style='color: #721c24;'>❌ PROBLEM DETECTED</h2>\n";
    echo "<p><strong>Some columns are still missing.</strong></p>\n";
    echo "<p>The database schema fix did not work completely. Manual intervention may be required.</p>\n";
    echo "</div>\n";
}

echo "<h2>📋 Current Table Structures</h2>\n";

foreach (array_keys($tables_to_check) as $table) {
    $full_table_name = $wpdb->prefix . $table;

    echo "<h3>{$full_table_name}</h3>\n";

    $columns = $wpdb->get_results("DESCRIBE {$full_table_name}");

    if ($columns) {
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px; width: 100%;'>\n";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";

        foreach ($columns as $column) {
            $is_target = in_array($column->Field, ['type', 'addressID', 'mediakey']);
            $style = $is_target ? "style='background-color: #ffffcc; font-weight: bold;'" : "";

            echo "<tr {$style}>\n";
            echo "<td>{$column->Field}</td>\n";
            echo "<td>{$column->Type}</td>\n";
            echo "<td>{$column->Null}</td>\n";
            echo "<td>{$column->Key}</td>\n";
            echo "<td>" . ($column->Default ?: 'NULL') . "</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
}
?>