<?php
/**
 * Simple Step 2 Access Test
 * 
 * Test specific access to Step 2 functionality
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>Step 2 Direct Access Test</h1>";

// Test if we can access the import page directly
echo "<h2>Testing Import Page Access</h2>";

// Simulate the exact conditions of Step 2
$_GET['page'] = 'heritagepress-import-export';
$_GET['tab'] = 'import';
$_GET['step'] = '2';
$_GET['file'] = 'gedcom_684a5b9b372d93.45983913';
$_GET['tree_id'] = '2';
$_GET['import_option'] = 'replace';

echo "<p>Simulated GET parameters:</p>";
echo "<pre>" . print_r($_GET, true) . "</pre>";

echo "<h3>1. Check Permissions</h3>";
if (current_user_can('manage_options')) {
    echo "<p style='color: green;'>✅ User has 'manage_options' permission</p>";
} else {
    echo "<p style='color: red;'>❌ User does NOT have 'manage_options' permission</p>";
    echo "<p>This is likely the cause of the access denied error!</p>";
    exit;
}

echo "<h3>2. Test ImportExportManager Creation</h3>";
try {
    $manager = new HeritagePress\Admin\ImportExportManager();
    echo "<p style='color: green;'>✅ ImportExportManager created successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error creating ImportExportManager: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

echo "<h3>3. Test Page Rendering</h3>";
try {
    ob_start();
    $manager->render_page();
    $output = ob_get_clean();

    if (strlen($output) > 100) { // Check if substantial content was generated
        echo "<p style='color: green;'>✅ Page rendered successfully (" . strlen($output) . " characters)</p>";

        // Look for specific Step 2 content
        if (strpos($output, 'Step 2') !== false || strpos($output, 'Validation') !== false) {
            echo "<p style='color: green;'>✅ Step 2 content found in output</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Step 2 content not found, but page rendered</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Page render produced minimal output</p>";
        echo "<p>Output: <pre>" . htmlspecialchars($output) . "</pre></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error rendering page: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h3>4. Check WordPress Menu Registration</h3>";
global $submenu;
if (isset($submenu['heritagepress'])) {
    $found = false;
    foreach ($submenu['heritagepress'] as $item) {
        if ($item[2] === 'heritagepress-import-export') {
            $found = true;
            echo "<p style='color: green;'>✅ Import/Export submenu is registered with capability: " . $item[1] . "</p>";
            break;
        }
    }
    if (!$found) {
        echo "<p style='color: red;'>❌ Import/Export submenu not found in WordPress menu system</p>";
    }
} else {
    echo "<p style='color: red;'>❌ HeritagePress main menu not found in WordPress menu system</p>";
}

echo "<h3>5. User Information</h3>";
$current_user = wp_get_current_user();
echo "<p>User ID: " . $current_user->ID . "</p>";
echo "<p>User Login: " . $current_user->user_login . "</p>";
echo "<p>User Roles: " . implode(', ', $current_user->roles) . "</p>";
echo "<p>User Capabilities: " . count($current_user->allcaps) . " total capabilities</p>";

// Check specific capabilities
$required_caps = ['manage_options', 'edit_posts', 'manage_categories'];
foreach ($required_caps as $cap) {
    echo "<p>Has '$cap': " . (current_user_can($cap) ? '✅' : '❌') . "</p>";
}

?>