<?php
/**
 * Debug Autoloader Path Generation
 */

echo "<h1>Autoloader Debug Test</h1>\n";

// Test the path generation logic
$class_name = 'HeritagePress\\Services\\GedcomService';

echo "<p>Testing class: $class_name</p>\n";

// Remove namespace prefix
$class_path = str_replace('HeritagePress\\', '', $class_name);
echo "<p>After removing namespace prefix: $class_path</p>\n";

// Convert namespace separator to directory separator
$class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_path);
echo "<p>After converting separators: $class_path</p>\n";

// Build file path
$base_dir = __DIR__ . DIRECTORY_SEPARATOR . 'includes';
$file = $base_dir . DIRECTORY_SEPARATOR . $class_path . '.php';
echo "<p>Expected file path: $file</p>\n";

// Check if file exists
if (file_exists($file)) {
    echo "<p style='color: green;'>✓ File exists!</p>\n";

    // Try to include it
    try {
        require_once $file;
        echo "<p style='color: green;'>✓ File included successfully</p>\n";

        // Try to instantiate
        if (class_exists($class_name)) {
            echo "<p style='color: green;'>✓ Class exists after include</p>\n";

            $instance = new $class_name();
            echo "<p style='color: green;'>✓ Class instantiated successfully</p>\n";
        } else {
            echo "<p style='color: red;'>✗ Class does not exist after include</p>\n";
        }

    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error including file: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
} else {
    echo "<p style='color: red;'>✗ File does not exist!</p>\n";

    // List files in the Services directory
    $services_dir = $base_dir . DIRECTORY_SEPARATOR . 'Services';
    echo "<p>Checking Services directory: $services_dir</p>\n";

    if (is_dir($services_dir)) {
        $files = scandir($services_dir);
        echo "<p>Files in Services directory:</p><ul>\n";
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "<li>$file</li>\n";
            }
        }
        echo "</ul>\n";
    } else {
        echo "<p style='color: red;'>Services directory does not exist!</p>\n";
    }
}
?>