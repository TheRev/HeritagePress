<?php
/**
 * Simple form test to debug tree name passing
 */

// Add WordPress header
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "<h2>Form Test for Tree Name Passing</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    echo "<h3>Tree Name Analysis:</h3>";
    $new_tree_name = $_POST['new_tree_name'] ?? '';
    echo "<ul>";
    echo "<li>Raw value: '" . $new_tree_name . "'</li>";
    echo "<li>Length: " . strlen($new_tree_name) . "</li>";
    echo "<li>Trimmed: '" . trim($new_tree_name) . "'</li>";
    echo "<li>Sanitized: '" . sanitize_text_field($new_tree_name) . "'</li>";
    echo "</ul>";
} else {
    echo "<h3>Test Form:</h3>";
    echo '<form method="post">';
    echo '<input type="hidden" name="tree_id" value="new">';
    echo '<input type="hidden" name="new_tree_name" value="My Test Tree">';
    echo '<input type="hidden" name="import_option" value="replace">';
    echo '<button type="submit">Submit Test Form</button>';
    echo '</form>';

    echo "<h3>Manual URL Test:</h3>";
    $test_url = admin_url('admin.php?page=heritagepress-importexport&tab=import&step=2&file=test&new_tree_name=' . urlencode('My Manual Test Tree') . '&tree_id=new&import_option=replace');
    echo '<a href="' . $test_url . '">Test Step 2 with Manual Tree Name</a>';
}
?>