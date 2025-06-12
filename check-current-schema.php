<?php
/**
 * Quick check of current database schema
 */

require_once('../../../wp-config.php');

global $wpdb;

echo "<h1>🔍 Current Database Schema Check</h1>\n";

$tables_to_check = ['hp_sources', 'hp_repositories', 'hp_media'];

foreach ($tables_to_check as $table) {
    $full_table_name = $wpdb->prefix . $table;

    echo "<h2>Table: {$full_table_name}</h2>\n";

    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");

    if (!$table_exists) {
        echo "<p style='color: red;'>❌ Table does not exist</p>\n";
        continue;
    }

    // Get table structure
    $columns = $wpdb->get_results("DESCRIBE {$full_table_name}");

    if ($columns) {
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";

        $missing_columns = [];
        $expected_columns = [];

        if ($table === 'hp_sources')
            $expected_columns = ['type'];
        if ($table === 'hp_repositories')
            $expected_columns = ['addressID'];
        if ($table === 'hp_media')
            $expected_columns = ['mediakey'];

        foreach ($columns as $column) {
            $is_missing = in_array($column->Field, $expected_columns);
            $style = $is_missing ? "style='background-color: #d4edda;'" : "";

            echo "<tr {$style}>\n";
            echo "<td>{$column->Field}</td>\n";
            echo "<td>{$column->Type}</td>\n";
            echo "<td>{$column->Null}</td>\n";
            echo "<td>{$column->Key}</td>\n";
            echo "<td>{$column->Default}</td>\n";
            echo "</tr>\n";

            if ($is_missing) {
                $expected_columns = array_diff($expected_columns, [$column->Field]);
            }
        }
        echo "</table>\n";

        // Show missing columns
        if (!empty($expected_columns)) {
            echo "<p style='color: red;'><strong>❌ Missing columns:</strong> " . implode(', ', $expected_columns) . "</p>\n";
        } else {
            echo "<p style='color: green;'><strong>✅ All required columns present</strong></p>\n";
        }
    }

    echo "<hr>\n";
}
?>