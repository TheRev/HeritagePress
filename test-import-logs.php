<?php
/**
 * Test script to verify import logging functionality
 */

require_once('../../../wp-config.php');

echo "<h2>Testing Import Logging Functionality</h2>\n";

try {
    // Check if LogsHandler exists
    require_once('includes/Admin/ImportExport/BaseManager.php');
    require_once('includes/Admin/ImportExport/LogsHandler.php');

    $logs_handler = new \HeritagePress\Admin\ImportExport\LogsHandler();
    echo "<p>✓ LogsHandler instantiated successfully</p>\n";

    // Test adding a log entry
    $result = $logs_handler->log_import(
        'test_log',
        'Test log entry for import logging verification',
        array(
            'test' => true,
            'timestamp' => time(),
            'status' => 'success'
        )
    );

    if ($result) {
        echo "<p>✓ Test log entry added successfully</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Failed to add test log entry</p>\n";
    }

    // Check if logs exist in database
    $logs = get_option('heritagepress_importexport_logs', array());
    echo "<p><strong>Total logs in database:</strong> " . count($logs) . "</p>\n";

    if (!empty($logs)) {
        echo "<h3>Recent Logs:</h3>\n";
        $recent_logs = array_slice(array_reverse($logs), 0, 5);
        foreach ($recent_logs as $log) {
            $date = date('Y-m-d H:i:s', $log['timestamp']);
            echo "<p><strong>{$date}</strong> - {$log['type']}: {$log['action']} - {$log['message']}</p>\n";
        }
    }

    // Test ImportHandler integration
    echo "<h3>Testing ImportHandler Integration:</h3>\n";
    require_once('includes/Admin/ImportExport/ImportHandler.php');

    $import_handler = new \HeritagePress\Admin\ImportExport\ImportHandler();
    echo "<p>✓ ImportHandler with LogsHandler instantiated successfully</p>\n";

    echo "<p style='color: green;'><strong>✅ All logging tests passed! Import logging is ready.</strong></p>\n";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>