<?php
/**
 * Debug AJAX Upload Response
 * 
 * Test what the AJAX upload handler actually returns
 */

// WordPress environment
require_once('../../../../../../../wp-config.php');

// Simulate an AJAX request
$_POST = array(
    'action' => 'hp_upload_gedcom',
    'hp_gedcom_nonce' => wp_create_nonce('hp_gedcom_upload'),
    'tree_id' => 'new',
    'new_tree_name' => 'Test Tree',
    'import_option' => 'replace'
);

// Create a test file upload
$test_gedcom_content = "0 HEAD\n1 SOUR Test\n1 GEDC\n2 VERS 5.5.1\n2 FORM LINEAGE-LINKED\n1 CHAR UTF-8\n0 @I1@ INDI\n1 NAME Test /Person/\n0 TRLR";
$temp_file = tempnam(sys_get_temp_dir(), 'gedcom_test');
file_put_contents($temp_file, $test_gedcom_content);

$_FILES = array(
    'gedcom_file' => array(
        'name' => 'test.ged',
        'tmp_name' => $temp_file,
        'size' => strlen($test_gedcom_content),
        'error' => UPLOAD_ERR_OK,
        'type' => 'application/octet-stream'
    )
);

echo "<h2>Debug AJAX Upload Response</h2>\n";
echo "<h3>Input Data:</h3>\n";
echo "<pre>";
echo "POST: " . print_r($_POST, true);
echo "FILES: " . print_r($_FILES, true);
echo "</pre>\n";

// Capture the output
ob_start();

try {
    // Initialize the plugin
    if (class_exists('HeritagePress\Admin\ImportExport\ImportHandler')) {
        $handler = new HeritagePress\Admin\ImportExport\ImportHandler();
        $handler->handle_gedcom_upload();
    } else {
        echo "ImportHandler class not found!\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();

echo "<h3>Handler Output:</h3>\n";
echo "<pre>" . htmlspecialchars($output) . "</pre>\n";

// Check what wp_send_json_success would output
echo "<h3>JSON Response Check:</h3>\n";
echo "<p>Testing wp_send_json_success format...</p>\n";

// Clean up
unlink($temp_file);
?>