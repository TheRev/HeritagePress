<?php
// Debug script to test AJAX handler registration
require_once('../../../wp-config.php');

echo "<h2>Testing AJAX Handler Registration</h2>";

// Check if ImportHandler class exists
if (class_exists('HeritagePress\Admin\ImportExport\ImportHandler')) {
    echo "<p>✓ ImportHandler class exists</p>";
} else {
    echo "<p>✗ ImportHandler class NOT found</p>";
}

// Check registered AJAX actions
global $wp_filter;

$ajax_actions_to_check = [
    'wp_ajax_hp_upload_gedcom',
    'wp_ajax_hp_process_gedcom',
    'wp_ajax_hp_import_progress'
];

echo "<h3>Registered AJAX Actions:</h3>";
foreach ($ajax_actions_to_check as $action) {
    if (isset($wp_filter[$action])) {
        echo "<p>✓ $action is registered</p>";
        echo "<pre>";
        foreach ($wp_filter[$action]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function'])) {
                    $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                    $method = $callback['function'][1];
                    echo "  - Priority $priority: $class::$method\n";
                } else {
                    echo "  - Priority $priority: {$callback['function']}\n";
                }
            }
        }
        echo "</pre>";
    } else {
        echo "<p>✗ $action is NOT registered</p>";
    }
}

// Test if we can instantiate ImportHandler
echo "<h3>Testing ImportHandler Instantiation:</h3>";
try {
    $handler = new HeritagePress\Admin\ImportExport\ImportHandler();
    echo "<p>✓ ImportHandler instantiated successfully</p>";
} catch (Exception $e) {
    echo "<p>✗ Error instantiating ImportHandler: " . $e->getMessage() . "</p>";
}

// Check if we can access the handler method
if (class_exists('HeritagePress\Admin\ImportExport\ImportHandler')) {
    $reflection = new ReflectionClass('HeritagePress\Admin\ImportExport\ImportHandler');
    if ($reflection->hasMethod('handle_gedcom_upload')) {
        echo "<p>✓ handle_gedcom_upload method exists</p>";
    } else {
        echo "<p>✗ handle_gedcom_upload method NOT found</p>";
    }
}

// Check wp_vars availability
echo "<h3>WordPress AJAX URL:</h3>";
echo "<p>AJAX URL: " . admin_url('admin-ajax.php') . "</p>";
?>