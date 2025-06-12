<?php
/**
 * Fix Missing Database Schema Columns for GEDCOM Import
 * 
 * This script adds the missing columns that are causing the import to fail.
 */

require_once('../../../wp-config.php');

global $wpdb;

echo "<h1>🔧 HeritagePress Database Schema Fix</h1>\n";
echo "<p>Adding missing columns for GEDCOM import compatibility...</p>\n";

// Define the missing columns that need to be added
$schema_fixes = [
    'hp_sources' => [
        'type' => "VARCHAR(20) NULL AFTER callnum"
    ],
    'hp_repositories' => [
        'addressID' => "INT NOT NULL AFTER reponame"
    ],
    'hp_media' => [
        'mediakey' => "VARCHAR(255) NOT NULL AFTER mediatypeID"
    ]
];

$success_count = 0;
$error_count = 0;
$messages = [];

foreach ($schema_fixes as $table => $columns) {
    $full_table_name = $wpdb->prefix . $table;

    echo "<h2>🔄 Updating table: {$full_table_name}</h2>\n";

    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");

    if (!$table_exists) {
        echo "<p style='color: red;'>❌ Table {$full_table_name} does not exist</p>\n";
        $error_count++;
        continue;
    }

    foreach ($columns as $column_name => $column_definition) {
        // Check if column already exists
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$full_table_name} LIKE '{$column_name}'");

        if ($column_exists) {
            echo "<p style='color: orange;'>⚠️ Column {$column_name} already exists in {$full_table_name}</p>\n";
            continue;
        }

        // Add the missing column
        $sql = "ALTER TABLE {$full_table_name} ADD COLUMN {$column_name} {$column_definition}";

        echo "<p>Adding column: <code>{$column_name}</code></p>\n";
        echo "<code>{$sql}</code><br>\n";

        $result = $wpdb->query($sql);

        if ($result !== false) {
            echo "<p style='color: green;'>✅ Successfully added column {$column_name}</p>\n";
            $success_count++;
            $messages[] = "Added {$column_name} to {$table}";
        } else {
            echo "<p style='color: red;'>❌ Failed to add column {$column_name}: {$wpdb->last_error}</p>\n";
            $error_count++;
            $messages[] = "FAILED: {$column_name} to {$table} - {$wpdb->last_error}";
        }
    }

    echo "<hr>\n";
}

// Summary
echo "<h2>📊 Summary</h2>\n";

if ($success_count > 0) {
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>\n";
    echo "<p><strong>✅ {$success_count} columns added successfully!</strong></p>\n";
    echo "<ul>\n";
    foreach ($messages as $msg) {
        if (strpos($msg, 'FAILED') === false) {
            echo "<li>{$msg}</li>\n";
        }
    }
    echo "</ul>\n";
    echo "</div>\n";
}

if ($error_count > 0) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>\n";
    echo "<p><strong>❌ {$error_count} errors occurred:</strong></p>\n";
    echo "<ul>\n";
    foreach ($messages as $msg) {
        if (strpos($msg, 'FAILED') !== false) {
            echo "<li>{$msg}</li>\n";
        }
    }
    echo "</ul>\n";
    echo "</div>\n";
}

// Show updated table structures
echo "<h2>📋 Updated Table Structures</h2>\n";

foreach (array_keys($schema_fixes) as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $columns = $wpdb->get_results("DESCRIBE {$full_table_name}");

    if ($columns) {
        echo "<h3>{$full_table_name}</h3>\n";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";

        foreach ($columns as $column) {
            $highlight = in_array($column->Field, ['type', 'addressID', 'mediakey']) ? "style='background-color: #ffffcc;'" : "";
            echo "<tr {$highlight}>\n";
            echo "<td>{$column->Field}</td>\n";
            echo "<td>{$column->Type}</td>\n";
            echo "<td>{$column->Null}</td>\n";
            echo "<td>{$column->Key}</td>\n";
            echo "<td>{$column->Default}</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
}

echo "<h2>🔄 Next Steps</h2>\n";
echo "<ol>\n";
echo "<li>The database schema has been updated with the missing columns</li>\n";
echo "<li>Try the GEDCOM import process again</li>\n";
echo "<li>The import should now complete successfully without database errors</li>\n";
echo "<li>If you still get errors, check the debug log for any other missing columns</li>\n";
echo "</ol>\n";

echo "<p><a href='http://localhost:8888/wordpress/wp-admin/admin.php?page=heritagepress-import-export&tab=import' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Test Import Again</a></p>\n";
?>