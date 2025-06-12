<?php
/**
 * Test script to check trees table structure and data
 */

// Load WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "<h1>HeritagePress Trees Test</h1>";
echo "<p>Database prefix: " . $wpdb->prefix . "</p>";

// Check for hp_trees table
$table_name = $wpdb->prefix . 'hp_trees';
echo "<h2>Checking table: $table_name</h2>";

$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
if ($table_exists) {
    echo "<p style='color: green;'>✓ Table exists!</p>";

    // Get table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "<h3>Table structure:</h3><ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column->Field . " (" . $column->Type . ")</li>";
    }
    echo "</ul>";

    // Get sample data
    $sample_data = $wpdb->get_results("SELECT * FROM $table_name LIMIT 5");
    echo "<h3>Sample data (" . count($sample_data) . " rows):</h3>";
    if (!empty($sample_data)) {
        echo "<table border='1'><tr>";
        foreach ($columns as $column) {
            echo "<th>" . $column->Field . "</th>";
        }
        echo "</tr>";
        foreach ($sample_data as $row) {
            echo "<tr>";
            foreach ($columns as $column) {
                $field = $column->Field;
                echo "<td>" . ($row->$field ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data found in table.</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Table does not exist!</p>";

    // List all HP tables
    $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'", ARRAY_N);
    echo "<h3>Available HeritagePress tables:</h3><ul>";
    foreach ($tables as $table) {
        echo "<li>" . $table[0] . "</li>";
    }
    echo "</ul>";
}

// Test the DatabaseOperations trait get_trees method
echo "<h2>Testing DatabaseOperations get_trees method</h2>";
try {
    // Create a test class that uses the trait
    $test_class = new class ($wpdb) {
        use HeritagePress\Admin\DatabaseOperations;
        private $wpdb;

        public function __construct($wpdb)
        {
            $this->wpdb = $wpdb;
        }

        public function test_get_trees()
        {
            return $this->get_trees();
        }
    };

    $trees = $test_class->test_get_trees();
    echo "<p>Trees found: " . count($trees) . "</p>";
    if (!empty($trees)) {
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Description</th></tr>";
        foreach ($trees as $tree) {
            echo "<tr>";
            echo "<td>" . ($tree->id ?? 'N/A') . "</td>";
            echo "<td>" . ($tree->name ?? 'N/A') . "</td>";
            echo "<td>" . ($tree->description ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>