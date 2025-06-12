<?php
/**
 * Direct test of ImportExportManager with full error reporting
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Include WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';

echo "<h1>ImportExportManager Error Test</h1>\n";

echo "<h2>PHP Configuration</h2>\n";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>\n";
echo "<p><strong>Error Reporting:</strong> " . error_reporting() . "</p>\n";
echo "<p><strong>Display Errors:</strong> " . ini_get('display_errors') . "</p>\n";

echo "<h2>WordPress Status</h2>\n";
echo "<p><strong>WordPress loaded:</strong> " . (defined('ABSPATH') ? 'Yes' : 'No') . "</p>\n";
echo "<p><strong>Database connected:</strong> " . (isset($wpdb) ? 'Yes' : 'No') . "</p>\n";

echo "<h2>Class Loading Test</h2>\n";

// Try to load the ImportExportManager manually
$class_file = __DIR__ . '/includes/Admin/ImportExportManager.php';
echo "<p><strong>Class file exists:</strong> " . (file_exists($class_file) ? 'Yes' : 'No') . "</p>\n";

if (file_exists($class_file)) {
    echo "<p>Loading class file...</p>\n";

    try {
        require_once $class_file;
        echo "<p style='color: green;'>✓ Class file loaded successfully</p>\n";

        if (class_exists('HeritagePress\Admin\ImportExportManager')) {
            echo "<p style='color: green;'>✓ ImportExportManager class found</p>\n";

            // Try to instantiate
            echo "<p>Creating ImportExportManager instance...</p>\n";
            $manager = new HeritagePress\Admin\ImportExportManager();
            echo "<p style='color: green;'>✓ ImportExportManager instance created</p>\n";

            // Test method call
            echo "<p>Testing render_page method...</p>\n";

            // Capture output
            ob_start();
            $manager->render_page();
            $output = ob_get_contents();
            ob_end_clean();

            if (!empty($output)) {
                echo "<p style='color: green;'>✓ render_page produced output (" . strlen($output) . " chars)</p>\n";

                // Show first part of output
                echo "<h3>Output Preview:</h3>\n";
                echo "<div style='border: 1px solid #ddd; padding: 10px; max-height: 300px; overflow: auto;'>\n";
                echo htmlspecialchars(substr($output, 0, 1000));
                if (strlen($output) > 1000) {
                    echo "\n\n... [truncated, showing first 1000 characters]";
                }
                echo "</div>\n";
            } else {
                echo "<p style='color: red;'>✗ render_page produced no output</p>\n";
            }

        } else {
            echo "<p style='color: red;'>✗ ImportExportManager class not found after loading</p>\n";
        }

    } catch (Throwable $e) {
        echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>\n";
        echo "<p><strong>File:</strong> " . $e->getFile() . " (line " . $e->getLine() . ")</p>\n";
        echo "<h3>Stack Trace:</h3>\n";
        echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
    }
} else {
    echo "<p style='color: red;'>✗ Class file not found: $class_file</p>\n";
}

echo "<h2>Test Complete</h2>\n";
echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
