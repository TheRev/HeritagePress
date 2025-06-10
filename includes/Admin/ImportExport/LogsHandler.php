<?php
/**
 * Logs Handler for HeritagePress
 *
 * @package HeritagePress
 * @subpackage Admin
 */

namespace HeritagePress\Admin\ImportExport;

/**
 * Class LogsHandler
 *
 * Handles import/export logs operations
 */
class LogsHandler extends BaseManager
{
    /**
     * Log types
     */
    const LOG_TYPE_IMPORT = 'import';
    const LOG_TYPE_EXPORT = 'export';
    const LOG_TYPE_VALIDATION = 'validation';
    const LOG_TYPE_ERROR = 'error';
    const LOG_TYPE_SETTINGS = 'settings';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Add a log entry
     *
     * @param string $type Log type
     * @param string $action Action performed
     * @param string $message Log message
     * @param array $details Additional details
     * @return bool Success status
     */
    public function add_log($type, $action, $message, $details = array())
    {
        // Get current logs
        $logs = $this->get_logs();

        // Add new log entry
        $logs[] = array(
            'id' => $this->generate_log_id(),
            'timestamp' => current_time('timestamp'),
            'type' => $type,
            'action' => $action,
            'message' => $message,
            'details' => $details,
            'user_id' => get_current_user_id(),
            'user_name' => wp_get_current_user()->display_name
        );

        // Trim logs to keep only the last 100 entries
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }

