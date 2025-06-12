<?php
/**
 * Debug Step 2 to Step 3 Form Submission
 */

// Add WordPress header
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "<h2>Debug Step 2 to Step 3 Form Submission</h2>";

// Show all GET parameters
echo "<h3>GET Parameters:</h3>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

// Show all POST parameters
echo "<h3>POST Parameters:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Show all REQUEST parameters
echo "<h3>REQUEST Parameters:</h3>";
echo "<pre>";
print_r($_REQUEST);
echo "</pre>";

// Check if we have a tree name from any source
$tree_name_sources = [
    'GET[new_tree_name]' => $_GET['new_tree_name'] ?? 'NOT SET',
    'POST[new_tree_name]' => $_POST['new_tree_name'] ?? 'NOT SET',
    'REQUEST[new_tree_name]' => $_REQUEST['new_tree_name'] ?? 'NOT SET'
];

echo "<h3>Tree Name from Different Sources:</h3>";
echo "<pre>";
print_r($tree_name_sources);
echo "</pre>";

// Check if submitted from step 2 form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Form submitted via POST - this should be step 2 to step 3 transition</h3>";

    // Extract the same variables as step 2
    $tree_id = isset($_GET['tree_id']) ? sanitize_text_field($_GET['tree_id']) : (isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : 'new');
    $new_tree_name = isset($_GET['new_tree_name']) ? sanitize_text_field($_GET['new_tree_name']) : (isset($_POST['new_tree_name']) ? sanitize_text_field($_POST['new_tree_name']) : '');
    $import_option = isset($_GET['import_option']) ? sanitize_text_field($_GET['import_option']) : (isset($_POST['import_option']) ? sanitize_text_field($_POST['import_option']) : 'replace');

    echo "<h4>Extracted Values (same logic as step 2):</h4>";
    echo "<ul>";
    echo "<li>tree_id: '" . $tree_id . "'</li>";
    echo "<li>new_tree_name: '" . $new_tree_name . "'</li>";
    echo "<li>import_option: '" . $import_option . "'</li>";
    echo "</ul>";
}

// If this is a GET request, show URL to step 2 for testing
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $step2_url = admin_url('admin.php?page=heritagepress-importexport&tab=import&step=2&file=test&new_tree_name=' . urlencode('My Test Tree'));
    echo "<h3>Testing URL for Step 2:</h3>";
    echo "<a href='" . $step2_url . "'>Go to Step 2 with test tree name</a>";
}
