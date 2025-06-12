<?php
/**
 * Debug script to test Import GEDCOM page functionality
 */

// Include WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';

echo "<h1>Debug Import GEDCOM Page</h1>\n";

// Test 1: Check if ImportExportManager class exists
echo "<h2>1. Class Loading Test</h2>\n";
try {
    if (class_exists('HeritagePress\Admin\ImportExportManager')) {
        echo "<p style='color: green;'>✓ ImportExportManager class found</p>\n";
    } else {
        echo "<p style='color: red;'>✗ ImportExportManager class not found</p>\n";
        // Try to load it manually
        require_once __DIR__ . '/includes/Admin/ImportExportManager.php';
        if (class_exists('HeritagePress\Admin\ImportExportManager')) {
            echo "<p style='color: orange;'>⚠️ ImportExportManager loaded manually</p>\n";
        } else {
            echo "<p style='color: red;'>✗ ImportExportManager still not found after manual load</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error loading ImportExportManager: " . $e->getMessage() . "</p>\n";
}

// Test 2: Test database connection and trees query
echo "<h2>2. Database Connection Test</h2>\n";
global $wpdb;
echo "<p><strong>Database:</strong> " . DB_NAME . "</p>\n";
echo "<p><strong>Prefix:</strong> " . $wpdb->prefix . "</p>\n";

// Test simple query
try {
    $trees_table = $wpdb->prefix . 'hp_trees';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$trees_table'") == $trees_table;

    if ($table_exists) {
        echo "<p style='color: green;'>✓ hp_trees table exists</p>\n";

        // Try to get trees
        $trees = $wpdb->get_results("SELECT * FROM $trees_table ORDER BY title ASC");
        echo "<p><strong>Trees found:</strong> " . count($trees) . "</p>\n";

        if (!empty($trees)) {
            echo "<ul>\n";
            foreach ($trees as $tree) {
                echo "<li>ID: {$tree->id}, Title: " . esc_html($tree->title) . "</li>\n";
            }
            echo "</ul>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ No trees found in database</p>\n";
        }
    } else {
        echo "<p style='color: red;'>✗ hp_trees table does not exist</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>\n";
}

// Test 3: Test ImportExportManager instantiation
echo "<h2>3. ImportExportManager Instantiation Test</h2>\n";
try {
    $manager = new HeritagePress\Admin\ImportExportManager();
    echo "<p style='color: green;'>✓ ImportExportManager created successfully</p>\n";

    // Test get_trees method directly if it's accessible
    // Since get_trees is private, we'll test the render_page method that should work
    echo "<p>Testing render_page method...</p>\n";
    ob_start();
    $manager->render_page();
    $output = ob_get_clean();

    if (!empty($output)) {
        echo "<p style='color: green;'>✓ render_page method produced output</p>\n";
        echo "<p><strong>Output length:</strong> " . strlen($output) . " characters</p>\n";

        // Check if output contains expected elements
        if (strpos($output, 'Step 1: Select GEDCOM File') !== false) {
            echo "<p style='color: green;'>✓ Step 1 content found in output</p>\n";
        } else {
            echo "<p style='color: red;'>✗ Step 1 content not found in output</p>\n";
        }

        // Show first 500 characters of output
        echo "<h3>First 500 characters of output:</h3>\n";
        echo "<pre>" . esc_html(substr($output, 0, 500)) . "</pre>\n";
    } else {
        echo "<p style='color: red;'>✗ render_page method produced no output</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error creating ImportExportManager: " . $e->getMessage() . "</p>\n";
    echo "<p><strong>Stack trace:</strong></p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
} catch (Error $e) {
    echo "<p style='color: red;'>✗ Fatal error creating ImportExportManager: " . $e->getMessage() . "</p>\n";
    echo "<p><strong>Stack trace:</strong></p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>Debug Complete</h2>\n";
echo "<p><strong>Current time:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
