<?php
/**
 * Simple test to replicate the Import GEDCOM step 1 page
 */

// Include WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';

echo "<h1>Import GEDCOM Test</h1>\n";

// Test direct template include
echo "<h2>Direct Template Test</h2>\n";

// Simulate what ImportExportManager::render_import_tab() does
try {
    // Mock the trees array
    $trees = array(
        (object) array('id' => 1, 'title' => 'My Family Tree'),
        (object) array('id' => 2, 'title' => 'Test Tree')
    );

    echo "<p>Available trees: " . count($trees) . "</p>\n";

    // Mock other variables
    $step = 1;
    $file_key = '';
    $tree_id = '';
    $new_tree_name = '';
    $import_option = 'replace';
    $selected_tree = null;
    $selected_tree_name = '';

    echo "<p>About to include import.php template...</p>\n";

    // Include the import template
    $template_path = __DIR__ . '/includes/templates/import/import.php';
    if (file_exists($template_path)) {
        echo "<p style='color: green;'>✓ Template file exists: $template_path</p>\n";

        ob_start();
        include $template_path;
        $output = ob_get_clean();

        echo "<p><strong>Template output length:</strong> " . strlen($output) . " characters</p>\n";

        if (!empty($output)) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>\n";
            echo "<h3>Template Output:</h3>\n";
            echo $output;
            echo "</div>\n";
        } else {
            echo "<p style='color: red;'>✗ Template produced no output</p>\n";
        }
    } else {
        echo "<p style='color: red;'>✗ Template file not found: $template_path</p>\n";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
} catch (Error $e) {
    echo "<p style='color: red;'>✗ Fatal Error: " . $e->getMessage() . "</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
} catch (ParseError $e) {
    echo "<p style='color: red;'>✗ Parse Error: " . $e->getMessage() . "</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>Test Complete</h2>\n";
