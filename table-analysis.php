<?php
/**
 * Check for potential table conflicts and verify the exact table structure
 */

// Load WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "<h1>Table Conflict Analysis</h1>";

// 1. Show all tables that might be related
echo "<h2>1. All HP Tables</h2>";
$all_hp_tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'", ARRAY_N);
echo "<ul>";
foreach ($all_hp_tables as $table) {
    echo "<li>" . $table[0] . "</li>";
}
echo "</ul>";

// 2. Check for multiple hp_trees tables
echo "<h2>2. Trees Tables Search</h2>";
$trees_tables = $wpdb->get_results("SHOW TABLES LIKE '%trees%'", ARRAY_N);
echo "<ul>";
foreach ($trees_tables as $table) {
    echo "<li>" . $table[0] . "</li>";
}
echo "</ul>";

// 3. Check the exact table that get_trees() is accessing
$table_name = $wpdb->prefix . 'hp_trees';
echo "<h2>3. Analyzing Table: $table_name</h2>";

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
if ($table_exists) {
    echo "<p style='color: green;'>✓ Table exists: $table_exists</p>";

    // Get column information
    $columns = $wpdb->get_results("SHOW COLUMNS FROM `$table_name`");
    echo "<h3>Columns:</h3><ul>";
    foreach ($columns as $column) {
        echo "<li><strong>" . $column->Field . "</strong> (" . $column->Type . ")</li>";
    }
    echo "</ul>";

    // Test simple query
    echo "<h3>Test Simple SELECT:</h3>";
    $simple_test = $wpdb->get_results("SELECT id, name FROM `$table_name` LIMIT 3");
    if ($wpdb->last_error) {
        echo "<p style='color: red;'>Error: " . $wpdb->last_error . "</p>";
    } else {
        echo "<p style='color: green;'>Success! Found " . count($simple_test) . " rows</p>";
        foreach ($simple_test as $row) {
            echo "<p>ID: " . $row->id . ", Name: " . $row->name . "</p>";
        }
    }

    // Test ORDER BY
    echo "<h3>Test ORDER BY name:</h3>";
    $order_test = $wpdb->get_results("SELECT * FROM `$table_name` ORDER BY name ASC LIMIT 3");
    if ($wpdb->last_error) {
        echo "<p style='color: red;'>Error: " . $wpdb->last_error . "</p>";
    } else {
        echo "<p style='color: green;'>Success! ORDER BY works</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Table does not exist</p>";
}

// 4. Test the exact query format used by get_trees
echo "<h2>4. Testing Exact get_trees Query Format</h2>";
$where_clause = '1=1';
$order_clause = 'ORDER BY name ASC';
$test_query = "SELECT * FROM {$wpdb->prefix}hp_trees WHERE {$where_clause} {$order_clause}";
echo "<p><strong>Query:</strong> " . $test_query . "</p>";

$result = $wpdb->get_results($test_query);
if ($wpdb->last_error) {
    echo "<p style='color: red;'>Error: " . $wpdb->last_error . "</p>";
} else {
    echo "<p style='color: green;'>Success! Query returned " . count($result) . " rows</p>";
}
?>