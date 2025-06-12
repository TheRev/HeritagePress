<?php
/**
 * Debug trees data structure
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';

echo "<h1>Trees Data Debug</h1>\n";

// Test direct database query
global $wpdb;
echo "<h2>1. Direct Database Query</h2>\n";

$trees_table = $wpdb->prefix . 'hp_trees';
echo "<p><strong>Table:</strong> $trees_table</p>\n";

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$trees_table'") == $trees_table;
echo "<p><strong>Table exists:</strong> " . ($table_exists ? 'Yes' : 'No') . "</p>\n";

if ($table_exists) {
    // Get table structure
    $columns = $wpdb->get_results("DESCRIBE $trees_table");
    echo "<h3>Table Structure:</h3>\n";
    echo "<ul>\n";
    foreach ($columns as $col) {
        echo "<li><strong>{$col->Field}</strong> ({$col->Type}) - " . ($col->Null == 'YES' ? 'NULL' : 'NOT NULL') . "</li>\n";
    }
    echo "</ul>\n";

    // Get actual data
    $trees = $wpdb->get_results("SELECT * FROM $trees_table ORDER BY title ASC");
    echo "<h3>Raw Trees Data:</h3>\n";
    echo "<p><strong>Count:</strong> " . count($trees) . "</p>\n";

    if (!empty($trees)) {
        echo "<pre>" . print_r($trees, true) . "</pre>\n";

        echo "<h3>Individual Tree Properties:</h3>\n";
        foreach ($trees as $i => $tree) {
            echo "<h4>Tree $i:</h4>\n";
            echo "<ul>\n";
            foreach (get_object_vars($tree) as $prop => $value) {
                echo "<li><strong>$prop:</strong> " . htmlspecialchars($value) . "</li>\n";
            }
            echo "</ul>\n";
        }
    } else {
        echo "<p style='color: orange;'>No trees found in database</p>\n";
    }
}

echo "<h2>2. Test ImportExportManager get_trees Method</h2>\n";

try {
    $manager = new HeritagePress\Admin\ImportExportManager();

    // Use reflection to access private method
    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('get_trees');
    $method->setAccessible(true);

    $trees_from_manager = $method->invoke($manager);

    echo "<p><strong>Trees from Manager:</strong> " . count($trees_from_manager) . "</p>\n";
    echo "<pre>" . print_r($trees_from_manager, true) . "</pre>\n";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error testing ImportExportManager: " . $e->getMessage() . "</p>\n";
}

echo "<h2>3. Create Sample Tree for Testing</h2>\n";

// Check if we have any trees, if not create one
if (empty($trees)) {
    echo "<p>No trees found, creating sample tree...</p>\n";

    $sample_tree_data = array(
        'title' => 'Sample Family Tree',
        'description' => 'Test tree for debugging',
        'privacy_level' => 0,
        'owner_user_id' => 1,
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    );

    $result = $wpdb->insert($trees_table, $sample_tree_data);

    if ($result) {
        $tree_id = $wpdb->insert_id;
        echo "<p style='color: green;'>✓ Sample tree created with ID: $tree_id</p>\n";

        // Re-fetch trees
        $trees = $wpdb->get_results("SELECT * FROM $trees_table ORDER BY title ASC");
        echo "<p><strong>Trees after creation:</strong> " . count($trees) . "</p>\n";
        echo "<pre>" . print_r($trees, true) . "</pre>\n";
    } else {
        echo "<p style='color: red;'>✗ Failed to create sample tree: " . $wpdb->last_error . "</p>\n";
    }
}

echo "<h2>Debug Complete</h2>\n";
