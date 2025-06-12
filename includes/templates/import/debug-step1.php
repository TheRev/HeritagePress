<?php
/**
 * Debug template to check variable availability
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

echo "<h1>Step 1 Template Debug</h1>";

// Check what variables are available
echo "<h2>Available Variables:</h2>";
echo "<pre>";
print_r(get_defined_vars());
echo "</pre>";

// Specifically check for trees variable
echo "<h2>Trees Variable Check:</h2>";
if (isset($trees)) {
    echo "<p style='color: green;'>✓ \$trees variable is set</p>";
    echo "<p>Type: " . gettype($trees) . "</p>";
    echo "<p>Count: " . (is_countable($trees) ? count($trees) : 'Not countable') . "</p>";

    if (!empty($trees)) {
        echo "<h3>Trees Data:</h3>";
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Description</th></tr>";
        foreach ($trees as $tree) {
            echo "<tr>";
            echo "<td>" . ($tree->id ?? 'N/A') . "</td>";
            echo "<td>" . ($tree->name ?? 'N/A') . "</td>";
            echo "<td>" . ($tree->description ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>Trees variable is set but empty</p>";
    }
} else {
    echo "<p style='color: red;'>✗ \$trees variable is NOT set</p>";
}

// Test the get_trees method directly from here
echo "<h2>Direct Database Query Test:</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'hp_trees';
$direct_trees = $wpdb->get_results("SELECT * FROM $table_name ORDER BY name ASC");
echo "<p>Direct query result: " . count($direct_trees) . " trees</p>";

if (!empty($direct_trees)) {
    echo "<ul>";
    foreach ($direct_trees as $tree) {
        echo "<li>ID: " . $tree->id . " - Name: " . $tree->name . "</li>";
    }
    echo "</ul>";
}
?>