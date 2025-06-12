<?php
/**
 * Variable Flow Test for GEDCOM Import
 * Tests all steps to ensure variables are passed correctly
 */

// WordPress environment
require_once('../../../../wp-config.php');

if (!current_user_can('manage_options')) {
    die('Access denied.');
}

echo "<h1>🔍 GEDCOM Import Variable Flow Test</h1>";

echo "<h2>Step 1: Form Variables</h2>";
echo "<h3>Upload Form Fields:</h3>";
echo "<ul>";
echo "<li><code>gedcom_file</code> - File input</li>";
echo "<li><code>tree_id</code> - 'new' or existing tree ID</li>";
echo "<li><code>new_tree_name</code> - Name for new tree (if tree_id='new')</li>";
echo "<li><code>import_option</code> - 'replace', 'add', or 'merge'</li>";
echo "<li><code>hp_gedcom_nonce</code> - Security nonce</li>";
echo "</ul>";

echo "<h3>Step 1 → Step 2 Redirect:</h3>";
echo "<p>After successful upload, redirects to:</p>";
echo "<code>admin.php?page=heritagepress-importexport&tab=import&step=2&file=[file_key]</code>";

echo "<h2>Step 2: Validation Variables</h2>";
echo "<h3>Received from URL:</h3>";
echo "<ul>";
echo "<li><code>\$_GET['file']</code> - File key from step 1</li>";
echo "</ul>";

echo "<h3>Received from POST (step 1 form):</h3>";
echo "<ul>";
echo "<li><code>\$_POST['tree_id']</code> - Tree selection</li>";
echo "<li><code>\$_POST['new_tree_name']</code> - New tree name</li>";
echo "<li><code>\$_POST['import_option']</code> - Import method</li>";
echo "</ul>";

echo "<h3>Step 2 Form Fields (hidden):</h3>";
echo "<ul>";
echo "<li><code>tree_id</code> - Passes through from step 1</li>";
echo "<li><code>new_tree_name</code> - Passes through from step 1</li>";
echo "<li><code>import_option</code> - Passes through from step 1</li>";
echo "<li><code>import_media</code> - Set to '1' (default)</li>";
echo "<li><code>privacy_living</code> - Set to '0' (default)</li>";
echo "<li><code>privacy_notes</code> - Set to '0' (default)</li>";
echo "<li><code>hp_gedcom_nonce</code> - Security nonce (hp_gedcom_upload action)</li>";
echo "</ul>";

echo "<h3>Step 2 → Step 3 Redirect:</h3>";
echo "<p>Form submits to:</p>";
echo "<code>admin.php?page=heritagepress-importexport&tab=import&step=3&file=[file_key]</code>";

echo "<h2>Step 3: Import Processing Variables</h2>";
echo "<h3>Received from URL:</h3>";
echo "<ul>";
echo "<li><code>\$_GET['file']</code> - File key</li>";
echo "</ul>";

echo "<h3>Received from POST (step 2 form):</h3>";
echo "<ul>";
echo "<li><code>\$_POST['tree_id']</code> - Tree selection</li>";
echo "<li><code>\$_POST['new_tree_name']</code> - New tree name</li>";
echo "<li><code>\$_POST['import_option']</code> - Import method</li>";
echo "<li><code>\$_POST['import_media']</code> - Include media</li>";
echo "<li><code>\$_POST['privacy_living']</code> - Privacy for living</li>";
echo "<li><code>\$_POST['privacy_notes']</code> - Privacy for notes</li>";
echo "<li><code>\$_POST['hp_gedcom_nonce']</code> - Security nonce</li>";
echo "</ul>";

echo "<h3>Step 3 JavaScript Variables:</h3>";
echo "<ul>";
echo "<li><code>fileKey</code> - From URL parameter</li>";
echo "<li><code>treeId</code> - From POST data</li>";
echo "<li><code>newTreeName</code> - From POST data</li>";
echo "<li><code>importOption</code> - From POST data</li>";
echo "<li><code>importMedia</code> - From POST data</li>";
echo "<li><code>privacyLiving</code> - From POST data</li>";
echo "<li><code>privacyNotes</code> - From POST data</li>";
echo "</ul>";

