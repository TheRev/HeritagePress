<?php
/**
 * Fix missing external_id columns in HeritagePress database tables
 */

require_once('../../../../../../wp-config.php');

echo "<h1>🔧 Adding Missing external_id Columns</h1>\n";

global $wpdb;

// Define the required schema updates
$schema_updates = [
    'hp_individuals' => [
        'column' => 'external_id',
        'definition' => 'VARCHAR(50) NULL',
        'after' => 'uuid'
    ],
    'hp_families' => [
        'column' => 'external_id',
        'definition' => 'VARCHAR(50) NULL',
        'after' => 'uuid'
    ]
];

$success_count = 0;
$error_count = 0;

foreach ($schema_updates as $table => $update) {
    $full_table_name = $wpdb->prefix . $table;

    echo "<h2>🔄 Updating table: {$full_table_name}</h2>\n";

    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");

    if (!$table_exists) {
        echo "<p style='color: red;'>❌ Table {$full_table_name} does not exist</p>\n";
        $error_count++;
        continue;
    }

    // Check if column already exists
    $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$full_table_name} LIKE '{$update['column']}'");

    if ($column_exists) {
        echo "<p style='color: orange;'>⚠️ Column {$update['column']} already exists in {$full_table_name}</p>\n";
        continue;
    }

    // Add the column
    $sql = "ALTER TABLE {$full_table_name} ADD COLUMN {$update['column']} {$update['definition']} AFTER {$update['after']}";

    echo "<p><strong>SQL:</strong> <code>{$sql}</code></p>\n";

    $result = $wpdb->query($sql);

    if ($result !== false) {
        echo "<p style='color: green;'>✅ Successfully added {$update['column']} column to {$full_table_name}</p>\n";
        $success_count++;

        // Verify the column was added
        $verify = $wpdb->get_var("SHOW COLUMNS FROM {$full_table_name} LIKE '{$update['column']}'");
        if ($verify) {
            echo "<p style='color: green;'>✅ Verified: Column {$update['column']} exists</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Verification failed: Column not found after adding</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to add {$update['column']} column to {$full_table_name}</p>\n";
        echo "<p><strong>Error:</strong> " . $wpdb->last_error . "</p>\n";
        $error_count++;
    }

    echo "<hr>\n";
}

echo "<h2>📊 Summary</h2>\n";
echo "<table border='1' style='border-collapse: collapse;'>\n";
echo "<tr><th>Result</th><th>Count</th></tr>\n";
echo "<tr><td style='color: green;'>Successful Updates</td><td>{$success_count}</td></tr>\n";
echo "<tr><td style='color: red;'>Errors</td><td>{$error_count}</td></tr>\n";
echo "</table>\n";

if ($success_count > 0 && $error_count === 0) {
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>\n";
    echo "<h3>🎉 Schema Update Complete!</h3>\n";
    echo "<p>All required external_id columns have been added successfully.</p>\n";
    echo "<p><strong>You can now retry the GEDCOM import process.</strong></p>\n";
    echo "</div>\n";
} else if ($error_count > 0) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>\n";
    echo "<h3>⚠️ Some Updates Failed</h3>\n";
    echo "<p>There were errors updating the database schema. Please check the errors above.</p>\n";
    echo "</div>\n";
}

echo "<h2>🧪 Next Steps:</h2>\n";
echo "<ol>\n";
echo "<li>Verify the columns were added correctly</li>\n";
echo "<li>Try the GEDCOM import process again</li>\n";
echo "<li>Check that records are being inserted into the database</li>\n";
echo "</ol>\n";

// Show updated table structures
echo "<h2>📋 Updated Table Structures:</h2>\n";
foreach (array_keys($schema_updates) as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $columns = $wpdb->get_results("DESCRIBE {$full_table_name}");

    echo "<h3>{$full_table_name}</h3>\n";
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>\n";

    foreach ($columns as $column) {
        $highlight = ($column->Field === 'external_id') ? "style='background-color: #ffffcc;'" : "";
        echo "<tr {$highlight}>\n";
        echo "<td>{$column->Field}</td>\n";
        echo "<td>{$column->Type}</td>\n";
        echo "<td>{$column->Null}</td>\n";
        echo "<td>{$column->Key}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
}
?>