<?php
/**
 * EMERGENCY COLUMN FIX - Force Add Missing Columns
 * This script bypasses all checks and forces the columns to be added
 */

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

// Suppress WordPress debug notices for this operation
$old_error_reporting = error_reporting(E_ERROR | E_PARSE);

echo "<h1>🚨 EMERGENCY DATABASE COLUMN FIX</h1>\n";
echo "<p>Force adding missing columns...</p>\n";

// Get database connection details
$db_host = DB_HOST;
$db_name = DB_NAME;
$db_user = DB_USER;
$db_pass = DB_PASSWORD;
$table_prefix = $wpdb->prefix;

echo "<p><strong>Database:</strong> $db_name</p>\n";
echo "<p><strong>Prefix:</strong> $table_prefix</p>\n";

// Create direct MySQLi connection
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_error) {
    die("<p style='color: red;'>❌ Connection failed: " . $mysqli->connect_error . "</p>");
}

echo "<p style='color: green;'>✅ Direct MySQL connection established</p>\n";

// SQL commands to add columns
$commands = [
    [
        'table' => $table_prefix . 'hp_sources',
        'column' => 'type',
        'sql' => "ALTER TABLE `{$table_prefix}hp_sources` ADD COLUMN `type` VARCHAR(20) NULL AFTER `callnum`"
    ],
    [
        'table' => $table_prefix . 'hp_repositories',
        'column' => 'addressID',
        'sql' => "ALTER TABLE `{$table_prefix}hp_repositories` ADD COLUMN `addressID` INT NOT NULL DEFAULT 0 AFTER `reponame`"
    ],
    [
        'table' => $table_prefix . 'hp_media',
        'column' => 'mediakey',
        'sql' => "ALTER TABLE `{$table_prefix}hp_media` ADD COLUMN `mediakey` VARCHAR(255) NOT NULL DEFAULT '' AFTER `mediatypeID`"
    ]
];

$success_count = 0;
$error_count = 0;

foreach ($commands as $cmd) {
    echo "<h3>Adding {$cmd['column']} to {$cmd['table']}</h3>\n";

    // Check if column already exists
    $check_sql = "SHOW COLUMNS FROM `{$cmd['table']}` LIKE '{$cmd['column']}'";
    $result = $mysqli->query($check_sql);

    if ($result && $result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ Column {$cmd['column']} already exists</p>\n";
        continue;
    }

    echo "<code>{$cmd['sql']}</code><br>\n";

    // Execute the ALTER TABLE command
    if ($mysqli->query($cmd['sql'])) {
        echo "<p style='color: green;'>✅ SUCCESS - Column {$cmd['column']} added!</p>\n";
        $success_count++;
    } else {
        echo "<p style='color: red;'>❌ ERROR: " . $mysqli->error . "</p>\n";
        $error_count++;
    }

    echo "<hr>\n";
}

echo "<h2>📊 Final Results</h2>\n";
echo "<p><strong>✅ Successful:</strong> $success_count</p>\n";
echo "<p><strong>❌ Errors:</strong> $error_count</p>\n";

// Show final table structures
echo "<h2>📋 Final Table Structures</h2>\n";

$tables = [
    $table_prefix . 'hp_sources',
    $table_prefix . 'hp_repositories',
    $table_prefix . 'hp_media'
];

foreach ($tables as $table) {
    echo "<h3>$table</h3>\n";

    $result = $mysqli->query("DESCRIBE `$table`");

    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>\n";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";

        while ($row = $result->fetch_assoc()) {
            $highlight = in_array($row['Field'], ['type', 'addressID', 'mediakey']) ? "style='background-color: #ffffcc;'" : "";
            echo "<tr $highlight>\n";
            echo "<td>{$row['Field']}</td>\n";
            echo "<td>{$row['Type']}</td>\n";
            echo "<td>{$row['Null']}</td>\n";
            echo "<td>{$row['Key']}</td>\n";
            echo "<td>{$row['Default']}</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
}

$mysqli->close();

// Restore error reporting
error_reporting($old_error_reporting);

echo "<h2>🧪 Next Steps</h2>\n";
echo "<p>If all columns were added successfully, try the GEDCOM import again:</p>\n";
echo "<p><a href='http://localhost:8888/wordpress/wp-admin/admin.php?page=heritagepress-import-export&tab=import' style='background: #0073aa; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>🚀 TEST GEDCOM IMPORT NOW</a></p>\n";

echo "Emergency Column Fix Starting...\n";

// Simple direct queries
$queries = [
    "ALTER TABLE wp_hp_people ADD person_id VARCHAR(50) NOT NULL DEFAULT ''",
    "ALTER TABLE wp_hp_families ADD family_id VARCHAR(50) NOT NULL DEFAULT ''",
    "ALTER TABLE wp_hp_sources ADD source_id VARCHAR(50) NOT NULL DEFAULT ''",
    "ALTER TABLE wp_hp_repositories ADD name VARCHAR(255) NOT NULL DEFAULT ''",
    "ALTER TABLE wp_hp_media ADD media_id VARCHAR(50) NOT NULL DEFAULT ''"
];

foreach ($queries as $query) {
    echo "Running: $query\n";
    $result = $wpdb->query($query);
    if ($result !== false) {
        echo "SUCCESS\n";
    } else {
        echo "ERROR: " . $wpdb->last_error . "\n";
    }
}

echo "Emergency fix complete!\n";
?>