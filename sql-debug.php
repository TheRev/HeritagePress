<?php
/**
 * SQL Debug script to check exactly what's happening with the query
 */

// Load WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "<h1>SQL Debug Report</h1>";

$table_name = $wpdb->prefix . 'hp_trees';

// 1. Check table structure
echo "<h2>1. Table Structure</h2>";
$columns = $wpdb->get_results("DESCRIBE $table_name");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>" . $column->Field . "</td>";
    echo "<td>" . $column->Type . "</td>";
    echo "<td>" . $column->Null . "</td>";
    echo "<td>" . $column->Key . "</td>";
    echo "<td>" . $column->Default . "</td>";
    echo "<td>" . $column->Extra . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Test the exact query from get_trees method
echo "<h2>2. Testing get_trees Query</h2>";
$where_clause = '1=1';
$order_clause = 'ORDER BY name ASC';
$test_query = "SELECT * FROM {$table_name} WHERE {$where_clause} {$order_clause}";

echo "<p><strong>Query:</strong> " . $test_query . "</p>";

// Test the query
$result = $wpdb->get_results($test_query);
$error = $wpdb->last_error;

if ($error) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $error . "</p>";
} else {
    echo "<p style='color: green;'><strong>Success:</strong> Query returned " . count($result) . " rows</p>";

    if (!empty($result)) {
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Description</th><th>Is Public</th></tr>";
        foreach ($result as $row) {
            echo "<tr>";
            echo "<td>" . $row->id . "</td>";
            echo "<td>" . $row->name . "</td>";
            echo "<td>" . ($row->description ?? '') . "</td>";
            echo "<td>" . ($row->is_public ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// 3. Test without ORDER BY
echo "<h2>3. Testing Without ORDER BY</h2>";
$simple_query = "SELECT * FROM {$table_name}";
echo "<p><strong>Query:</strong> " . $simple_query . "</p>";

$simple_result = $wpdb->get_results($simple_query);
$simple_error = $wpdb->last_error;

if ($simple_error) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $simple_error . "</p>";
} else {
    echo "<p style='color: green;'><strong>Success:</strong> Simple query returned " . count($simple_result) . " rows</p>";
}

// 4. Test case sensitivity
echo "<h2>4. Testing Case Sensitivity</h2>";
$case_query = "SELECT * FROM {$table_name} ORDER BY `name` ASC";
echo "<p><strong>Query with backticks:</strong> " . $case_query . "</p>";

$case_result = $wpdb->get_results($case_query);
$case_error = $wpdb->last_error;

if ($case_error) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $case_error . "</p>";
} else {
    echo "<p style='color: green;'><strong>Success:</strong> Case-sensitive query worked</p>";
}
?>