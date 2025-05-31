<?php
/**
 * Heritage Press WordPress Plugin Status Checker
 * Comprehensive test of plugin deployment and functionality
 */

echo "<html><head><title>Heritage Press Plugin Status</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; }
.warning { color: orange; }
.error { color: red; }
.info { color: blue; }
h1 { border-bottom: 2px solid #ccc; }
h2 { margin-top: 30px; color: #333; }
.status-box { border: 1px solid #ddd; padding: 10px; margin: 10px 0; background: #f9f9f9; }
</style></head><body>";

echo "<h1>Heritage Press Plugin Status Report</h1>";
echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Check WordPress plugin directory
$plugin_dir = 'C:\\xampp\\htdocs\\wordpress\\wp-content\\plugins\\heritage-press\\';
$main_file = $plugin_dir . 'heritage-press.php';

echo "<div class='status-box'>";
echo "<h2>1. Plugin File Deployment</h2>";

if (file_exists($main_file)) {
    echo "<p class='success'>✅ Main plugin file found: heritage-press.php</p>";
    
    // Check plugin header
    $content = file_get_contents($main_file);
    if (strpos($content, 'Plugin Name: Heritage Press') !== false) {
        echo "<p class='success'>✅ Plugin header is correct</p>";
        
        // Extract version
        if (preg_match('/Version:\s*([0-9.]+)/', $content, $matches)) {
            echo "<p class='info'>📄 Plugin Version: {$matches[1]}</p>";
        }
    } else {
        echo "<p class='error'>❌ Plugin header missing or invalid</p>";
    }
} else {
    echo "<p class='error'>❌ Plugin file not found at: $main_file</p>";
}
echo "</div>";

// Test 2: Check key directories and files
echo "<div class='status-box'>";
echo "<h2>2. Directory Structure Verification</h2>";

$key_paths = [
    'admin\\' => 'Admin directory',
    'includes\\' => 'Includes directory',
    'includes\\class-autoloader.php' => 'Autoloader',
    'includes\\models\\' => 'Models directory',
    'includes\\core\\' => 'Core directory',
    'includes\\database\\' => 'Database handlers',
    'includes\\gedcom\\' => 'GEDCOM parsers',
    'admin\\views\\' => 'Admin views',
    'admin\\css\\' => 'Admin CSS',
    'admin\\js\\' => 'Admin JavaScript',
    'public\\' => 'Public assets',
    'templates\\' => 'Templates'
];

$missing_count = 0;
foreach ($key_paths as $path => $description) {
    $full_path = $plugin_dir . $path;
    if (file_exists($full_path)) {
        echo "<p class='success'>✅ $description</p>";
    } else {
        echo "<p class='error'>❌ Missing: $description ($path)</p>";
        $missing_count++;
    }
}

if ($missing_count === 0) {
    echo "<p class='success'><strong>All key directories and files are present!</strong></p>";
} else {
    echo "<p class='warning'><strong>$missing_count items are missing</strong></p>";
}
echo "</div>";

// Test 3: Check specific core files
echo "<div class='status-box'>";
echo "<h2>3. Core Files Check</h2>";

$core_files = [
    'includes\\class-autoloader.php' => 'Autoloader class',
    'includes\\core\\class-activator.php' => 'Plugin activator',
    'includes\\models\\class-individual.php' => 'Individual model',
    'includes\\models\\class-family.php' => 'Family model',
    'includes\\database\\class-database-handler.php' => 'Database handler',
    'includes\\gedcom\\class-gedcom-parser.php' => 'GEDCOM parser',
    'admin\\views\\admin-dashboard.php' => 'Admin dashboard'
];

foreach ($core_files as $file => $description) {
    $full_path = $plugin_dir . $file;
    if (file_exists($full_path)) {
        $size = filesize($full_path);
        echo "<p class='success'>✅ $description (" . number_format($size) . " bytes)</p>";
    } else {
        echo "<p class='error'>❌ Missing: $description</p>";
    }
}
echo "</div>";

// Test 4: PHP Syntax Check
echo "<div class='status-box'>";
echo "<h2>4. PHP Syntax Validation</h2>";

if (file_exists($main_file)) {
    // Use XAMPP PHP for syntax check
    $syntax_check = shell_exec("C:\\xampp\\php\\php.exe -l \"$main_file\" 2>&1");
    if (strpos($syntax_check, 'No syntax errors') !== false) {
        echo "<p class='success'>✅ Main plugin file has valid PHP syntax</p>";
    } else {
        echo "<p class='error'>❌ PHP syntax errors found:</p>";
        echo "<pre>$syntax_check</pre>";
    }
    
    // Check autoloader syntax
    $autoloader_file = $plugin_dir . 'includes\\class-autoloader.php';
    if (file_exists($autoloader_file)) {
        $auto_check = shell_exec("C:\\xampp\\php\\php.exe -l \"$autoloader_file\" 2>&1");
        if (strpos($auto_check, 'No syntax errors') !== false) {
            echo "<p class='success'>✅ Autoloader has valid PHP syntax</p>";
        } else {
            echo "<p class='error'>❌ Autoloader syntax errors:</p>";
            echo "<pre>$auto_check</pre>";
        }
    }
}
echo "</div>";

// Test 5: WordPress Integration Readiness
echo "<div class='status-box'>";
echo "<h2>5. WordPress Integration Status</h2>";

try {
    // Try to load WordPress (this might fail if run outside WordPress context)
    $wp_config = 'C:\\xampp\\htdocs\\wordpress\\wp-config.php';
    if (file_exists($wp_config)) {
        echo "<p class='success'>✅ WordPress installation found</p>";
        echo "<p class='info'>📍 WordPress path: C:\\xampp\\htdocs\\wordpress\\</p>";
        
        // Check if we can determine WordPress version
        $wp_version_file = 'C:\\xampp\\htdocs\\wordpress\\wp-includes\\version.php';
        if (file_exists($wp_version_file)) {
            $version_content = file_get_contents($wp_version_file);
            if (preg_match("/\\$wp_version = '([^']+)'/", $version_content, $matches)) {
                echo "<p class='info'>📄 WordPress Version: {$matches[1]}</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ WordPress installation not found</p>";
    }
} catch (Exception $e) {
    echo "<p class='warning'>⚠️ Cannot determine WordPress status: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 6: Activation Instructions
echo "<div class='status-box'>";
echo "<h2>6. Plugin Activation Instructions</h2>";
echo "<p>To activate Heritage Press plugin:</p>";
echo "<ol>";
echo "<li>Open WordPress Admin: <a href='http://localhost/wordpress/wp-admin/plugins.php' target='_blank'>http://localhost/wordpress/wp-admin/plugins.php</a></li>";
echo "<li>Login to WordPress admin panel</li>";
echo "<li>Navigate to Plugins → Installed Plugins</li>";
echo "<li>Find 'Heritage Press' in the plugin list</li>";
echo "<li>Click 'Activate' button</li>";
echo "<li>Check for any activation errors</li>";
echo "</ol>";
echo "</div>";

// Test 7: Expected Post-Activation Features
echo "<div class='status-box'>";
echo "<h2>7. Expected Features After Activation</h2>";
echo "<p>Once activated, Heritage Press should provide:</p>";
echo "<ul>";
echo "<li>🔗 'Heritage Press' menu in WordPress admin sidebar</li>";
echo "<li>👥 Individual management (add/edit family members)</li>";
echo "<li>👨‍👩‍👧‍👦 Family relationship management</li>";
echo "<li>📁 GEDCOM file import/export capabilities</li>";
echo "<li>🔍 Evidence analysis and citation tools</li>";
echo "<li>📊 Family charts and reports</li>";
echo "<li>🗃️ Research organization tools</li>";
echo "</ul>";
echo "</div>";

// Test 8: Database Tables Expected
echo "<div class='status-box'>";
echo "<h2>8. Database Tables (Created on Activation)</h2>";
echo "<p>Heritage Press will create these database tables:</p>";
echo "<ul>";
echo "<li>wp_heritage_individuals</li>";
echo "<li>wp_heritage_families</li>";
echo "<li>wp_heritage_citations</li>";
echo "<li>wp_heritage_evidence</li>";
echo "<li>wp_heritage_places</li>";
echo "<li>wp_heritage_sources</li>";
echo "<li>wp_heritage_events</li>";
echo "<li>wp_heritage_media</li>";
echo "</ul>";
echo "</div>";

// Test 9: Testing Recommendations
echo "<div class='status-box'>";
echo "<h2>9. Testing Workflow Recommendations</h2>";
echo "<ol>";
echo "<li><strong>Activate Plugin:</strong> Use WordPress admin interface</li>";
echo "<li><strong>Check Admin Menu:</strong> Verify Heritage Press appears in sidebar</li>";
echo "<li><strong>Test Basic Functions:</strong> Add a test individual</li>";
echo "<li><strong>Import Test:</strong> Try importing a small GEDCOM file</li>";
echo "<li><strong>Database Verification:</strong> Check that tables are created</li>";
echo "<li><strong>Frontend Test:</strong> Verify no conflicts with WordPress theme</li>";
echo "</ol>";
echo "</div>";

echo "<div class='status-box'>";
echo "<h2>Quick Links</h2>";
echo "<ul>";
echo "<li><a href='http://localhost/wordpress/' target='_blank'>WordPress Frontend</a></li>";
echo "<li><a href='http://localhost/wordpress/wp-admin/' target='_blank'>WordPress Admin Dashboard</a></li>";
echo "<li><a href='http://localhost/wordpress/wp-admin/plugins.php' target='_blank'>WordPress Plugins Page</a></li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>
