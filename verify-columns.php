<?php
/**
 * Verify Database Columns
 */

// Load WordPress
require_once('../../../wp-config.php');

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied.');
}

global $wpdb;

echo "<h1>Database Column Verification</h1>";

$tables_to_check = [
    'wp_hp_people' => ['person_id'],
    'wp_hp_families' => ['family_id'],
    'wp_hp_sources' => ['source_id'],
    'wp_hp_repositories' => ['name'],
    'wp_hp_media' => ['media_id']
];

foreach ($tables_to_check as $table => $columns) {
    echo "<h2>Table: $table</h2>";

    // Get table structure
    $result = $wpdb->get_results("DESCRIBE $table");

    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

        foreach ($result as $row) {
            $highlight = in_array($row->Field, $columns) ? "style='background-color: yellow;'" : "";
            echo "<tr $highlight>";
            echo "<td>{$row->Field}</td>";
            echo "<td>{$row->Type}</td>";
            echo "<td>{$row->Null}</td>";
            echo "<td>{$row->Key}</td>";
            echo "<td>{$row->Default}</td>";
            echo "<td>{$row->Extra}</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Check if required columns exist
        $existing_fields = array_column($result, 'Field');
        foreach ($columns as $required_column) {
            if (in_array($required_column, $existing_fields)) {
                echo "<p style='color: green;'>✅ Column '$required_column' exists</p>";
            } else {
                echo "<p style='color: red;'>❌ Column '$required_column' missing</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Table $table does not exist or error: " . $wpdb->last_error . "</p>";
    }

    echo "<hr>";
}

echo "<h2>Next Steps</h2>";
echo "<p><a href='quick-column-fix.php'>Run Column Fix</a> | ";
echo "<a href='" . admin_url('admin.php?page=heritagepress-import') . "'>Test Import</a></p>";
?>