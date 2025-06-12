<?php
/**
 * Debug Step 3 File Key Issue
 */

// Include WordPress
require_once('c:/MAMP/htdocs/wordpress/wp-config.php');

echo "<h1>Step 3 File Key Debug</h1>\n";

echo "<h2>Current REQUEST Data:</h2>\n";
echo "<h3>GET Parameters:</h3>\n";
echo "<pre>" . print_r($_GET, true) . "</pre>\n";

echo "<h3>POST Parameters:</h3>\n";
echo "<pre>" . print_r($_POST, true) . "</pre>\n";

echo "<h3>REQUEST_URI:</h3>\n";
echo "<p>" . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</p>\n";

echo "<h3>QUERY_STRING:</h3>\n";
echo "<p>" . ($_SERVER['QUERY_STRING'] ?? 'Not set') . "</p>\n";

// Simulate the Step 3 file key extraction
$file_key = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : '';

echo "<h3>Extracted File Key:</h3>\n";
echo "<p>File Key: '<strong>" . htmlspecialchars($file_key) . "</strong>' (Length: " . strlen($file_key) . ")</p>\n";

if (empty($file_key)) {
    echo "<p style='color: red;'>❌ File key is empty - this is the problem!</p>\n";

    echo "<h3>Possible Solutions:</h3>\n";
    echo "<ul>\n";
    echo "<li>1. Check if the URL contains the 'file' parameter when coming from Step 2</li>\n";
    echo "<li>2. Make sure the form action in Step 2 includes the file parameter</li>\n";
    echo "<li>3. Consider passing file_key in POST data as a hidden field</li>\n";
    echo "</ul>\n";
} else {
    echo "<p style='color: green;'>✅ File key found successfully!</p>\n";

    // Check if corresponding file exists
    $upload_info = wp_upload_dir();
    $gedcom_dir = $upload_info['basedir'] . '/heritagepress/gedcom/';
    $gedcom_file = $gedcom_dir . $file_key . '.ged';

    echo "<h3>File Path Check:</h3>\n";
    echo "<p>Expected file: <code>$gedcom_file</code></p>\n";

    if (file_exists($gedcom_file)) {
        echo "<p style='color: green;'>✅ GEDCOM file exists</p>\n";
        echo "<p>File size: " . filesize($gedcom_file) . " bytes</p>\n";
    } else {
        echo "<p style='color: red;'>❌ GEDCOM file does not exist</p>\n";
    }
}

echo "<h2>Test URLs</h2>\n";
echo "<p>Try these test URLs to see if file key is passed correctly:</p>\n";
echo "<ul>\n";
echo "<li><a href='?file=test123'>Test with file=test123</a></li>\n";
echo "<li><a href='?page=heritagepress-importexport&step=3&file=test456'>Test with step 3 params</a></li>\n";
echo "</ul>\n";

// Show recent GEDCOM files for reference
echo "<h2>Recent GEDCOM Files</h2>\n";
$upload_info = wp_upload_dir();
$gedcom_dir = $upload_info['basedir'] . '/heritagepress/gedcom/';

if (is_dir($gedcom_dir)) {
    $files = glob($gedcom_dir . '*.ged');
    if ($files) {
        echo "<ul>\n";
        foreach ($files as $file) {
            $basename = basename($file, '.ged');
            $mod_time = date('Y-m-d H:i:s', filemtime($file));
            echo "<li><strong>$basename</strong> (modified: $mod_time)</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p>No GEDCOM files found in $gedcom_dir</p>\n";
    }
} else {
    echo "<p style='color: red;'>GEDCOM directory does not exist: $gedcom_dir</p>\n";
}
?>