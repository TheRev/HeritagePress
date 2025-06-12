<?php
/**
 * Check tree structure directly
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';

echo "<h1>Tree Structure Check</h1>\n";

global $wpdb;

$trees_table = $wpdb->prefix . 'hp_trees';
echo "<p><strong>Table:</strong> $trees_table</p>\n";

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$trees_table'") == $trees_table;
echo "<p><strong>Table exists:</strong> " . ($table_exists ? 'Yes' : 'No') . "</p>\n";

if ($table_exists) {
    // Get table structure
    $columns = $wpdb->get_results("DESCRIBE $trees_table");
    echo "<h3>Table Structure:</h3>\n";
    echo "<table border='1'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Key</th><th>Extra</th></tr>\n";
    foreach ($columns as $col) {
        echo "<tr><td>{$col->Field}</td><td>{$col->Type}</td><td>{$col->Key}</td><td>{$col->Extra}</td></tr>\n";
    }
    echo "</table>\n";

    // Get sample data 
    $trees = $wpdb->get_results("SELECT * FROM $trees_table ORDER BY title ASC LIMIT 5");
    echo "<h3>Sample Data:</h3>\n";
    echo "<p><strong>Count:</strong> " . count($trees) . "</p>\n";

    if (!empty($trees)) {
        foreach ($trees as $i => $tree) {
            echo "<h4>Tree $i Properties:</h4>\n";
            echo "<ul>\n";
            foreach (get_object_vars($tree) as $prop => $value) {
                echo "<li><strong>$prop:</strong> " . esc_html($value) . "</li>\n";
            }
            echo "</ul>\n";
        }
    } else {
        echo "<p>No trees found. Creating a test tree...</p>\n";

        // Create a test tree
        $result = $wpdb->insert(
            $trees_table,
            array(
                'title' => 'Test Tree for Debug',
                'description' => 'Created for debugging purposes',
                'privacy_level' => 0,
                'owner_user_id' => 1,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%d', '%s', '%s')
        );

        if ($result) {
            echo "<p style='color: green;'>✓ Test tree created successfully. Refresh to see data.</p>\n";
        } else {
            echo "<p style='color: red;'>✗ Failed to create test tree: " . $wpdb->last_error . "</p>\n";
        }
    }
} else {
    echo "<p style='color: red;'>Table does not exist!</p>\n";
}

echo "<h2>Check Complete</h2>\n";
