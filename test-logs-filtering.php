<?php
/**
 * Test logs filtering functionality
 */

require_once('../../../wp-config.php');

echo "<h2>Testing Logs Filtering Functionality</h2>\n";

try {
    // Check current log count
    $all_logs = get_option('heritagepress_importexport_logs', array());
    echo "<h3>Current Log Status:</h3>\n";
    echo "<p><strong>Total logs in database:</strong> " . count($all_logs) . "</p>\n";

    if (count($all_logs) > 0) {
        // Show breakdown by type
        $types = array();
        $statuses = array();
        foreach ($all_logs as $log) {
            $type = $log['type'] ?? 'unknown';
            $status = isset($log['details']['status']) ? $log['details']['status'] : 'unknown';

            $types[$type] = ($types[$type] ?? 0) + 1;
            $statuses[$status] = ($statuses[$status] ?? 0) + 1;
        }

        echo "<p><strong>By Type:</strong></p><ul>\n";
        foreach ($types as $type => $count) {
            echo "<li>{$type}: {$count}</li>\n";
        }
        echo "</ul>\n";

        echo "<p><strong>By Status:</strong></p><ul>\n";
        foreach ($statuses as $status => $count) {
            echo "<li>{$status}: {$count}</li>\n";
        }
        echo "</ul>\n";

        // Test LogsHandler filtering
        echo "<h3>Testing LogsHandler Filtering:</h3>\n";
        require_once('includes/Admin/ImportExport/BaseManager.php');
        require_once('includes/Admin/ImportExport/LogsHandler.php');

        $logs_handler = new \HeritagePress\Admin\ImportExport\LogsHandler();

        // Test filtering by type
        $import_logs = $logs_handler->get_logs(array('type' => 'import'));
        echo "<p><strong>Import logs:</strong> " . count($import_logs) . "</p>\n";

        // Test filtering by status
        $success_logs = $logs_handler->get_logs(array('status' => 'success'));
        echo "<p><strong>Success logs:</strong> " . count($success_logs) . "</p>\n";

        $error_logs = $logs_handler->get_logs(array('status' => 'error'));
        echo "<p><strong>Error logs:</strong> " . count($error_logs) . "</p>\n";

        // Test combined filtering
        $import_success_logs = $logs_handler->get_logs(array('type' => 'import', 'status' => 'success'));
        echo "<p><strong>Import + Success logs:</strong> " . count($import_success_logs) . "</p>\n";

    } else {
        echo "<p style='color: orange;'>⚠️ No logs found in database</p>\n";
        echo "<p>Try performing some import operations to generate logs.</p>\n";
    }

    // Check if we're approaching the 100 limit
    if (count($all_logs) > 80) {
        echo "<h3>⚠️ Log Limit Warning:</h3>\n";
        echo "<p style='color: orange;'>You have " . count($all_logs) . " logs. The system automatically keeps only the 100 most recent logs.</p>\n";
        if (count($all_logs) >= 100) {
            echo "<p style='color: red;'><strong>Limit reached!</strong> Older logs are being automatically removed.</p>\n";
        }
    }

    echo "<h3>✅ Log filtering system is ready for testing</h3>\n";
    echo "<p><a href='" . admin_url('admin.php?page=heritagepress-import-export&tab=logs') . "' target='_blank'>➡️ <strong>Test Filters in Logs Tab</strong></a></p>\n";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>