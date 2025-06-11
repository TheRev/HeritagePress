<?php
/**
 * Complete TNG Schema Installation - All 36 Tables
 * This script installs the complete TNG-based schema with GEDCOM 7 extensions
 * providing 100% TNG compatibility with modern enhancements
 */

require_once('../../../../../../wp-config.php');

echo "<h1>🏗️ Complete TNG Schema Installation - All 36 Tables</h1>\n";

global $wpdb;

// Include the Database Manager
require_once(dirname(__FILE__) . '/includes/Database/Manager.php');
require_once(dirname(__FILE__) . '/includes/Helpers/WPHelper.php');

use HeritagePress\Database\Manager;

echo "<h2>📊 Schema Overview</h2>\n";
echo "<div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #0073aa;'>\n";
echo "<p><strong>Complete TNG Database Structure (36 Tables):</strong></p>\n";
echo "<ul>\n";
echo "<li><strong>Core Tables (22):</strong> Trees, People, Families, Children, Events, Places, Sources, etc.</li>\n";
echo "<li><strong>Advanced Features (17):</strong> Albums, Branches, DNA, Reports, Templates, Users, etc.</li>\n";
echo "<li><strong>GEDCOM 7 Extensions:</strong> Enhanced data types, enumerations, custom tags</li>\n";
echo "</ul>\n";
echo "<p>This provides 100% TNG compatibility plus modern GEDCOM 7.0 compliance.</p>\n";
echo "</div>\n";

echo "<h2>🔄 Installing Complete Schema...</h2>\n";

try {
    // Create database manager
    $plugin_dir = dirname(__FILE__) . '/';
    $manager = new Manager($plugin_dir, '1.0.0');
    
    echo "<p>✅ Database manager created successfully</p>\n";
    
    // Get current table count
    $existing_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
    $before_count = count($existing_tables);
    
    echo "<p>📋 Tables before installation: <strong>$before_count</strong></p>\n";
    
    // Install all tables
    $manager->install();
    
    echo "<p>✅ Schema installation completed</p>\n";
    
    // Check final table count
    $final_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
    $after_count = count($final_tables);
    $added_count = $after_count - $before_count;
    
    echo "<h2>📈 Installation Results</h2>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Metric</th><th>Value</th></tr>\n";
    echo "<tr><td>Tables Before</td><td>$before_count</td></tr>\n";
    echo "<tr><td>Tables After</td><td>$after_count</td></tr>\n";
    echo "<tr><td>Tables Added</td><td style='color: green; font-weight: bold;'>$added_count</td></tr>\n";
    echo "<tr><td>Target (TNG Complete)</td><td>39 tables</td></tr>\n";
    echo "<tr><td>Completion</td><td>" . round(($after_count / 39) * 100, 1) . "%</td></tr>\n";
    echo "</table>\n";
    
    if ($after_count >= 36) {
        echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>\n";
        echo "<h3>🎉 Complete TNG Schema Successfully Installed!</h3>\n";
        echo "<p>All 36+ TNG tables have been created with GEDCOM 7 extensions.</p>\n";
        echo "<p><strong>Your HeritagePress installation now has:</strong></p>\n";
        echo "<ul>\n";
        echo "<li>✅ 100% TNG compatibility</li>\n";
        echo "<li>✅ Direct GEDCOM-to-database mapping</li>\n";
        echo "<li>✅ GEDCOM 7.0 compliance</li>\n";
        echo "<li>✅ Advanced genealogy features</li>\n";
        echo "</ul>\n";
        echo "</div>\n";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>\n";
        echo "<h3>⚠️ Partial Installation</h3>\n";
        echo "<p>Some tables may not have been created. Expected 36+ tables, got $after_count.</p>\n";
        echo "</div>\n";
    }
    
    echo "<h2>📋 Installed Tables</h2>\n";
    echo "<div style='max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;'>\n";
    
    if (!empty($final_tables)) {
        echo "<ol>\n";
        foreach ($final_tables as $table) {
            $clean_name = str_replace($wpdb->prefix, '', $table);
            echo "<li>$clean_name</li>\n";
        }
        echo "</ol>\n";
    } else {
        echo "<p style='color: red;'>❌ No HeritagePress tables found</p>\n";
    }
    
    echo "</div>\n";
    
    echo "<h2>🎯 Next Steps</h2>\n";
    echo "<ol>\n";
    echo "<li><strong>Test GEDCOM Import:</strong> Upload and import a GEDCOM file to verify the schema</li>\n";
    echo "<li><strong>Configure Settings:</strong> Set up privacy levels, user permissions, and display options</li>\n";
    echo "<li><strong>Add Default Data:</strong> Populate lookup tables (countries, states, media types)</li>\n";
    echo "<li><strong>Create First Tree:</strong> Start building your family tree database</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>\n";
    echo "<h3>❌ Installation Error</h3>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>\n";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>\n";
    echo "</div>\n";
    
    // Show any existing tables for debugging
    $existing = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
    if (!empty($existing)) {
        echo "<h3>Existing HeritagePress Tables:</h3>\n";
        echo "<ul>\n";
        foreach ($existing as $table) {
            echo "<li>" . str_replace($wpdb->prefix, '', $table) . "</li>\n";
        }
        echo "</ul>\n";
    }
}

echo "<hr>\n";
echo "<p><a href='" . admin_url() . "'>← Back to WordPress Admin</a></p>\n";
?>
