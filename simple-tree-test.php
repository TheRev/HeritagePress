<?php
// Simple test to verify trees exist and are accessible
require_once('../../../wp-config.php');

echo "<h2>Direct Tree Access Test</h2>";

global $wpdb;
$table_name = $wpdb->prefix . 'hp_trees';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
echo "<p>Table exists: " . ($table_exists ? 'YES' : 'NO') . "</p>";

if ($table_exists) {
    // Get trees
    $trees = $wpdb->get_results("SELECT * FROM $table_name ORDER BY title ASC");
    echo "<p>Trees found: " . count($trees) . "</p>";

    if (!empty($trees)) {
        echo "<h3>Trees in database:</h3>";
        echo "<ul>";
        foreach ($trees as $tree) {
            echo "<li>ID: {$tree->id}, Title: {$tree->title}</li>";
        }
        echo "</ul>";

        echo "<h3>HTML Select Test:</h3>";
        echo "<select>";
        echo '<option value="new">Create New Tree</option>';
        foreach ($trees as $tree) {
            if (isset($tree->id)) {
                echo '<option value="' . esc_attr($tree->id) . '">' . esc_html($tree->title) . '</option>';
            }
        }
        echo "</select>";
    }
}

// Test if WordPress functions are available
echo "<h3>WordPress Function Test:</h3>";
echo "<p>esc_attr available: " . (function_exists('esc_attr') ? 'YES' : 'NO') . "</p>";
echo "<p>esc_html available: " . (function_exists('esc_html') ? 'YES' : 'NO') . "</p>";
echo "<p>esc_html_e available: " . (function_exists('esc_html_e') ? 'YES' : 'NO') . "</p>";
?>