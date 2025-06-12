<?php
/**
 * Quick Database Check - Existing HeritagePress Tables
 */

require_once('../../../../../../wp-config.php');

echo "<h1>🔍 HeritagePress Database Check</h1>\n";

global $wpdb;

echo "<h2>Database Connection</h2>\n";
echo "<p>Database: {$wpdb->dbname}</p>\n";
echo "<p>Prefix: {$wpdb->prefix}</p>\n";
echo "<p>Connection: " . ($wpdb->db_connect() ? "✅ Connected" : "❌ Failed") . "</p>\n";

echo "<h2>Existing HeritagePress Tables</h2>\n";

// Check for any existing HP tables
$existing_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");

if (empty($existing_tables)) {
    echo "<p style='color: red;'>❌ No HeritagePress tables found in database</p>\n";
    echo "<p>This means the schema installation has not run successfully.</p>\n";
} else {
    echo "<p style='color: green;'>✅ Found " . count($existing_tables) . " HeritagePress tables:</p>\n";
    echo "<ul>\n";
    foreach ($existing_tables as $table) {
        $clean_name = str_replace($wpdb->prefix, '', $table);
        echo "<li>$clean_name</li>\n";
    }
    echo "</ul>\n";
}

echo "<h2>Plugin Activation Status</h2>\n";

// Check if plugin is activated
$active_plugins = get_option('active_plugins', []);
$plugin_file = 'heritagepress/heritagepress.php';
$is_active = in_array($plugin_file, $active_plugins);

echo "<p>Plugin Status: " . ($is_active ? "✅ Active" : "❌ Inactive") . "</p>\n";

if (!$is_active) {
    echo "<p style='color: orange;'>⚠️ Plugin is not activated. Tables are usually created during plugin activation.</p>\n";
}

echo "<h2>Schema Files Check</h2>\n";

$schema_dir = dirname(__FILE__) . '/includes/Database/schema/';
$required_files = [
    'complete-genealogy-schema.sql',
    'default-event-types.sql'
];

foreach ($required_files as $file) {
    $exists = file_exists($schema_dir . $file);
    $status = $exists ? '✅' : '❌';
    echo "<p>$status $file</p>\n";

    if ($exists) {
        $size = filesize($schema_dir . $file);
        echo "<p style='margin-left: 20px;'>Size: " . number_format($size) . " bytes</p>\n";
    }
}

echo "<h2>Next Steps</h2>\n";

if (empty($existing_tables)) {
    echo "<ol>\n";
    echo "<li><strong>Try Manual Installation:</strong> Run <code>install-final-genealogy-schema.php</code></li>\n";
    echo "<li><strong>Check Plugin Activation:</strong> Ensure HeritagePress plugin is activated</li>\n";
    echo "<li><strong>Check Error Logs:</strong> Look for PHP/MySQL errors</li>\n";
    echo "<li><strong>Database Permissions:</strong> Verify CREATE TABLE permissions</li>\n";
    echo "</ol>\n";
} else {
    echo "<p style='color: green;'>✅ Tables exist. Schema installation appears to have worked.</p>\n";
}

echo "<hr>\n";
echo "<p><em>Check completed at " . date('Y-m-d H:i:s') . "</em></p>\n";
?>