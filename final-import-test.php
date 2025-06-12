<?php
/**
 * Final comprehensive test of the Import GEDCOM page
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';

echo "<h1>Final Import GEDCOM Test</h1>\n";
echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>\n";

echo "<h2>1. Class Loading Test</h2>\n";
try {
    $manager = new HeritagePress\Admin\ImportExportManager();
    echo "<p style='color: green;'>✓ ImportExportManager created successfully</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Failed to create ImportExportManager: " . $e->getMessage() . "</p>\n";
    exit();
}

echo "<h2>2. Database Trees Test</h2>\n";
try {
    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';
    $trees = $wpdb->get_results("SELECT * FROM $trees_table ORDER BY title ASC");
    echo "<p><strong>Trees in database:</strong> " . count($trees) . "</p>\n";

    if (!empty($trees)) {
        foreach ($trees as $tree) {
            echo "<p>• ID: {$tree->id}, Title: " . esc_html($tree->title) . "</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>\n";
}

echo "<h2>3. Template Rendering Test</h2>\n";
try {
    // Mock the necessary variables
    $_GET['tab'] = 'import';  // Force import tab
    $_GET['step'] = 1;        // Force step 1

    ob_start();
    $manager->render_page();
    $output = ob_get_clean();

    if (!empty($output)) {
        echo "<p style='color: green;'>✓ render_page produced output (" . strlen($output) . " characters)</p>\n";

        // Check for key elements
        $checks = array(
            'Step 1: Select GEDCOM File' => 'Step 1 header found',
            'hp-gedcom-upload-form' => 'Upload form found',
            'hp-gedcom-tree' => 'Tree selector found',
            'Destination Tree' => 'Tree selection label found'
        );

        foreach ($checks as $needle => $description) {
            if (strpos($output, $needle) !== false) {
                echo "<p style='color: green;'>✓ $description</p>\n";
            } else {
                echo "<p style='color: orange;'>⚠️ $description - not found</p>\n";
            }
        }

        // Show a sample of the output
        echo "<h3>Output Sample (first 800 characters):</h3>\n";
        echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9; max-height: 400px; overflow: auto;'>\n";
        echo "<pre>" . htmlspecialchars(substr($output, 0, 800)) . "</pre>\n";
        echo "</div>\n";

    } else {
        echo "<p style='color: red;'>✗ render_page produced no output</p>\n";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error in template rendering: " . $e->getMessage() . "</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>4. Direct Template Test</h2>\n";
try {
    // Test the step1 template directly
    $trees = array(
        (object) array('id' => 1, 'title' => 'Test Tree 1'),
        (object) array('id' => 2, 'title' => 'Test Tree 2')
    );

    echo "<p>Testing with " . count($trees) . " mock trees</p>\n";

    ob_start();
    include __DIR__ . '/includes/templates/import/step1-upload.php';
    $template_output = ob_get_clean();

    if (!empty($template_output)) {
        echo "<p style='color: green;'>✓ Step1 template rendered successfully (" . strlen($template_output) . " characters)</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Step1 template produced no output</p>\n";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error in direct template test: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Test Results Summary</h2>\n";
echo "<p>If you see this message, the Import GEDCOM page should be working.</p>\n";
echo "<p><strong>Next step:</strong> Visit the WordPress admin Import/Export page to verify.</p>\n";
echo "<p><strong>URL:</strong> <a href='/wordpress/wp-admin/admin.php?page=heritagepress-importexport&tab=import' target='_blank'>Import GEDCOM Page</a></p>\n";
