<?php
/**
 * Test Script for Tools Menu Implementation
 * 
 * This script tests that the Tools menu was successfully created and
 * the table management functionality was moved from Import/Export.
 */

// Load WordPress
$wp_load_path = realpath(__DIR__ . '/../../../wp-load.php');
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
    echo "<h1>HeritagePress Tools Menu Test</h1>";
} else {
    die('WordPress not found');
}

echo "<style>
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
</style>";

// Test 1: Check if MenuManager class exists and has the Tools menu
echo "<h2>1. Testing MenuManager Class</h2>";
try {
    require_once(__DIR__ . '/includes/Admin/MenuManager.php');
    echo "<p class='success'>✓ MenuManager class loaded successfully</p>";

    // Create instance to test methods
    $menuManager = new HeritagePress\Admin\MenuManager();
    echo "<p class='success'>✓ MenuManager instantiated successfully</p>";

    // Check if render_tools_page method exists
    if (method_exists($menuManager, 'render_tools_page')) {
        echo "<p class='success'>✓ render_tools_page() method exists</p>";
    } else {
        echo "<p class='error'>✗ render_tools_page() method not found</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>✗ MenuManager test failed: " . $e->getMessage() . "</p>";
}

// Test 2: Check if TableManager class exists and has required methods
echo "<h2>2. Testing TableManager Class</h2>";
try {
    require_once(__DIR__ . '/includes/Admin/TableManager.php');
    echo "<p class='success'>✓ TableManager class loaded successfully</p>";

    // Create instance to test methods
    $tableManager = new HeritagePress\Admin\TableManager();
    echo "<p class='success'>✓ TableManager instantiated successfully</p>";

    // Check required methods
    $required_methods = [
        'render_page',
        'handle_get_table_structure',
        'handle_clear_table',
        'handle_delete_table',
        'handle_rebuild_tables',
        'handle_optimize_tables',
        'handle_clear_all_tables',
        'handle_delete_all_tables'
    ];

    foreach ($required_methods as $method) {
        if (method_exists($tableManager, $method)) {
            echo "<p class='success'>✓ {$method}() method exists</p>";
        } else {
            echo "<p class='error'>✗ {$method}() method not found</p>";
        }
    }

} catch (Exception $e) {
    echo "<p class='error'>✗ TableManager test failed: " . $e->getMessage() . "</p>";
}

// Test 3: Check if Admin class was updated to include TableManager
echo "<h2>3. Testing Admin Class Integration</h2>";
try {
    require_once(__DIR__ . '/includes/Admin/Admin.php');
    echo "<p class='success'>✓ Admin class loaded successfully</p>";

    // Read the Admin.php file to check for TableManager integration
    $admin_content = file_get_contents(__DIR__ . '/includes/Admin/Admin.php');

    if (strpos($admin_content, 'use HeritagePress\\Admin\\TableManager;') !== false) {
        echo "<p class='success'>✓ TableManager import statement found</p>";
    } else {
        echo "<p class='error'>✗ TableManager import statement not found</p>";
    }

    if (strpos($admin_content, 'private $tableManager;') !== false) {
        echo "<p class='success'>✓ TableManager property declaration found</p>";
    } else {
        echo "<p class='error'>✗ TableManager property declaration not found</p>";
    }

    if (strpos($admin_content, 'new TableManager()') !== false) {
        echo "<p class='success'>✓ TableManager instantiation found</p>";
    } else {
        echo "<p class='error'>✗ TableManager instantiation not found</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>✗ Admin class test failed: " . $e->getMessage() . "</p>";
}

// Test 4: Check if ImportExportManager was cleaned up
echo "<h2>4. Testing ImportExportManager Cleanup</h2>";
try {
    require_once(__DIR__ . '/includes/Admin/ImportExportManager.php');
    echo "<p class='success'>✓ ImportExportManager class loaded successfully</p>";

    // Read the ImportExportManager.php file to check cleanup
    $import_export_content = file_get_contents(__DIR__ . '/includes/Admin/ImportExportManager.php');

    // Check that table management was removed
    if (strpos($import_export_content, "'tables'") === false) {
        echo "<p class='success'>✓ 'tables' tab reference removed</p>";
    } else {
        echo "<p class='warning'>⚠️ 'tables' tab reference still found</p>";
    }

    if (strpos($import_export_content, 'render_tables_tab') === false) {
        echo "<p class='success'>✓ render_tables_tab() method removed</p>";
    } else {
        echo "<p class='error'>✗ render_tables_tab() method still exists</p>";
    }

    if (strpos($import_export_content, 'handle_get_table_structure') === false) {
        echo "<p class='success'>✓ Table management AJAX handlers removed</p>";
    } else {
        echo "<p class='error'>✗ Table management AJAX handlers still exist</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>✗ ImportExportManager test failed: " . $e->getMessage() . "</p>";
}

// Test 5: Check if template exists
echo "<h2>5. Testing Template File</h2>";
$template_path = __DIR__ . '/includes/templates/tables/tables.php';
if (file_exists($template_path)) {
    echo "<p class='success'>✓ tables.php template exists</p>";

    // Check template header
    $template_content = file_get_contents($template_path);
    if (strpos($template_content, 'HeritagePress Tools') !== false) {
        echo "<p class='success'>✓ Template header updated to 'HeritagePress Tools'</p>";
    } else {
        echo "<p class='warning'>⚠️ Template header may not be updated</p>";
    }
} else {
    echo "<p class='error'>✗ tables.php template not found</p>";
}

echo "<h2>Test Summary</h2>";
echo "<p class='info'>All tests completed. The Tools Menu implementation should now be functional.</p>";
echo "<p class='info'>To test in WordPress admin:</p>";
echo "<ol>";
echo "<li>Login to WordPress admin</li>";
echo "<li>Look for 'HeritagePress' in the main menu</li>";
echo "<li>Check for 'Tools' submenu under HeritagePress</li>";
echo "<li>Click on Tools to access table management functionality</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='" . admin_url() . "'>← Back to WordPress Admin</a></p>";
?>