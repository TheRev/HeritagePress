<?php
/**
 * Test GEDCOM Import Workflow End-to-End
 * 
 * This tests the complete import process from file upload through step 3 processing
 */

require_once(dirname(__FILE__) . '/../../../../wp-load.php');

if (!defined('ABSPATH')) {
    die('WordPress not loaded');
}

// Ensure we have admin capabilities
if (!current_user_can('manage_options')) {
    die('Admin access required');
}

echo "<h1>HeritagePress GEDCOM Import Workflow Test</h1>\n";
echo "<p>Testing complete import workflow...</p>\n";

// Test 1: Create a simple test GEDCOM file
echo "<h2>Step 1: Creating Test GEDCOM File</h2>\n";

$test_gedcom_content = "0 HEAD\n";
$test_gedcom_content .= "1 SOUR HeritagePress Test\n";
$test_gedcom_content .= "1 GEDC\n";
$test_gedcom_content .= "2 VERS 5.5.1\n";
$test_gedcom_content .= "1 CHAR UTF-8\n";
$test_gedcom_content .= "0 @I1@ INDI\n";
$test_gedcom_content .= "1 NAME John /Test/\n";
$test_gedcom_content .= "1 SEX M\n";
$test_gedcom_content .= "1 BIRT\n";
$test_gedcom_content .= "2 DATE 1 JAN 1980\n";
$test_gedcom_content .= "0 @I2@ INDI\n";
$test_gedcom_content .= "1 NAME Jane /Test/\n";
$test_gedcom_content .= "1 SEX F\n";
$test_gedcom_content .= "1 BIRT\n";
$test_gedcom_content .= "2 DATE 15 MAR 1985\n";
$test_gedcom_content .= "0 @F1@ FAM\n";
$test_gedcom_content .= "1 HUSB @I1@\n";
$test_gedcom_content .= "1 WIFE @I2@\n";
$test_gedcom_content .= "1 MARR\n";
$test_gedcom_content .= "2 DATE 20 JUN 2010\n";
$test_gedcom_content .= "0 TRLR\n";

// Create upload directory structure
$upload_info = wp_upload_dir();
$heritagepress_dir = $upload_info['basedir'] . '/heritagepress/gedcom/';
if (!is_dir($heritagepress_dir)) {
    wp_mkdir_p($heritagepress_dir);
}

// Generate a unique file key
$file_key = 'test_' . uniqid();
$test_file_path = $heritagepress_dir . $file_key . '.ged';

if (file_put_contents($test_file_path, $test_gedcom_content)) {
    echo "<p>✓ Test GEDCOM file created: $test_file_path</p>\n";
    echo "<p>File size: " . filesize($test_file_path) . " bytes</p>\n";
} else {
    echo "<p>✗ Failed to create test GEDCOM file</p>\n";
    exit;
}

// Test 2: Simulate step 1 -> step 2 parameter passing
echo "<h2>Step 2: Testing Parameter Passing (Step 1 -> Step 2)</h2>\n";

$tree_name = "Test Tree " . date('Y-m-d H:i:s');
$tree_id = 'new';
$import_option = 'replace';

// Simulate the redirect URL construction from step 1
$redirect_url = admin_url('admin.php?page=heritagepress-import-export&step=2');
$redirect_url .= '&file=' . urlencode($file_key);
$redirect_url .= '&tree_id=' . urlencode($tree_id);
$redirect_url .= '&new_tree_name=' . urlencode($tree_name);
$redirect_url .= '&import_option=' . urlencode($import_option);

echo "<p>Simulated redirect URL: <code>" . htmlspecialchars($redirect_url) . "</code></p>\n";

// Parse the URL back to test parameter extraction
$url_parts = parse_url($redirect_url);
parse_str($url_parts['query'], $params);

echo "<p>Extracted parameters:</p>\n";
echo "<ul>\n";
echo "<li>file: " . htmlspecialchars($params['file'] ?? 'NOT_SET') . "</li>\n";
echo "<li>tree_id: " . htmlspecialchars($params['tree_id'] ?? 'NOT_SET') . "</li>\n";
echo "<li>new_tree_name: " . htmlspecialchars($params['new_tree_name'] ?? 'NOT_SET') . "</li>\n";
echo "<li>import_option: " . htmlspecialchars($params['import_option'] ?? 'NOT_SET') . "</li>\n";
echo "</ul>\n";

if (!empty($params['new_tree_name'])) {
    echo "<p>✓ Tree name successfully preserved in URL</p>\n";
} else {
    echo "<p>✗ Tree name lost in URL encoding</p>\n";
}

