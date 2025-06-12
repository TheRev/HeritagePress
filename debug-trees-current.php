<?php
/**
 * Quick Tree Debug - Check what's in the trees table
 */

// Set up WordPress environment
define('WP_USE_THEMES', false);
require_once('../../../../../../../wp-load.php');

echo "<h2>Tree Debug - Current State</h2>\n";

global $wpdb;
if (!$wpdb) {
    die("❌ WordPress database not available");
}

$table_name = $wpdb->prefix . 'hp_trees';
echo "<p><strong>Table:</strong> $table_name</p>\n";

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
echo "<p><strong>Table exists:</strong> " . ($table_exists ? "✅ YES" : "❌ NO") . "</p>\n";

if ($table_exists) {
    // Get table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "<h3>Table Structure:</h3>\n";
    echo "<pre>";
    foreach ($columns as $column) {
        echo "{$column->Field} ({$column->Type})\n";
    }
    echo "</pre>\n";

    // Get all trees
    $trees = $wpdb->get_results("SELECT * FROM $table_name ORDER BY title ASC");
    echo "<h3>Trees in Database:</h3>\n";
    echo "<p><strong>Count:</strong> " . count($trees) . "</p>\n";

    if (!empty($trees)) {
        echo "<pre>";
        foreach ($trees as $tree) {
            echo "Tree object:\n";
            print_r($tree);
            echo "\n---\n";
        }
        echo "</pre>\n";

        // Test property access
        echo "<h3>Property Access Test:</h3>\n";
        foreach ($trees as $tree) {
            echo "<p>";
            echo "Tree: ";
            try {
                echo "ID = " . $tree->id . ", ";
            } catch (Exception $e) {
                echo "ID ERROR: " . $e->getMessage() . ", ";
            }
            try {
                echo "Title = " . $tree->title;
            } catch (Exception $e) {
                echo "Title ERROR: " . $e->getMessage();
            }
            echo "</p>\n";
        }
    } else {
        echo "<p>❌ No trees found in database</p>\n";
    }
} else {
    echo "<p>❌ hp_trees table does not exist</p>\n";
}
?>