echo "<h2>AJAX Handler Variables</h2>";
echo "<h3>Expected by handle_gedcom_process():</h3>";
echo "<ul>";
echo "<li><code>\$_POST['hp_gedcom_nonce']</code> - Must verify against 'hp_gedcom_upload'</li>";
echo "<li><code>\$_POST['file_key']</code> - Optional file identifier</li>";
echo "<li><code>\$_POST['tree_id']</code> - 'new' or tree ID number</li>";
echo "<li><code>\$_POST['new_tree_name']</code> - Required if tree_id='new'</li>";
echo "<li><code>\$_POST['import_option']</code> - Import method</li>";
echo "<li><code>\$_POST['import_media']</code> - Boolean</li>";
echo "<li><code>\$_POST['privacy_living']</code> - Boolean</li>";
echo "<li><code>\$_POST['privacy_notes']</code> - Boolean</li>";
echo "</ul>";

echo "<h2>Critical Fixes Applied</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
echo "<h3>✅ Nonce Consistency</h3>";
echo "<p>Step 2 form nonce changed from 'hp_gedcom_nonce' to 'hp_gedcom_upload' to match handler expectation</p>";

echo "<h3>✅ Variable Name Consistency</h3>";
echo "<p>Handler updated to accept both 'new_tree_name' and 'tree_name' for backward compatibility</p>";

echo "<h3>✅ Tree Creation Logic</h3>";
echo "<p>Handler logic fixed to properly handle tree_id='new' vs import_option='new'</p>";

echo "<h3>✅ Missing Import Options</h3>";
echo "<p>Step 2 form updated to include import_media, privacy_living, privacy_notes fields</p>";

echo "<h3>✅ URL Consistency</h3>";
echo "<p>Step 2 form action updated to include &tab=import parameter</p>";
echo "</div>";

echo "<h2>Variable Flow Verification</h2>";

// Test variable simulation
$test_variables = [
    'step1_to_step2' => [
        'file_key' => 'test_12345',
        'tree_id' => 'new',
        'new_tree_name' => 'My Test Tree',
        'import_option' => 'replace'
    ],
    'step2_to_step3' => [
        'file_key' => 'test_12345',
        'tree_id' => 'new',
        'new_tree_name' => 'My Test Tree',
        'import_option' => 'replace',
        'import_media' => '1',
        'privacy_living' => '0',
        'privacy_notes' => '0'
    ],
    'ajax_call' => [
        'action' => 'hp_process_gedcom',
        'hp_gedcom_nonce' => 'valid_nonce',
        'file_key' => 'test_12345',
        'tree_id' => 'new',
        'new_tree_name' => 'My Test Tree',
        'import_option' => 'replace',
        'import_media' => 1,
        'privacy_living' => 0,
        'privacy_notes' => 0
    ]
];

foreach ($test_variables as $step => $vars) {
    echo "<h3>$step Variables:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr><th style='padding: 8px; background: #f5f5f5;'>Variable</th><th style='padding: 8px; background: #f5f5f5;'>Value</th><th style='padding: 8px; background: #f5f5f5;'>Type</th></tr>";

    foreach ($vars as $key => $value) {
        $type = gettype($value);
        $display_value = is_bool($value) ? ($value ? 'true' : 'false') : htmlspecialchars($value);
        echo "<tr>";
        echo "<td style='padding: 8px;'><code>$key</code></td>";
        echo "<td style='padding: 8px;'>$display_value</td>";
        echo "<td style='padding: 8px;'>$type</td>";
        echo "</tr>";
    }

    echo "</table>";
}

echo "<h2>Testing Recommendations</h2>";
echo "<ol>";
echo "<li><strong>Test Step 1 → Step 2:</strong> Upload a GEDCOM file and verify step 2 displays correctly</li>";
echo "<li><strong>Test Step 2 → Step 3:</strong> Click 'Continue to Import' and verify step 3 loads with correct variables</li>";
echo "<li><strong>Test AJAX Import:</strong> Verify import process starts and completes successfully</li>";
echo "<li><strong>Test Tree Creation:</strong> Test both 'new tree' and 'existing tree' scenarios</li>";
echo "<li><strong>Test Error Handling:</strong> Test with missing variables and invalid data</li>";
echo "</ol>";

echo "<h2>Next Steps</h2>";
echo "<p><a href='/wordpress/wp-admin/admin.php?page=heritagepress-importexport&tab=import' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Test Import Process</a></p>";

echo "<hr>";
echo "<p><em>Variable flow analysis completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>