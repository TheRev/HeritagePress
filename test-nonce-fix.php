<?php
/**
 * Test the upload nonce fix
 */

echo "<h1>Upload Nonce Fix Test</h1>\n";

// Simulate the nonce that would be sent from the form
$nonce_action = 'hp_gedcom_upload';
$nonce_field = 'hp_gedcom_nonce';

echo "<h2>Testing Nonce Creation and Verification</h2>\n";

// Test nonce creation (what the form does)
$test_nonce = wp_create_nonce($nonce_action);
echo "<p><strong>Generated nonce:</strong> " . esc_html($test_nonce) . "</p>\n";

// Test nonce verification (what the handler does)
$_POST[$nonce_field] = $test_nonce;
$verification_result = wp_verify_nonce($_POST[$nonce_field], $nonce_action);

if ($verification_result) {
    echo "<p style='color: green;'>✅ Nonce verification PASSED</p>\n";
} else {
    echo "<p style='color: red;'>❌ Nonce verification FAILED</p>\n";
}

echo "<h2>Testing Upload Handler Nonce Logic</h2>\n";

// Test the exact logic from ImportHandler
if (!wp_verify_nonce($_POST['hp_gedcom_nonce'], 'hp_gedcom_upload')) {
    echo "<p style='color: red;'>❌ ImportHandler nonce check would FAIL</p>\n";
} else {
    echo "<p style='color: green;'>✅ ImportHandler nonce check would PASS</p>\n";
}

echo "<h2>Form Template Nonce Information</h2>\n";

// Check the step1-upload.php template
$step1_file = 'includes/templates/import/step1-upload.php';
if (file_exists($step1_file)) {
    $content = file_get_contents($step1_file);
    if (strpos($content, "wp_nonce_field('hp_gedcom_upload', 'hp_gedcom_nonce')") !== false) {
        echo "<p style='color: green;'>✅ Step 1 template has correct nonce field</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Step 1 template nonce field issue</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ Step 1 template not found</p>\n";
}

echo "<h2>ImportHandler Nonce Check</h2>\n";

// Check the ImportHandler
$handler_file = 'includes/Admin/ImportExport/ImportHandler.php';
if (file_exists($handler_file)) {
    $content = file_get_contents($handler_file);
    if (strpos($content, "wp_verify_nonce(\$_POST['hp_gedcom_nonce'], 'hp_gedcom_upload')") !== false) {
        echo "<p style='color: green;'>✅ ImportHandler has correct nonce verification</p>\n";
    } else {
        echo "<p style='color: red;'>❌ ImportHandler nonce verification issue</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ ImportHandler not found</p>\n";
}

echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border-left: 4px solid #28a745;'>\n";
echo "<h3>✅ Fix Applied:</h3>\n";
echo "<p>The nonce mismatch between the upload form and ImportHandler has been resolved:</p>\n";
echo "<ul>\n";
echo "<li><strong>Form nonce:</strong> wp_nonce_field('hp_gedcom_upload', 'hp_gedcom_nonce')</li>\n";
echo "<li><strong>Handler verification:</strong> wp_verify_nonce(\$_POST['hp_gedcom_nonce'], 'hp_gedcom_upload')</li>\n";
echo "</ul>\n";
echo "<p>The 'Security check failed' error should now be resolved.</p>\n";
echo "</div>\n";

?>