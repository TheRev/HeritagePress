<?php
// Simple test to trigger import handler debug logging

// Include WordPress
require_once('../../../wp-config.php');

// Simulate POST data for new tree creation
$_POST = array(
    'hp_gedcom_nonce' => wp_create_nonce('hp_gedcom_upload'),
    'file_key' => 'test_file_key',
    'tree_id' => 'new',
    'new_tree_name' => 'Test Tree Name',
    'import_option' => 'replace'
);

// Include the ImportHandler
require_once('includes/Admin/ImportExport/ImportHandler.php');

echo "<h1>Testing ImportHandler Debug Logging</h1>";

// Check if our variables are set correctly
echo "<h2>POST Data Debug:</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h2>Tree name extraction test:</h2>";
$tree_name = sanitize_text_field($_POST['new_tree_name'] ?? $_POST['tree_name'] ?? '');
echo "Extracted tree name: '" . $tree_name . "'<br>";
echo "Is empty: " . (empty($tree_name) ? 'YES' : 'NO') . "<br>";

// Log to debug
error_log('GEDCOM Import Debug Test: new_tree_name=' . ($_POST['new_tree_name'] ?? 'NOT_SET'));
error_log('GEDCOM Import Debug Test: tree_name=' . ($_POST['tree_name'] ?? 'NOT_SET'));
error_log('GEDCOM Import Debug Test: extracted=' . $tree_name);

echo "<p>Debug messages logged. Check debug.log file.</p>";
?>