<?php
/**
 * Debug script to check trees in database and ImportExportManager functionality
 */

// Load WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "<h1>Trees Debug Report</h1>";

// 1. Check if hp_trees table exists and has data
$table_name = $wpdb->prefix . 'hp_trees';
echo "<h2>1. Database Check</h2>";
echo "<p>Table name: $table_name</p>";

$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
if ($table_exists) {
    echo "<p style='color: green;'>✓ Table exists</p>";

    $trees = $wpdb->get_results("SELECT * FROM $table_name ORDER BY name ASC");
    echo "<p>Trees found: " . count($trees) . "</p>";

    if (!empty($trees)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Description</th><th>Is Public</th></tr>";
        foreach ($trees as $tree) {
            echo "<tr>";
            echo "<td>" . $tree->id . "</td>";
            echo "<td>" . $tree->name . "</td>";
            echo "<td>" . ($tree->description ?? '') . "</td>";
            echo "<td>" . ($tree->is_public ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No trees found in database</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Table does not exist</p>";
}

// 2. Test DatabaseOperations trait directly
echo "<h2>2. DatabaseOperations Trait Test</h2>";
try {
    // Create test class using the trait
    $test_db_ops = new class ($wpdb) {
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

    $trait_trees = $test_db_ops->test_get_trees();
    echo "<p>Trees from get_trees() method: " . count($trait_trees) . "</p>";

    if (!empty($trait_trees)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Description</th></tr>";
        foreach ($trait_trees as $tree) {
            echo "<tr>";
            echo "<td>" . ($tree->id ?? 'N/A') . "</td>";
            echo "<td>" . ($tree->name ?? 'N/A') . "</td>";
            echo "<td>" . ($tree->description ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error testing DatabaseOperations trait: " . $e->getMessage() . "</p>";
}

// 3. Test ImportExportManager
echo "<h2>3. ImportExportManager Test</h2>";
try {
    $import_export_manager = new HeritagePress\Admin\ImportExportManager();

    // Check if the class uses the trait
    $uses_trait = in_array('HeritagePress\Admin\DatabaseOperations', class_uses($import_export_manager));
    echo "<p>Uses DatabaseOperations trait: " . ($uses_trait ? 'Yes' : 'No') . "</p>";

    // Try to call get_trees method if it exists
    if (method_exists($import_export_manager, 'get_trees')) {
        echo "<p style='color: green;'>✓ get_trees method exists</p>";

        // Use reflection to call the private method
        $reflection = new ReflectionClass($import_export_manager);
        $get_trees_method = $reflection->getMethod('get_trees');
        $get_trees_method->setAccessible(true);

        $manager_trees = $get_trees_method->invoke($import_export_manager);
        echo "<p>Trees from ImportExportManager: " . count($manager_trees) . "</p>";

        if (!empty($manager_trees)) {
            echo "<ul>";
            foreach ($manager_trees as $tree) {
                echo "<li>ID: " . ($tree->id ?? 'N/A') . " - Name: " . ($tree->name ?? 'N/A') . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>✗ get_trees method does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error testing ImportExportManager: " . $e->getMessage() . "</p>";
}

// 4. Check template variables
echo "<h2>4. Template Variable Test</h2>";
echo "<p>Simulating template include...</p>";

// Simulate what happens in render_import_tab
try {
    $import_export_manager = new HeritagePress\Admin\ImportExportManager();

    // Simulate the trees variable that would be passed to template
    $reflection = new ReflectionClass($import_export_manager);
    if ($reflection->hasMethod('get_trees')) {
        $get_trees_method = $reflection->getMethod('get_trees');
        $get_trees_method->setAccessible(true);
        $trees = $get_trees_method->invoke($import_export_manager);

        echo "<p>Trees variable for template: " . count($trees) . " items</p>";
        echo "<p>Trees variable type: " . gettype($trees) . "</p>";

        if (is_array($trees) || is_object($trees)) {
            echo "<pre>";
            print_r($trees);
            echo "</pre>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error simulating template variables: " . $e->getMessage() . "</p>";
}

?>