        // Update logs in database
        return update_option('heritagepress_importexport_logs', $logs);
    }

    /**
     * Get all logs
     *
     * @param array $filters Optional filters
     * @return array Log entries
     */
    public function get_logs($filters = array())
    {
        $logs = get_option('heritagepress_importexport_logs', array());

        if (empty($filters)) {
            return $logs;
        }

        return $this->filter_logs($logs, $filters);
    }

    /**
     * Filter logs based on criteria
     *
     * @param array $logs Log entries
     * @param array $filters Filter criteria
     * @return array Filtered logs
     */
    private function filter_logs($logs, $filters)
    {
        return array_filter($logs, function ($log) use ($filters) {
            // Filter by type
            if (isset($filters['type']) && $log['type'] !== $filters['type']) {
                return false;
            }

            // Filter by date range
            if (isset($filters['date_from'])) {
                $date_from = strtotime($filters['date_from']);
                if ($log['timestamp'] < $date_from) {
                    return false;
                }
            }

            if (isset($filters['date_to'])) {
                $date_to = strtotime($filters['date_to'] . ' 23:59:59');
                if ($log['timestamp'] > $date_to) {
                    return false;
                }
            }

            // Filter by user
            if (isset($filters['user_id']) && $log['user_id'] != $filters['user_id']) {
                return false;
            }

            // Filter by search query
            if (isset($filters['search'])) {
                $search = strtolower($filters['search']);
                $searchable = strtolower($log['message'] . ' ' . $log['action']);
                if (strpos($searchable, $search) === false) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Get log statistics
     *
     * @param array $filters Optional filters
     * @return array Statistics
     */
    public function get_log_stats($filters = array())
    {
        $logs = $this->get_logs($filters);

        $stats = array(
            'total' => count($logs),
            'by_type' => array(),
            'by_user' => array(),
            'recent' => 0
        );

        // Count by type
        foreach ($logs as $log) {
            $type = $log['type'];
            if (!isset($stats['by_type'][$type])) {
                $stats['by_type'][$type] = 0;
            }
            $stats['by_type'][$type]++;

            // Count by user
            $user_name = $log['user_name'] ?? 'Unknown';
            if (!isset($stats['by_user'][$user_name])) {
                $stats['by_user'][$user_name] = 0;
            }
            $stats['by_user'][$user_name]++;

            // Count recent (last 24 hours)
            if ($log['timestamp'] > (current_time('timestamp') - DAY_IN_SECONDS)) {
                $stats['recent']++;
            }
        }

        return $stats;
    }

    /**
     * Clear logs
     *
     * @param array $filters Optional filters to clear specific logs
     * @return bool Success status
     */
    public function clear_logs($filters = array())
    {
        if (empty($filters)) {
            // Clear all logs
            $success = delete_option('heritagepress_importexport_logs');
        } else {
            // Clear filtered logs
            $all_logs = $this->get_logs();
            $filtered_logs = $this->filter_logs($all_logs, $filters);

            // Remove filtered logs from all logs
            $remaining_logs = array_diff_key($all_logs, $filtered_logs);
            $success = update_option('heritagepress_importexport_logs', array_values($remaining_logs));
        }

        if ($success) {
            $this->add_log(self::LOG_TYPE_SETTINGS, 'logs_cleared', 'Import/Export logs cleared', $filters);
        }

        return $success;
    }

    /**
     * Export logs to file
     *
     * @param string $format Export format ('csv', 'json', 'txt')
     * @param array $filters Optional filters
     * @return array Export result with file path
     */
    public function export_logs($format = 'csv', $filters = array())
    {
        $logs = $this->get_logs($filters);

        if (empty($logs)) {
            throw new \Exception(__('No logs to export', 'heritagepress'));
        }

        // Prepare export directory
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/heritagepress/logs';

        if (!file_exists($export_dir)) {
            if (!mkdir($export_dir, 0755, true)) {
                throw new \Exception(__('Failed to create logs export directory', 'heritagepress'));
            }
            file_put_contents($export_dir . '/index.php', '<?php // Silence is golden');
        }

        // Generate filename
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "heritagepress_logs_{$timestamp}.{$format}";
        $filepath = $export_dir . '/' . $filename;

        // Export based on format
        switch ($format) {
            case 'csv':
                $this->export_logs_csv($logs, $filepath);
                break;
            case 'json':
                $this->export_logs_json($logs, $filepath);
                break;
            case 'txt':
                $this->export_logs_txt($logs, $filepath);
                break;
            default:
                throw new \Exception(__('Unsupported export format', 'heritagepress'));
        }

        // Log the export
        $this->add_log(self::LOG_TYPE_EXPORT, 'logs_exported', "Logs exported to {$format} format", array(
            'filename' => $filename,
            'count' => count($logs),
            'filters' => $filters
        ));

        return array(
            'filename' => $filename,
            'filepath' => $filepath,
            'download_url' => $upload_dir['baseurl'] . '/heritagepress/logs/' . $filename,
            'count' => count($logs)
        );
    }

    /**
     * Export logs to CSV format
     *
     * @param array $logs Log entries
     * @param string $filepath Output file path
     */
    private function export_logs_csv($logs, $filepath)
    {
        $file = fopen($filepath, 'w');

        // Write header
        fputcsv($file, array('Date', 'Type', 'Action', 'Message', 'User', 'Details'));

        // Write log entries
        foreach ($logs as $log) {
            fputcsv($file, array(
                date('Y-m-d H:i:s', $log['timestamp']),
                $log['type'],
                $log['action'],
                $log['message'],
                $log['user_name'] ?? 'Unknown',
                is_array($log['details']) ? json_encode($log['details']) : $log['details']
            ));
        }

        fclose($file);
    }

    /**
     * Export logs to JSON format
     *
     * @param array $logs Log entries
     * @param string $filepath Output file path
     */
    private function export_logs_json($logs, $filepath)
    {
        // Add human-readable timestamps
        $formatted_logs = array_map(function ($log) {
            $log['formatted_date'] = date('Y-m-d H:i:s', $log['timestamp']);
            return $log;
        }, $logs);

        $json_data = array(
            'exported_at' => date('Y-m-d H:i:s'),
            'total_logs' => count($logs),
            'logs' => $formatted_logs
        );

        file_put_contents($filepath, json_encode($json_data, JSON_PRETTY_PRINT));
    }

    /**
     * Export logs to text format
     *
     * @param array $logs Log entries
     * @param string $filepath Output file path
     */
    private function export_logs_txt($logs, $filepath)
    {
        $content = "HeritagePress Import/Export Logs\n";
        $content .= "Exported: " . date('Y-m-d H:i:s') . "\n";
        $content .= str_repeat("=", 50) . "\n\n";

        foreach ($logs as $log) {
            $content .= "[" . date('Y-m-d H:i:s', $log['timestamp']) . "] ";
            $content .= strtoupper($log['type']) . " - ";
            $content .= $log['action'] . ": ";
            $content .= $log['message'];
            $content .= " (User: " . ($log['user_name'] ?? 'Unknown') . ")";

            if (!empty($log['details'])) {
                $content .= "\n  Details: " . (is_array($log['details']) ? json_encode($log['details']) : $log['details']);
            }

            $content .= "\n\n";
        }

        file_put_contents($filepath, $content);
    }

    /**
     * Generate unique log ID
     *
     * @return string Unique log ID
     */
    private function generate_log_id()
    {
        return uniqid('log_', true);
    }

    /**
     * Log import activity
     *
     * @param string $action Import action
     * @param string $message Log message
     * @param array $details Additional details
     */
    public function log_import($action, $message, $details = array())
    {
        $this->add_log(self::LOG_TYPE_IMPORT, $action, $message, $details);
    }

    /**
     * Log export activity
     *
     * @param string $action Export action
     * @param string $message Log message
     * @param array $details Additional details
     */
    public function log_export($action, $message, $details = array())
    {
        $this->add_log(self::LOG_TYPE_EXPORT, $action, $message, $details);
    }

    /**
     * Log validation activity
     *
     * @param string $action Validation action
     * @param string $message Log message
     * @param array $details Additional details
     */
    public function log_validation($action, $message, $details = array())
    {
        $this->add_log(self::LOG_TYPE_VALIDATION, $action, $message, $details);
    }

    /**
     * Log error
     *
     * @param string $action Action that caused error
     * @param string $message Error message
     * @param array $details Error details
     */
    public function log_error($action, $message, $details = array())
    {
        $this->add_log(self::LOG_TYPE_ERROR, $action, $message, $details);
    }
}
