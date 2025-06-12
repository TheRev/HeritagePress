<?php
/**
 * Debug Plugin Activation Test
 * 
 * This script provides detailed debugging for table creation issues
 * Access via: http://localhost/wordpress/wp-content/plugins/heritagepress/HeritagePress/debug-activation.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load WordPress
$wp_load_path = '../../../wp-load.php';
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    die('WordPress not found. Make sure this plugin is in the correct directory.');
}

echo '<h1>HeritagePress Debug Activation Test</h1>';
echo '<style>
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
</style>';

// Test 1: Check WordPress basics
echo '<h2>1. WordPress Environment Check</h2>';
echo '<p class="info">WordPress Version: ' . get_bloginfo('version') . '</p>';
echo '<p class="info">Database Prefix: ' . $wpdb->prefix . '</p>';
echo '<p class="info">Plugin Directory: ' . __DIR__ . '</p>';

// Test 2: Check if files exist
echo '<h2>2. File Existence Check</h2>';
$required_files = [
    'includes/class-heritagepress.php',
    'includes/Database/Manager.php',
    'includes/Database/WPHelper.php',
    'includes/Database/schema/core-tables.sql',
    'includes/Database/schema/gedcom7-tables.sql',
    'includes/Database/schema/compliance-tables.sql',
    'includes/Database/schema/documentation-tables.sql'
];

foreach ($required_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo '<p class="success">✓ Found: ' . $file . '</p>';
    } else {
        echo '<p class="error">✗ Missing: ' . $file . '</p>';
    }
}

// Test 3: Try to load classes
echo '<h2>3. Class Loading Test</h2>';
try {
    require_once(__DIR__ . '/includes/class-heritagepress.php');
    echo '<p class="success">✓ HeritagePress main class loaded</p>';
} catch (Exception $e) {
    echo '<p class="error">✗ Failed to load HeritagePress class: ' . $e->getMessage() . '</p>';
} catch (Error $e) {
    echo '<p class="error">✗ PHP Error loading HeritagePress class: ' . $e->getMessage() . '</p>';
}

try {
    require_once(__DIR__ . '/includes/Database/Manager.php');
    echo '<p class="success">✓ Database Manager class loaded</p>';
} catch (Exception $e) {
    echo '<p class="error">✗ Failed to load Database Manager: ' . $e->getMessage() . '</p>';
} catch (Error $e) {
    echo '<p class="error">✗ PHP Error loading Database Manager: ' . $e->getMessage() . '</p>';
}

// Test 4: Test direct database manager instantiation
echo '<h2>4. Database Manager Test</h2>';
try {
    $db_manager = new HeritagePress\Database\Manager(__DIR__, '1.0.0');
    echo '<p class="success">✓ Database Manager instantiated successfully</p>';

    // Test schema directory
    $schema_dir = __DIR__ . '/includes/Database/schema/';
    echo '<p class="info">Schema directory: ' . $schema_dir . '</p>';

    if (is_dir($schema_dir)) {
        echo '<p class="success">✓ Schema directory exists</p>';
        $sql_files = glob($schema_dir . '*.sql');
        echo '<p class="info">Found ' . count($sql_files) . ' SQL files:</p>';
        foreach ($sql_files as $file) {
            echo '<p class="info">- ' . basename($file) . '</p>';
        }
    } else {
        echo '<p class="error">✗ Schema directory missing</p>';
    }

} catch (Exception $e) {
    echo '<p class="error">✗ Database Manager instantiation failed: ' . $e->getMessage() . '</p>';
} catch (Error $e) {
    echo '<p class="error">✗ PHP Error with Database Manager: ' . $e->getMessage() . '</p>';
}

// Test 5: Check current tables before activation
echo '<h2>5. Current Table Status</h2>';
global $wpdb;
$before_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo '<p class="info">Found ' . count($before_tables) . ' HeritagePress tables:</p>';
if (count($before_tables) > 0) {
    foreach ($before_tables as $table) {
        echo '<p class="info">- ' . $table . '</p>';
    }
} else {
    echo '<p class="warning">No HeritagePress tables found</p>';
}

// Test 6: Try manual table creation
echo '<h2>6. Manual Table Creation Test</h2>';
if (isset($db_manager)) {
    try {
        echo '<p class="info">Attempting to run install() method...</p>';
        $db_manager->install();
        echo '<p class="success">✓ install() method completed without errors</p>';
    } catch (Exception $e) {
        echo '<p class="error">✗ install() method failed: ' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    } catch (Error $e) {
        echo '<p class="error">✗ PHP Error in install() method: ' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
}

// Test 7: Check tables after manual creation
echo '<h2>7. Table Status After Manual Creation</h2>';
$after_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo '<p class="info">Found ' . count($after_tables) . ' HeritagePress tables after manual creation:</p>';
if (count($after_tables) > 0) {
    foreach ($after_tables as $table) {
        echo '<p class="success">✓ ' . $table . '</p>';
    }
} else {
    echo '<p class="error">✗ Still no HeritagePress tables found</p>';
}

$new_tables = array_diff($after_tables, $before_tables);
if (count($new_tables) > 0) {
    echo '<p class="success">✓ ' . count($new_tables) . ' new tables created:</p>';
    foreach ($new_tables as $table) {
        echo '<p class="success">+ ' . $table . '</p>';
    }
} else {
    echo '<p class="warning">⚠️ No new tables were created</p>';
}

echo '<hr>';
echo '<p><a href="' . admin_url() . '">← Back to WordPress Admin</a></p>';
?>