<?php
/**
 * Fix missing external_id column in HeritagePress database tables
 */

// Include WordPress
require_once('c:/MAMP/htdocs/wordpress/wp-config.php');

global $wpdb;

echo "<h1>HeritagePress Database Schema Fix - Add external_id Column</h1>\n";

$tables_to_fix = [
    'hp_individuals' => 'Add external_id column for GEDCOM import tracking',
    'hp_families' => 'Add external_id column for GEDCOM import tracking',
    'hp_sources' => 'Add external_id column for GEDCOM import tracking'
];

foreach ($tables_to_fix as $table => $description) {
    $full_table_name = $wpdb->prefix . $table;

    echo "<h2>Checking table: $full_table_name</h2>\n";

    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    if (!$table_exists) {
        echo "<p style='color: orange;'>⚠️ Table $full_table_name does not exist. Skipping...</p>\n";
        continue;
    }

    // Check if external_id column already exists
    $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $full_table_name LIKE 'external_id'");

    if ($column_exists) {
        echo "<p style='color: green;'>✅ Column external_id already exists in $full_table_name</p>\n";
        continue;
    }

    echo "<p>📝 $description</p>\n";
    echo "<p>➕ Adding external_id column to $full_table_name...</p>\n";

    // Add the external_id column
    $sql = "ALTER TABLE $full_table_name ADD COLUMN external_id VARCHAR(20) NULL AFTER uuid";

    echo "<pre>SQL: $sql</pre>\n";

    $result = $wpdb->query($sql);

    if ($result !== false) {
        echo "<p style='color: green;'>✅ Successfully added external_id column to $full_table_name</p>\n";

        // Add index for better performance
        $index_sql = "ALTER TABLE $full_table_name ADD INDEX idx_external_id (external_id)";
        $index_result = $wpdb->query($index_sql);

        if ($index_result !== false) {
            echo "<p style='color: green;'>✅ Successfully added index on external_id column</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ Failed to add index on external_id column: " . $wpdb->last_error . "</p>\n";
        }

    } else {
        echo "<p style='color: red;'>❌ Failed to add external_id column to $full_table_name</p>\n";
        echo "<p style='color: red;'>Error: " . $wpdb->last_error . "</p>\n";
    }

    echo "<hr>\n";
}

echo "<h2>Verification</h2>\n";

// Verify the changes
foreach ($tables_to_fix as $table => $description) {
    $full_table_name = $wpdb->prefix . $table;

    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    if (!$table_exists) {
        continue;
    }

    $columns = $wpdb->get_results("SHOW COLUMNS FROM $full_table_name");

    echo "<h3>$full_table_name columns:</h3>\n";
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";

    foreach ($columns as $column) {
        $highlight = ($column->Field === 'external_id') ? 'style="background-color: #90EE90;"' : '';
        echo "<tr $highlight>";
        echo "<td>{$column->Field}</td>";
        echo "<td>{$column->Type}</td>";
        echo "<td>{$column->Null}</td>";
        echo "<td>{$column->Key}</td>";
        echo "<td>{$column->Default}</td>";
        echo "<td>{$column->Extra}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
}

echo "<h2>Summary</h2>\n";
echo "<p>✅ Database schema fixes completed!</p>\n";
echo "<p>📌 The external_id column is now available for GEDCOM import operations.</p>\n";
echo "<p>🔄 You can now retry the GEDCOM import.</p>\n";
?>