<?php
/**
 * Debug Step 2 Access Issue
 * 
 * Test if the ImportExportManager is being created properly
 * and if there are permission issues
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>HeritagePress Step 2 Access Debug</h1>";

// Test 1: Check if user has proper permissions
echo "<h2>1. User Permissions</h2>";
echo "<p>Current user can 'manage_options': " . (current_user_can('manage_options') ? '✅ YES' : '❌ NO') . "</p>";
echo "<p>Current user ID: " . get_current_user_id() . "</p>";
echo "<p>User roles: " . implode(', ', wp_get_current_user()->roles) . "</p>";

// Test 2: Check if plugin is active and classes are available
echo "<h2>2. Plugin Status</h2>";
if (class_exists('HeritagePress\\Admin\\ImportExportManager')) {
    echo "<p>✅ ImportExportManager class exists</p>";

    try {
        $manager = new HeritagePress\Admin\ImportExportManager();
        echo "<p>✅ ImportExportManager can be instantiated</p>";

        if (method_exists($manager, 'render_page')) {
            echo "<p>✅ render_page method exists</p>";
        } else {
            echo "<p>❌ render_page method missing</p>";
        }

    } catch (Exception $e) {
        echo "<p>❌ Error creating ImportExportManager: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>❌ ImportExportManager class does not exist</p>";
}

// Test 3: Check MenuConfig
echo "<h2>3. Menu Configuration</h2>";
if (class_exists('HeritagePress\\Config\\MenuConfig')) {
    echo "<p>✅ MenuConfig class exists</p>";

    $manager_class = HeritagePress\Config\MenuConfig::getManagerForSlug('heritagepress-import-export');
    echo "<p>Manager class for 'heritagepress-import-export': " . ($manager_class ?: 'NONE FOUND') . "</p>";

    $capabilities = HeritagePress\Config\MenuConfig::getCapabilities();
    echo "<p>Required capability: " . ($capabilities['heritagepress-import-export'] ?? 'NOT SET') . "</p>";

} else {
    echo "<p>❌ MenuConfig class does not exist</p>";
}

// Test 4: Check ManagerFactory
echo "<h2>4. Manager Factory</h2>";
if (class_exists('HeritagePress\\Factories\\ManagerFactory')) {
    echo "<p>✅ ManagerFactory class exists</p>";

    try {
        $factory = new HeritagePress\Factories\ManagerFactory();
        echo "<p>✅ ManagerFactory can be instantiated</p>";

        if (method_exists($factory, 'create')) {
            echo "<p>✅ create method exists</p>";

            try {
                $manager = $factory->create('ImportExportManager');
                echo "<p>✅ ImportExportManager can be created via factory</p>";
            } catch (Exception $e) {
                echo "<p>❌ Error creating ImportExportManager via factory: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p>❌ create method missing from factory</p>";
        }

    } catch (Exception $e) {
        echo "<p>❌ Error creating ManagerFactory: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>❌ ManagerFactory class does not exist</p>";
}

// Test 5: Test the exact URL that's failing
echo "<h2>5. URL Test</h2>";
$step2_url = admin_url('admin.php?page=heritagepress-import-export&tab=import&step=2&file=test&tree_id=2&import_option=replace');
echo "<p>Step 2 URL: <a href='" . htmlspecialchars($step2_url) . "' target='_blank'>" . htmlspecialchars($step2_url) . "</a></p>";

// Test 6: WordPress menu system check
echo "<h2>6. WordPress Menu System</h2>";
global $menu, $submenu;

$found_main = false;
$found_sub = false;

// Check main menu
if (isset($menu)) {
    foreach ($menu as $item) {
        if (isset($item[2]) && $item[2] === 'heritagepress') {
            $found_main = true;
            echo "<p>✅ Main HeritagePress menu found</p>";
            break;
        }
    }
}

if (!$found_main) {
    echo "<p>❌ Main HeritagePress menu NOT found</p>";
}

// Check submenu
if (isset($submenu['heritagepress'])) {
    foreach ($submenu['heritagepress'] as $item) {
        if (isset($item[2]) && $item[2] === 'heritagepress-import-export') {
            $found_sub = true;
            echo "<p>✅ Import/Export submenu found</p>";
            break;
        }
    }
}

if (!$found_sub) {
    echo "<p>❌ Import/Export submenu NOT found</p>";
}

echo "<h2>7. Test Direct Access</h2>";
echo "<p>Try accessing the page directly:</p>";
echo "<p><a href='" . admin_url('admin.php?page=heritagepress-import-export') . "' target='_blank'>Main Import/Export Page</a></p>";
echo "<p><a href='" . admin_url('admin.php?page=heritagepress-import-export&tab=import') . "' target='_blank'>Import Tab</a></p>";
echo "<p><a href='" . admin_url('admin.php?page=heritagepress-import-export&tab=import&step=1') . "' target='_blank'>Import Step 1</a></p>";

?>