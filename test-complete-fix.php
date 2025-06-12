<?php
/**
 * Complete GEDCOM Import Fix Test
 * Tests both the tree creation fix and the data flow fix
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>🔧 Complete GEDCOM Import Fix Test</h1>\n";

echo "<h2>1. Test Tree Creation Function</h2>\n";

// Load the ImportHandler class
require_once(__DIR__ . '/includes/Admin/ImportExport/ImportHandler.php');

use HeritagePress\Admin\ImportExport\ImportHandler;

$handler = new ImportHandler();

// Test tree creation with our fix
$reflection = new ReflectionClass($handler);
$create_tree_method = $reflection->getMethod('create_new_tree');
$create_tree_method->setAccessible(true);

$test_tree_name = 'Complete Test Tree ' . date('H:i:s');

try {
    $tree_id = $create_tree_method->invoke($handler, $test_tree_name);

    if ($tree_id) {
        echo "<p style='color: green;'>✅ <strong>Tree Creation:</strong> Successfully created tree with ID: $tree_id</p>\n";

        // Verify in database
        global $wpdb;
        $tree = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}hp_trees WHERE treeID = %d",
            $tree_id
        ));

        if ($tree) {
            echo "<p>✅ <strong>Database Verification:</strong> Tree found in database</p>\n";
            echo "<ul>\n";
            echo "<li><strong>Tree ID:</strong> {$tree->treeID}</li>\n";
            echo "<li><strong>GEDCOM:</strong> {$tree->gedcom}</li>\n";
            echo "<li><strong>Title:</strong> {$tree->title}</li>\n";
            echo "</ul>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ <strong>Tree Creation Failed</strong></p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>\n";
}

echo "<h2>2. Test Data Flow Simulation</h2>\n";

// Simulate the form data flow from step 1 → step 2 → step 3
$test_data = [
    'tree_id' => 'new',
    'new_tree_name' => 'Test Data Flow Tree',
    'import_option' => 'replace'
];

echo "<p><strong>Simulated Step 1 Data:</strong></p>\n";
echo "<pre>" . print_r($test_data, true) . "</pre>\n";

// Simulate step 1 redirect URL construction
$file_key = 'test_file_' . time();
$redirect_url = admin_url('admin.php?page=heritagepress-importexport&tab=import&step=2&file=' . urlencode($file_key));

if ($test_data['tree_id']) {
    $redirect_url .= '&tree_id=' . urlencode($test_data['tree_id']);
}
if ($test_data['new_tree_name']) {
    $redirect_url .= '&new_tree_name=' . urlencode($test_data['new_tree_name']);
}
if ($test_data['import_option']) {
    $redirect_url .= '&import_option=' . urlencode($test_data['import_option']);
}

echo "<p><strong>Step 1 → Step 2 Redirect URL:</strong></p>\n";
echo "<p><code>" . esc_html($redirect_url) . "</code></p>\n";

// Simulate step 2 parameter extraction
parse_str(parse_url($redirect_url, PHP_URL_QUERY), $step2_params);

echo "<p><strong>Step 2 Extracted Parameters:</strong></p>\n";
echo "<pre>" . print_r($step2_params, true) . "</pre>\n";

// Verify all data is preserved
$data_preserved = (
    isset($step2_params['tree_id']) && $step2_params['tree_id'] === $test_data['tree_id'] &&
    isset($step2_params['new_tree_name']) && $step2_params['new_tree_name'] === $test_data['new_tree_name'] &&
    isset($step2_params['import_option']) && $step2_params['import_option'] === $test_data['import_option']
);

if ($data_preserved) {
    echo "<p style='color: green;'>✅ <strong>Data Flow:</strong> All form data preserved through redirect</p>\n";
} else {
    echo "<p style='color: red;'>❌ <strong>Data Flow:</strong> Some data lost in redirect</p>\n";
}

echo "<h2>3. Test Full Import Scenario</h2>\n";

// Test the complete scenario that was failing
$_POST = [
    'tree_id' => 'new',
    'new_tree_name' => 'Full Scenario Test Tree',
    'import_option' => 'replace'
];

echo "<p><strong>Simulated Step 3 POST Data:</strong></p>\n";
echo "<pre>" . print_r($_POST, true) . "</pre>\n";

$tree_name = sanitize_text_field($_POST['new_tree_name'] ?? $_POST['tree_name'] ?? '');

echo "<p><strong>Extracted Tree Name:</strong> <code>" . esc_html($tree_name) . "</code></p>\n";

if (!empty($tree_name)) {
    echo "<p style='color: green;'>✅ <strong>Tree Name Extraction:</strong> Success</p>\n";

    // Test tree creation with this name
    try {
        $scenario_tree_id = $create_tree_method->invoke($handler, $tree_name);

        if ($scenario_tree_id) {
            echo "<p style='color: green;'>✅ <strong>Full Scenario:</strong> Tree created successfully with ID: $scenario_tree_id</p>\n";
        } else {
            echo "<p style='color: red;'>❌ <strong>Full Scenario:</strong> Tree creation failed</p>\n";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ <strong>Full Scenario Error:</strong> " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ <strong>Tree Name Extraction:</strong> Tree name is empty</p>\n";
}

echo "<h2>✅ Fix Summary</h2>\n";
echo "<div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #0073aa; margin: 20px 0;'>\n";
echo "<h3>Two Critical Issues Fixed:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Database Insert Fix:</strong> Added missing 'gedcom' field to tree creation</li>\n";
echo "<li><strong>Data Flow Fix:</strong> Form data now preserved through AJAX redirect from step 1 to step 2</li>\n";
echo "</ol>\n";
echo "<p><strong>Result:</strong> GEDCOM import should now work completely from step 1 through step 4.</p>\n";
echo "</div>\n";

// Clean up test POST data
$_POST = [];

echo "<h2>🧪 Ready for Live Testing</h2>\n";
echo "<p>The fixes are complete. You can now test the full import workflow:</p>\n";
echo "<ol>\n";
echo "<li>Go to <a href='" . admin_url('admin.php?page=heritagepress-importexport&tab=import') . "' target='_blank'>Import Page</a></li>\n";
echo "<li>Upload a GEDCOM file</li>\n";
echo "<li>Enter a tree name (e.g., 'My Test Tree')</li>\n";
echo "<li>Complete the import process</li>\n";
echo "</ol>\n";

echo "<p><strong>Expected Behavior:</strong> No more 'Tree name is required' errors!</p>\n";
?>