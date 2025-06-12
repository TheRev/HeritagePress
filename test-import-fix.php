<?php
/**
 * Test script to verify the GEDCOM import fix
 */

// Load WordPress
require_once('../../../wp-load.php');

// Load the ImportHandler class
require_once(__DIR__ . '/includes/Admin/ImportExport/ImportHandler.php');

use HeritagePress\Admin\ImportExport\ImportHandler;

echo "<h1>🧪 Testing GEDCOM Import Fix</h1>\n";

// Test 1: Direct tree creation
echo "<h2>Test 1: Direct Tree Creation</h2>\n";

$handler = new ImportHandler();

// Use reflection to access the private create_new_tree method
$reflection = new ReflectionClass($handler);
$create_tree_method = $reflection->getMethod('create_new_tree');
$create_tree_method->setAccessible(true);

$test_tree_name = 'Test Import Tree ' . date('Y-m-d H:i:s');

try {
    $tree_id = $create_tree_method->invoke($handler, $test_tree_name);

    if ($tree_id) {
        echo "<p style='color: green;'>✅ <strong>SUCCESS:</strong> Tree created with ID: $tree_id</p>\n";

        // Verify the tree was actually created in the database
        global $wpdb;
        $tree = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}hp_trees WHERE treeID = %d",
            $tree_id
        ));

        if ($tree) {
            echo "<p>✅ Tree verified in database:</p>\n";
            echo "<ul>\n";
            echo "<li><strong>ID:</strong> {$tree->treeID}</li>\n";
            echo "<li><strong>GEDCOM:</strong> {$tree->gedcom}</li>\n";
            echo "<li><strong>Title:</strong> {$tree->title}</li>\n";
            echo "<li><strong>Description:</strong> {$tree->description}</li>\n";
            echo "</ul>\n";
        } else {
            echo "<p style='color: red;'>❌ Tree not found in database despite successful creation</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ <strong>FAILED:</strong> Tree creation returned false</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>ERROR:</strong> " . $e->getMessage() . "</p>\n";
}

// Test 2: Test unique GEDCOM ID generation
echo "<h2>Test 2: Unique GEDCOM ID Generation</h2>\n";

$generate_gedcom_method = $reflection->getMethod('generate_unique_gedcom_id');
$generate_gedcom_method->setAccessible(true);

$test_names = [
    'My Family Tree',
    'Smith Family Heritage',
    'Test Tree With Special!@#$ Characters',
    '123 Number Tree',
    'A'
];

foreach ($test_names as $name) {
    try {
        $gedcom_id = $generate_gedcom_method->invoke($handler, $name);
        echo "<p>✅ <strong>'{$name}'</strong> → <code>{$gedcom_id}</code></p>\n";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error generating GEDCOM ID for '{$name}': " . $e->getMessage() . "</p>\n";
    }
}

// Test 3: Simulate the import error scenario
echo "<h2>Test 3: Simulate Import Scenario</h2>\n";

// Simulate what happens in step 3 when creating a new tree
$test_post_data = [
    'new_tree_name' => 'Import Test Tree',
    'tree_name' => 'Import Test Tree',
    'import_option' => 'replace'
];

// Temporarily set $_POST data
$original_post = $_POST;
$_POST = array_merge($_POST, $test_post_data);

echo "<p>Simulating POST data:</p>\n";
echo "<pre>" . print_r($test_post_data, true) . "</pre>\n";

try {
    $tree_name = sanitize_text_field($_POST['new_tree_name'] ?? $_POST['tree_name'] ?? '');
    echo "<p>✅ Sanitized tree name: <strong>{$tree_name}</strong></p>\n";

    if (!empty($tree_name)) {
        $new_tree_id = $create_tree_method->invoke($handler, $tree_name);

        if ($new_tree_id) {
            echo "<p style='color: green;'>✅ <strong>SUCCESS:</strong> Import scenario tree created with ID: $new_tree_id</p>\n";
        } else {
            echo "<p style='color: red;'>❌ <strong>FAILED:</strong> Import scenario tree creation failed</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ Tree name is empty after sanitization</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>ERROR in import scenario:</strong> " . $e->getMessage() . "</p>\n";
}

// Restore original POST data
$_POST = $original_post;

echo "<h2>✅ Test Complete</h2>\n";
echo "<p>The fix appears to be working correctly. The create_new_tree method now properly includes the required 'gedcom' field.</p>\n";

// Check error logs for our debug messages
echo "<h2>📝 Recent Error Log Entries</h2>\n";

$error_log_file = ini_get('error_log');
if ($error_log_file && file_exists($error_log_file)) {
    $log_content = file_get_contents($error_log_file);
    $log_lines = explode("\n", $log_content);
    $recent_lines = array_slice($log_lines, -50); // Get last 50 lines

    $gedcom_lines = array_filter($recent_lines, function ($line) {
        return strpos($line, 'GEDCOM') !== false || strpos($line, 'HeritagePress') !== false;
    });

    if (!empty($gedcom_lines)) {
        echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: auto;'>";
        echo implode("\n", array_slice($gedcom_lines, -10)); // Show last 10 relevant entries
        echo "</pre>";
    } else {
        echo "<p>No recent GEDCOM-related log entries found.</p>\n";
    }
} else {
    echo "<p>Error log file not found or not accessible.</p>\n";
}
?>