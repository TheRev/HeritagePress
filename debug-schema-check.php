<?php
/**
 * Check Current Database Schema vs Expected Schema
 */

// Set up WordPress environment
define('WP_USE_THEMES', false);
require_once('../../../../../../../wp-load.php');

echo "<h2>Database Schema Check</h2>\n";

global $wpdb;

$tables = [
    'wp_hp_people' => ['person_id', 'gedcom', 'lastname', 'firstname', 'birthdate', 'sex'],
    'wp_hp_families' => ['family_id', 'gedcom', 'husband', 'wife', 'marriage_date'],
    'wp_hp_sources' => ['source_id', 'gedcom', 'title', 'author', 'publisher'],
    'wp_hp_repositories' => ['repo_id', 'gedcom', 'name', 'address'],
    'wp_hp_media' => ['media_id', 'gedcom', 'file_path', 'title']
];

foreach ($tables as $table => $expected_columns) {
    echo "<h3>Table: $table</h3>\n";

    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if (!$table_exists) {
        echo "<p>❌ Table does not exist</p>\n";
        continue;
    }

    // Get actual columns
    $actual_columns = $wpdb->get_results("DESCRIBE $table");
    $actual_column_names = array_map(function ($col) {
        return $col->Field; }, $actual_columns);

    echo "<p><strong>Expected columns:</strong> " . implode(', ', $expected_columns) . "</p>\n";
    echo "<p><strong>Actual columns:</strong> " . implode(', ', $actual_column_names) . "</p>\n";

    // Check for missing columns
    $missing = array_diff($expected_columns, $actual_column_names);
    $extra = array_diff($actual_column_names, $expected_columns);

    if (!empty($missing)) {
        echo "<p>❌ <strong>Missing columns:</strong> " . implode(', ', $missing) . "</p>\n";
    }

    if (!empty($extra)) {
        echo "<p>ℹ️ <strong>Extra columns:</strong> " . implode(', ', $extra) . "</p>\n";
    }

    if (empty($missing)) {
        echo "<p>✅ All expected columns present</p>\n";
    }

    echo "<hr>\n";
}
?>