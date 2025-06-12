<?php
/**
 * Fix Database Schema - Add Missing Columns
 */

// Set up WordPress environment
define('WP_USE_THEMES', false);
require_once('../../../../../../../wp-load.php');

echo "<h2>Database Schema Fix</h2>\n";

global $wpdb;

// Define the missing columns that need to be added
$schema_fixes = [
    'wp_hp_people' => [
        'person_id' => "VARCHAR(50) NOT NULL AFTER gedcom"
    ],
    'wp_hp_families' => [
        'family_id' => "VARCHAR(50) NOT NULL AFTER gedcom"
    ],
    'wp_hp_sources' => [
        'source_id' => "VARCHAR(50) NOT NULL AFTER gedcom"
    ],
    'wp_hp_repositories' => [
        'name' => "VARCHAR(255) NOT NULL AFTER repo_id"
    ],
    'wp_hp_media' => [
        'media_id' => "VARCHAR(50) NOT NULL AFTER gedcom"
    ]
];

foreach ($schema_fixes as $table => $columns) {
    echo "<h3>Fixing Table: $table</h3>\n";

    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if (!$table_exists) {
        echo "<p>❌ Table $table does not exist, skipping</p>\n";
        continue;
    }

    foreach ($columns as $column_name => $column_definition) {
        // Check if column already exists
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table LIKE '$column_name'");

        if ($column_exists) {
            echo "<p>✅ Column $column_name already exists in $table</p>\n";
        } else {
            // Add the missing column
            $sql = "ALTER TABLE $table ADD COLUMN $column_name $column_definition";
            echo "<p>Adding column: <code>$sql</code></p>\n";

            $result = $wpdb->query($sql);
            if ($result !== false) {
                echo "<p>✅ Successfully added column $column_name to $table</p>\n";
            } else {
                echo "<p>❌ Failed to add column $column_name to $table</p>\n";
                echo "<p>Error: " . $wpdb->last_error . "</p>\n";
            }
        }
    }
}

echo "<h3>Schema Fix Complete</h3>\n";
echo "<p><a href='debug-schema-check.php'>Re-check Schema</a></p>\n";
echo "<p><a href='http://localhost:8888/wordpress/wp-admin/admin.php?page=heritagepress-importexport&tab=import'>Test Import Again</a></p>\n";
?>