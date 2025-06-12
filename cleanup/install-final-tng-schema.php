<?php
/**
 * Install Complete TNG Schema - Final Implementation
 * This script installs the complete 39-table TNG-based schema with GEDCOM 7 extensions
 * providing 100% TNG compatibility plus modern features
 */

require_once('../../../../../../wp-config.php');

echo "<h1>🏗️ Installing Complete TNG Schema - Final Implementation</h1>\n";

global $wpdb;

// Include the Database Manager
require_once(dirname(__FILE__) . '/includes/Database/Manager.php');

use HeritagePress\Database\Manager;

echo "<h2>📊 Complete TNG Schema Overview</h2>\n";
echo "<div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #0073aa;'>\n";
echo "<p><strong>Complete TNG Database Structure (39 Tables):</strong></p>\n";
echo "<ul>\n";
echo "<li><strong>Core TNG Tables (22):</strong> Trees, People, Families, Children, Events, Places, Sources, etc.</li>\n";
echo "<li><strong>Advanced TNG Tables (17):</strong> Albums, Branches, DNA, Reports, Templates, Users, etc.</li>\n";
echo "<li><strong>GEDCOM 7 Extensions:</strong> Enhanced data types, enumerations, custom tags</li>\n";
echo "</ul>\n";
echo "<p><strong>Key Features:</strong></p>\n";
echo "<ul>\n";
echo "<li>✅ 100% TNG compatibility - proven genealogy software structure (20+ years)</li>\n";
echo "<li>✅ Direct GEDCOM-to-database mapping - no complex transformations</li>\n";
echo "<li>✅ GEDCOM 7.0 compliance - modern extensions and features</li>\n";
echo "<li>✅ Original GEDCOM dates + parsed dates for reliable searching</li>\n";
echo "<li>✅ Direct GEDCOM ID references (e.g., @I123@, @F456@)</li>\n";
echo "<li>✅ Advanced features: DNA testing, photo albums, branches, reports</li>\n";
echo "</ul>\n";
echo "</div>\n";

// Check current table status
echo "<h2>📋 Current Database Status</h2>\n";
$existing_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
$before_count = count($existing_tables);

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
echo "<tr><th>Status</th><th>Value</th></tr>\n";
echo "<tr><td>Current Tables</td><td>$before_count</td></tr>\n";
echo "<tr><td>Target Tables</td><td>39</td></tr>\n";
echo "<tr><td>Schema Files</td><td>complete-tng-schema.sql, documentation-tables.sql, default-event-types.sql</td></tr>\n";
echo "</table>\n";

if ($before_count > 0) {
    echo "<p><strong>Existing HeritagePress Tables:</strong></p>\n";
    echo "<div style='max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;'>\n";
    foreach ($existing_tables as $table) {
        $clean_name = str_replace($wpdb->prefix, '', $table);
        echo "• $clean_name<br>\n";
    }
    echo "</div>\n";
}

echo "<h2>🔄 Installing Complete TNG Schema...</h2>\n";

