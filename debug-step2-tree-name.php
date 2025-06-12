<?php
/**
 * Debug Step 2 Tree Name Issue
 */

// Include WordPress
require_once '../../../../../../../wp-config.php';

echo "<h1>Debug: Step 2 Tree Name Issue</h1>";

// Simulate the data flow from step 1 to step 2
echo "<h2>Simulating GET parameters from step 1 → step 2</h2>";

// These are the parameters that would come from step 1 AJAX redirect
$test_params = [
    'tree_id' => 'new',
    'new_tree_name' => 'Test Family Tree',
    'import_option' => 'replace'
];

echo "<pre>";
foreach ($test_params as $key => $value) {
    echo "GET parameter: $key = '$value'\n";
}
echo "</pre>";

// Test what step 2 would extract
$tree_id = $test_params['tree_id'] ?? 'new';
$new_tree_name = $test_params['new_tree_name'] ?? '';
$import_option = $test_params['import_option'] ?? 'replace';

echo "<h3>Step 2 extracted values:</h3>";
echo "<pre>";
echo "tree_id: '$tree_id'\n";
echo "new_tree_name: '$new_tree_name'\n";
echo "import_option: '$import_option'\n";
echo "</pre>";

// Test the hidden form fields that would be generated
echo "<h3>Hidden fields that would be in the form:</h3>";
echo "<pre>";
echo "&lt;input type=\"hidden\" name=\"tree_id\" value=\"" . esc_attr($tree_id) . "\"&gt;\n";
echo "&lt;input type=\"hidden\" name=\"new_tree_name\" value=\"" . esc_attr($new_tree_name) . "\"&gt;\n";
echo "&lt;input type=\"hidden\" name=\"import_option\" value=\"" . esc_attr($import_option) . "\"&gt;\n";
echo "</pre>";

echo "<h2>Testing Current URL Parameters</h2>";
echo "<p>Current GET parameters:</p>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<p>Current POST parameters:</p>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Check if we can access step 1 to see what it's actually sending
echo "<h2>Checking Step 1 AJAX</h2>";
echo "<p>To fix this issue, we need to check:</p>";
echo "<ol>";
echo "<li>Is step 1 properly setting the tree name before redirect?</li>";
echo "<li>Is the AJAX redirect URL properly including new_tree_name?</li>";
echo "<li>Is step 2 properly extracting the new_tree_name from GET parameters?</li>";
echo "<li>Is step 2 properly setting the new_tree_name in the hidden form field?</li>";
echo "</ol>";

// Let's check the actual step 1 template
echo "<h3>Checking Step 1 AJAX Code</h3>";
$step1_file = dirname(__FILE__) . '/includes/templates/import/step1-upload.php';
if (file_exists($step1_file)) {
    echo "<p>Reading step 1 template...</p>";
    $step1_content = file_get_contents($step1_file);

    // Look for the AJAX success handler
    if (preg_match('/success.*?function.*?\{.*?\}/s', $step1_content, $matches)) {
        echo "<h4>Found AJAX success handler:</h4>";
        echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
    }

    // Look for window.location assignments
    if (preg_match_all('/window\.location[^;]+/i', $step1_content, $matches)) {
        echo "<h4>Found redirect statements:</h4>";
        foreach ($matches[0] as $match) {
            echo "<pre>" . htmlspecialchars($match) . "</pre>";
        }
    }
} else {
    echo "<p>Step 1 file not found at: $step1_file</p>";
}

?>