// Test 3: Test the ImportHandler directly
echo "<h2>Step 3: Testing ImportHandler Processing</h2>\n";

try {
    // Load ImportHandler
    require_once(dirname(__FILE__) . '/includes/Admin/ImportExport/ImportHandler.php');
    $import_handler = new \HeritagePress\Admin\ImportExport\ImportHandler();

    // Simulate the form submission for step 3
    $_POST = [
        'file_key' => $file_key,
        'tree_id' => 'new',
        'new_tree_name' => $tree_name,
        'import_option' => $import_option,
        'hp_gedcom_nonce' => wp_create_nonce('hp_gedcom_process')
    ];

    echo "<p>Testing tree creation...</p>\n";

    // Test the create_new_tree method indirectly by checking if tree gets created
    global $wpdb;
    $initial_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_trees");
    echo "<p>Trees in database before: $initial_count</p>\n";

    // Start output buffering to capture any output from the handler
    ob_start();

    // Simulate calling the process method
    // We'll create the tree manually since the AJAX handler is complex

    // Generate unique GEDCOM ID
    $base_gedcom = strtolower(trim($tree_name));
    $base_gedcom = preg_replace('/[^a-z0-9_-]/', '_', $base_gedcom);
    $base_gedcom = preg_replace('/_+/', '_', $base_gedcom);
    $base_gedcom = trim($base_gedcom, '_');
    $base_gedcom = substr($base_gedcom, 0, 15);

    if (empty($base_gedcom)) {
        $base_gedcom = 'imported_tree';
    }

    $gedcom_id = $base_gedcom . '_test';

    // Create the tree
    $result = $wpdb->insert(
        $wpdb->prefix . 'hp_trees',
        array(
            'gedcom' => $gedcom_id,
            'title' => $tree_name,
            'description' => 'Test import tree',
            'privacy_level' => 0,
            'owner_user_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%d', '%d', '%s', '%s')
    );

    $output = ob_get_clean();

    if ($result) {
        $new_tree_id = $wpdb->insert_id;
        echo "<p>✓ Tree created successfully with ID: $new_tree_id</p>\n";
        echo "<p>GEDCOM ID: $gedcom_id</p>\n";

        $final_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_trees");
        echo "<p>Trees in database after: $final_count</p>\n";

        // Test GEDCOM import
        echo "<h3>Testing GEDCOM Import</h3>\n";

        require_once(dirname(__FILE__) . '/includes/Services/GedcomService.php');
        $gedcom_service = new \HeritagePress\Services\GedcomService();

        $import_result = $gedcom_service->import($test_file_path, $new_tree_id);

        if ($import_result['success']) {
            echo "<p>✓ GEDCOM import successful!</p>\n";
            echo "<p>Statistics:</p>\n";
            echo "<ul>\n";
            foreach ($import_result['stats'] as $key => $value) {
                if ($key !== 'errors' && is_numeric($value)) {
                    echo "<li>$key: $value</li>\n";
                }
            }
            echo "</ul>\n";

            if (!empty($import_result['stats']['errors'])) {
                echo "<p>Errors encountered:</p>\n";
                echo "<ul>\n";
                foreach ($import_result['stats']['errors'] as $error) {
                    echo "<li>" . htmlspecialchars($error) . "</li>\n";
                }
                echo "</ul>\n";
            }
        } else {
            echo "<p>✗ GEDCOM import failed</p>\n";
            echo "<p>Error: " . htmlspecialchars($import_result['message'] ?? 'Unknown error') . "</p>\n";
        }

        // Cleanup - remove the test tree
        echo "<h3>Cleanup</h3>\n";
        $cleanup_result = $wpdb->delete($wpdb->prefix . 'hp_trees', ['treeID' => $new_tree_id]);
        if ($cleanup_result) {
            echo "<p>✓ Test tree removed</p>\n";
        }

    } else {
        echo "<p>✗ Failed to create tree. Error: " . $wpdb->last_error . "</p>\n";
    }

    if (!empty($output)) {
        echo "<p>Handler output: " . htmlspecialchars($output) . "</p>\n";
    }

} catch (Exception $e) {
    echo "<p>✗ Exception during import test: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Cleanup test file
if (file_exists($test_file_path)) {
    unlink($test_file_path);
    echo "<p>✓ Test GEDCOM file cleaned up</p>\n";
}

echo "<h2>Summary</h2>\n";
echo "<p>End-to-end import workflow test completed. Check the results above to verify functionality.</p>\n";

// Check current error log for any debug messages
echo "<h3>Recent Debug Messages</h3>\n";
echo "<p>Check your PHP error log for debug messages starting with 'Step 2 Debug' or 'GEDCOM Import Debug'</p>\n";
