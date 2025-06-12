<?php
// Test AJAX endpoint directly
require_once('../../../wp-config.php');

echo "<h2>Testing AJAX Upload Endpoint</h2>";

// Simulate AJAX POST data
$_POST['action'] = 'hp_upload_gedcom';
$_POST['hp_gedcom_nonce'] = wp_create_nonce('hp_gedcom_upload');

// Create a simple test file upload simulation
$test_file_content = "0 HEAD\n1 SOUR Test\n0 TRLR";
$temp_file = tmpfile();
fwrite($temp_file, $test_file_content);
$temp_file_path = stream_get_meta_data($temp_file)['uri'];

$_FILES['gedcom_file'] = array(
    'name' => 'test.ged',
    'type' => 'text/plain',
    'tmp_name' => $temp_file_path,
    'error' => UPLOAD_ERR_OK,
    'size' => strlen($test_file_content)
);

echo "<h3>POST Data:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>FILES Data:</h3>";
echo "<pre>";
print_r($_FILES);
echo "</pre>";

// Try to call the AJAX handler directly
if (class_exists('HeritagePress\Admin\ImportExport\ImportHandler')) {
    try {
        $handler = new HeritagePress\Admin\ImportExport\ImportHandler();
        echo "<p>✓ ImportHandler created successfully</p>";

        // Check if the method exists
        if (method_exists($handler, 'handle_gedcom_upload')) {
            echo "<p>✓ handle_gedcom_upload method exists</p>";

            // Try to call it (capture output)
            ob_start();
            $handler->handle_gedcom_upload();
            $output = ob_get_clean();

            echo "<h3>Handler Output:</h3>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
        } else {
            echo "<p>✗ handle_gedcom_upload method does not exist</p>";
        }
    } catch (Exception $e) {
        echo "<p>✗ Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>✗ ImportHandler class not found</p>";
}

// Test direct AJAX URL
echo "<h3>AJAX URL Test:</h3>";
$ajax_url = admin_url('admin-ajax.php');
echo "<p>AJAX URL: $ajax_url</p>";

// Test with curl
$post_data = array(
    'action' => 'hp_upload_gedcom',
    'hp_gedcom_nonce' => wp_create_nonce('hp_gedcom_upload')
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ajax_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>CURL Test to AJAX URL:</h3>";
echo "<p>HTTP Code: $http_code</p>";
echo "<p>Response: " . htmlspecialchars(substr($response, 0, 500)) . "...</p>";

fclose($temp_file);
?>