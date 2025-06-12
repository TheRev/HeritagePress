<?php
/**
 * Simple verification that logging works - can be accessed via browser
 */

// Only allow access if user is logged in as admin
if (!is_admin() || !current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h2>🔍 Import Logging System Verification</h2>\n";

// Clear any previous logs first (optional - for testing only)
// delete_option('heritagepress_importexport_logs');

try {
    // Test LogsHandler
    require_once(dirname(__FILE__) . '/includes/Admin/ImportExport/LogsHandler.php');
    $logs_handler = new \HeritagePress\Admin\ImportExport\LogsHandler();

    // Add a test log entry
    $result = $logs_handler->log_import(
        'system_test',
        'Import logging system test - system is working correctly',
        array(
            'test_type' => 'system_verification',
            'timestamp' => current_time('timestamp'),
            'status' => 'success',
            'user_id' => get_current_user_id()
        )
    );

    if ($result) {
        echo "<p style='color: green;'>✅ <strong>Success!</strong> Test log entry created successfully</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Failed to create test log entry</p>\n";
    }

    // Show current log count
    $logs = get_option('heritagepress_importexport_logs', array());
    echo "<p><strong>Total logs in system:</strong> " . count($logs) . "</p>\n";

    if (!empty($logs)) {
        echo "<p style='color: green;'>✅ Logs are being stored in the database</p>\n";
        echo "<p><a href='" . admin_url('admin.php?page=heritagepress-import-export&tab=logs') . "' target='_blank'>➡️ <strong>View Logs Tab</strong></a></p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ No logs found in database yet</p>\n";
    }

    echo "<h3>✅ Ready for Real Import Testing</h3>\n";
    echo "<p>The import logging system is ready. Now perform a GEDCOM import to see real log entries:</p>\n";
    echo "<ol>\n";
    echo "<li><a href='" . admin_url('admin.php?page=heritagepress-import-export&tab=import') . "' target='_blank'>Go to Import Tab</a></li>\n";
    echo "<li>Upload a GEDCOM file</li>\n";
    echo "<li>Complete the import process</li>\n";
    echo "<li><a href='" . admin_url('admin.php?page=heritagepress-import-export&tab=logs') . "' target='_blank'>Check the Logs Tab</a> to see real import activity</li>\n";
    echo "</ol>\n";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>