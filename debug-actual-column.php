<?php
// Debug script to check actual database column structure
require_once('../../../wp-config.php');

global $wpdb;

// Get table structure
$table_name = $wpdb->prefix . 'hp_trees';
$columns = $wpdb->get_results("DESCRIBE $table_name");

echo "<h2>Table Structure for $table_name:</h2>";
echo "<pre>";
foreach ($columns as $column) {
    echo sprintf(
        "Column: %-20s Type: %-20s Key: %-10s\n",
        $column->Field,
        $column->Type,
        $column->Key
    );
}
echo "</pre>";

// Get actual data to see properties
$trees = $wpdb->get_results("SELECT * FROM $table_name LIMIT 1");
if (!empty($trees)) {
    echo "<h2>Sample Tree Object Properties:</h2>";
    echo "<pre>";
    var_dump($trees[0]);
    echo "</pre>";

    echo "<h2>Available Properties:</h2>";
    echo "<pre>";
    foreach (get_object_vars($trees[0]) as $prop => $value) {
        echo "Property: $prop = $value\n";
    }
    echo "</pre>";
} else {
    echo "<p>No trees found in database</p>";
}
?>