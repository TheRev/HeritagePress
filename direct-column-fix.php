<?php
/**
 * Direct Database Column Fix - Force Add Missing Columns
 */

require_once('../../../wp-config.php');

global $wpdb;

echo "<h1>🔧 Direct Database Column Fix</h1>\n";
echo "<p>Adding missing columns with direct SQL commands...</p>\n";

// Get the WordPress database prefix
$prefix = $wpdb->prefix;

echo "<p><strong>Database Prefix:</strong> {$prefix}</p>\n";

// Define the SQL commands to add missing columns
$sql_commands = [
    "ALTER TABLE {$prefix}hp_sources ADD COLUMN type VARCHAR(20) NULL AFTER callnum",
    "ALTER TABLE {$prefix}hp_repositories ADD COLUMN addressID INT NOT NULL DEFAULT 0 AFTER reponame",
    "ALTER TABLE {$prefix}hp_media ADD COLUMN mediakey VARCHAR(255) NOT NULL DEFAULT '' AFTER mediatypeID"
];

$success_count = 0;
$error_count = 0;

foreach ($sql_commands as $sql) {
    echo "<h3>Executing SQL:</h3>\n";
    echo "<code>{$sql}</code><br>\n";

    // Execute the SQL command
    $result = $wpdb->query($sql);

    if ($result !== false) {
        echo "<p style='color: green;'>✅ SUCCESS</p>\n";
        $success_count++;
    } else {
        echo "<p style='color: red;'>❌ ERROR: {$wpdb->last_error}</p>\n";
        $error_count++;

        // Check if error is because column already exists
        if (strpos($wpdb->last_error, 'Duplicate column name') !== false) {
            echo "<p style='color: orange;'>⚠️ Column already exists - this is OK</p>\n";
        }
    }

    echo "<hr>\n";
}

echo "<h2>📊 Summary</h2>\n";
echo "<p><strong>✅ Successful:</strong> {$success_count}</p>\n";
echo "<p><strong>❌ Errors:</strong> {$error_count}</p>\n";

// Show final table structures
echo "<h2>📋 Final Table Structures</h2>\n";

$tables = ['hp_sources', 'hp_repositories', 'hp_media'];

foreach ($tables as $table) {
    $full_table = $prefix . $table;

    echo "<h3>{$full_table}</h3>\n";

    $columns = $wpdb->get_results("DESCRIBE {$full_table}");

    if ($columns) {
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>\n";
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

echo "<h2>🧪 Test Import Again</h2>\n";
echo "<p><a href='http://localhost:8888/wordpress/wp-admin/admin.php?page=heritagepress-import-export&tab=import' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try GEDCOM Import Now</a></p>\n";
?>