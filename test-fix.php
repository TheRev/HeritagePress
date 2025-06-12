<?php
/**
 * Test the ImportHandler fix
 */

require_once('../../../wp-config.php');

// Test if ImportHandler can create a new tree with GEDCOM ID
echo "<h1>Testing ImportHandler Fix</h1>\n";

// Include the ImportHandler class
require_once('includes/Admin/ImportExport/ImportHandler.php');

global $wpdb;

// Check if hp_trees table exists and has gedcom column
$table_name = $wpdb->prefix . 'hp_trees';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if ($table_exists) {
    echo "<p>✅ Table $table_name exists</p>\n";
    
    // Check structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "<h3>Table Structure:</h3>\n";
    echo "<ul>\n";
    foreach ($columns as $col) {
        echo "<li>{$col->Field} - {$col->Type}</li>\n";
    }
    echo "</ul>\n";
    
    // Check if gedcom column exists
    $has_gedcom = false;
    foreach ($columns as $col) {
        if ($col->Field == 'gedcom') {
            $has_gedcom = true;
            break;
        }
    }
    
    if ($has_gedcom) {
        echo "<p>✅ GEDCOM column exists</p>\n";
    } else {
        echo "<p>❌ GEDCOM column missing</p>\n";
    }
    
    // Test creating a new tree using reflection to access private method
    try {
        $handler = new \HeritagePress\Admin\ImportExport\ImportHandler();
        $reflection = new ReflectionClass($handler);
        $method = $reflection->getMethod('create_new_tree');
        $method->setAccessible(true);
        
        $test_tree_name = 'Test Tree ' . time();
        echo "<h3>Testing Tree Creation:</h3>\n";
        echo "<p>Creating tree: $test_tree_name</p>\n";
        
        $tree_id = $method->invoke($handler, $test_tree_name);
        
        if ($tree_id) {
            echo "<p>✅ Tree created successfully with ID: $tree_id</p>\n";
            
            // Check the created tree
            $tree = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE treeID = %d", $tree_id));
            if ($tree) {
                echo "<p>Tree details:</p>\n";
                echo "<ul>\n";
                echo "<li>ID: {$tree->treeID}</li>\n";
                echo "<li>GEDCOM: {$tree->gedcom}</li>\n";
                echo "<li>Title: {$tree->title}</li>\n";
                echo "<li>Description: {$tree->description}</li>\n";
                echo "</ul>\n";
                
                // Clean up test tree
                $wpdb->delete($table_name, array('treeID' => $tree_id));
                echo "<p>✅ Test tree cleaned up</p>\n";
            }
        } else {
            echo "<p>❌ Failed to create tree</p>\n";
            echo "<p>Last error: " . $wpdb->last_error . "</p>\n";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error testing tree creation: " . $e->getMessage() . "</p>\n";
    }
    
} else {
    echo "<p>❌ Table $table_name does not exist</p>\n";
}

echo "<h2>Complete!</h2>\n";
?>
