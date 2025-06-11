<?php
/**
 * Direct activation test script
 * This will test the activation hook directly
 */

// Load WordPress
require_once('../../../wp-load.php');

// Load plugin files
require_once('includes/class-heritagepress.php');

echo "<h1>Direct Activation Test</h1>";

global $wpdb;
echo "<p>Database prefix: {$wpdb->prefix}</p>";

// Check tables before
$before_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "<p>Tables before activation: " . count($before_tables) . "</p>";

// Test activation
echo "<h2>Running Activation</h2>";
try {
    HeritagePress::activate();
    echo "<p style='color: green;'>✓ Activation completed successfully!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Check tables after
$after_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "<p>Tables after activation: " . count($after_tables) . "</p>";

// Show created tables
$created_tables = array_diff($after_tables, $before_tables);
if (count($created_tables) > 0) {
    echo "<h3>New tables created:</h3>";
    echo "<ul>";
    foreach ($created_tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: orange;'>⚠️ No new tables were created</p>";
}

// Debug info
echo "<h2>Debug Information</h2>";
echo "<p>WordPress Version: " . get_bloginfo('version') . "</p>";
echo "<p>Plugin Dir Constant: " . (defined('HERITAGEPRESS_PLUGIN_DIR') ? HERITAGEPRESS_PLUGIN_DIR : 'Not defined') . "</p>";
echo "<p>Plugin File Constant: " . (defined('HERITAGEPRESS_PLUGIN_FILE') ? HERITAGEPRESS_PLUGIN_FILE : 'Not defined') . "</p>";

// Test database manager directly
echo "<h2>Direct Database Manager Test</h2>";
try {
    require_once('includes/Database/Manager.php');
    $db_manager = new HeritagePress\Database\Manager(__DIR__, '1.0.0');
    echo "<p style='color: green;'>✓ Database Manager created successfully</p>";

    echo "<p>Running install() method...</p>";
    $db_manager->install();
    echo "<p style='color: green;'>✓ install() method completed</p>";

    // Check tables again
    $final_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
    echo "<p>Tables after direct install: " . count($final_tables) . "</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database Manager Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>