try {
    // Create database manager
    $plugin_dir = dirname(__FILE__) . '/';
    $manager = new Manager($plugin_dir, '2.0.0');
    
    echo "<p>✅ Database manager created successfully</p>\n";
    echo "<p>📁 Schema directory: " . $plugin_dir . "includes/Database/schema/</p>\n";
    
    // Install all tables
    echo "<p>🔧 Installing schema files...</p>\n";
    $manager->install();
    
    echo "<p>✅ Schema installation completed</p>\n";
    
    // Check final table count
    $final_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
    $after_count = count($final_tables);
    $added_count = $after_count - $before_count;
    
    echo "<h2>📈 Installation Results</h2>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Metric</th><th>Value</th><th>Status</th></tr>\n";
    echo "<tr><td>Tables Before</td><td>$before_count</td><td>-</td></tr>\n";
    echo "<tr><td>Tables After</td><td>$after_count</td><td style='color: " . ($after_count >= 39 ? 'green' : 'orange') . ";'>$after_count/39</td></tr>\n";
    echo "<tr><td>Tables Added</td><td style='color: green; font-weight: bold;'>$added_count</td><td>-</td></tr>\n";
    echo "<tr><td>Completion</td><td>" . round(($after_count / 39) * 100, 1) . "%</td><td style='color: " . ($after_count >= 39 ? 'green' : 'orange') . ";'>" . ($after_count >= 39 ? 'COMPLETE' : 'PARTIAL') . "</td></tr>\n";
    echo "</table>\n";
    
    if ($after_count >= 39) {
        echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>\n";
        echo "<h3>🎉 Complete TNG Schema Successfully Installed!</h3>\n";
        echo "<p>All 39 TNG tables have been created with GEDCOM 7 extensions.</p>\n";
        echo "<p><strong>Your HeritagePress installation now has:</strong></p>\n";
        echo "<ul>\n";
        echo "<li>✅ 100% TNG compatibility</li>\n";
        echo "<li>✅ Direct GEDCOM-to-database mapping</li>\n";
        echo "<li>✅ GEDCOM 7.0 compliance</li>\n";
        echo "<li>✅ Advanced genealogy features</li>\n";
        echo "<li>✅ DNA testing support</li>\n";
        echo "<li>✅ Photo album management</li>\n";
        echo "<li>✅ Branch organization</li>\n";
        echo "<li>✅ Custom reports</li>\n";
        echo "</ul>\n";
        echo "<p><strong>🚀 Ready for GEDCOM Import!</strong></p>\n";
        echo "</div>\n";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>\n";
        echo "<h3>⚠️ Partial Installation</h3>\n";
        echo "<p>Some tables may not have been created. Expected 39 tables, got $after_count.</p>\n";
        echo "<p>Check the error log for details.</p>\n";
        echo "</div>\n";
    }
    
    echo "<h2>📋 Installed Tables by Category</h2>\n";
    
    // Categorize tables
    $core_tables = ['hp_trees', 'hp_people', 'hp_families', 'hp_children', 'hp_events', 'hp_eventtypes', 
                   'hp_places', 'hp_sources', 'hp_repositories', 'hp_citations', 'hp_media', 'hp_medialinks',
                   'hp_xnotes', 'hp_notelinks', 'hp_associations', 'hp_countries', 'hp_states', 'hp_mediatypes',
                   'hp_languages', 'hp_gedcom7_enums', 'hp_gedcom7_extensions', 'hp_gedcom7_data'];
    
    $advanced_tables = ['hp_address', 'hp_albums', 'hp_albumlinks', 'hp_album2entities', 'hp_branches', 'hp_branchlinks',
                       'hp_cemeteries', 'hp_dna_groups', 'hp_dna_links', 'hp_dna_tests', 'hp_image_tags', 'hp_mostwanted',
                       'hp_reports', 'hp_saveimport', 'hp_temp_events', 'hp_templates', 'hp_users'];
    
    $installed_core = 0;
    $installed_advanced = 0;
    
    if (!empty($final_tables)) {
        echo "<div style='display: flex; gap: 20px;'>\n";
        
        // Core Tables
        echo "<div style='flex: 1; border: 1px solid #ddd; padding: 10px;'>\n";
        echo "<h4>Core TNG Tables (22)</h4>\n";
        echo "<ul style='font-size: 12px; max-height: 300px; overflow-y: auto;'>\n";
        foreach ($core_tables as $table) {
            $full_table = $wpdb->prefix . $table;
            $exists = in_array($full_table, $final_tables);
            if ($exists) $installed_core++;
            echo "<li style='color: " . ($exists ? 'green' : 'red') . ";'>" . ($exists ? '✅' : '❌') . " $table</li>\n";
        }
        echo "</ul>\n";
        echo "<p><strong>Installed: $installed_core/22</strong></p>\n";
        echo "</div>\n";
        
        // Advanced Tables
        echo "<div style='flex: 1; border: 1px solid #ddd; padding: 10px;'>\n";
        echo "<h4>Advanced TNG Tables (17)</h4>\n";
        echo "<ul style='font-size: 12px; max-height: 300px; overflow-y: auto;'>\n";
        foreach ($advanced_tables as $table) {
            $full_table = $wpdb->prefix . $table;
            $exists = in_array($full_table, $final_tables);
            if ($exists) $installed_advanced++;
            echo "<li style='color: " . ($exists ? 'green' : 'red') . ";'>" . ($exists ? '✅' : '❌') . " $table</li>\n";
        }
        echo "</ul>\n";
        echo "<p><strong>Installed: $installed_advanced/17</strong></p>\n";
        echo "</div>\n";
        
        echo "</div>\n";
    }
    
    echo "<h2>🎯 Next Steps</h2>\n";
    echo "<div style='background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3;'>\n";
    if ($after_count >= 39) {
        echo "<p><strong>Schema Installation Complete!</strong> You can now:</p>\n";
        echo "<ol>\n";
        echo "<li>🎯 Test GEDCOM import with the new schema</li>\n";
        echo "<li>🧹 Remove all TNG references from file names and content</li>\n";
        echo "<li>🔧 Verify the GedcomServiceSimplified is working correctly</li>\n";
        echo "<li>✅ Run final validation tests</li>\n";
        echo "</ol>\n";
    } else {
        echo "<p><strong>Troubleshooting:</strong></p>\n";
        echo "<ol>\n";
        echo "<li>Check WordPress error logs for SQL errors</li>\n";
        echo "<li>Ensure database user has CREATE TABLE permissions</li>\n";
        echo "<li>Verify schema files exist and are readable</li>\n";
        echo "<li>Try running this script again</li>\n";
        echo "</ol>\n";
    }
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>\n";
    echo "<h3>❌ Installation Failed</h3>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>\n";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>\n";
    echo "</div>\n";
}

echo "<hr>\n";
echo "<p><em>Installation completed at " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
