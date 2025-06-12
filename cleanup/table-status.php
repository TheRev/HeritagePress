<?php
/**
 * Quick Table Count Check
 * Simple script to check current table status
 */

// Load WordPress
$wp_load_path = realpath(__DIR__ . '/../../../wp-load.php');
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    die('WordPress not found');
}

echo "<h1>HeritagePress Table Status</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

global $wpdb;
echo "<p>Database prefix: <strong>" . $wpdb->prefix . "</strong></p>";

// Check HeritagePress tables
$tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");

echo "<h2>Current HeritagePress Tables (" . count($tables) . ")</h2>";

if (count($tables) > 0) {
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li style='color: green;'>✓ " . $table . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red; font-size: 18px;'>❌ No HeritagePress tables found</p>";
}

// Expected tables (32 total)
$expected = [
    'hp_trees',
    'hp_individuals',
    'hp_names',
    'hp_families',
    'hp_family_links',
    'hp_events',
    'hp_event_links',
    'hp_places',
    'hp_event_types',
    'hp_aliases',
    'hp_ages',
    'hp_relationships',
    'hp_gedzip_archives',
    'hp_gedzip_files',
    'hp_calendar_systems',
    'hp_calendar_dates',
    'hp_extended_characters',
    'hp_extended_character_mappings',
    'hp_event_roles',
    'hp_family_groups',
    'hp_family_group_members',
    'hp_dna_tests',
    'hp_dna_matches',
    'hp_dna_segments',
    'hp_repositories',
    'hp_sources',
    'hp_citations',
    'hp_citation_links',
    'hp_notes',
    'hp_note_links',
    'hp_media_objects',
    'hp_media_links'
];

echo "<h2>Expected vs Actual</h2>";
echo "<p>Expected: <strong>" . count($expected) . "</strong> tables</p>";
echo "<p>Found: <strong>" . count($tables) . "</strong> tables</p>";

if (count($tables) === count($expected)) {
    echo "<p style='color: green; font-size: 18px;'>🎉 All tables present!</p>";
} else {
    echo "<h3>Missing Tables:</h3>";
    echo "<ul>";
    foreach ($expected as $expected_table) {
        $full_name = $wpdb->prefix . $expected_table;
        if (!in_array($full_name, $tables)) {
            echo "<li style='color: red;'>❌ " . $full_name . "</li>";
        }
    }
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='" . admin_url('plugins.php') . "'>← Plugins Page</a> | ";
echo "<a href='" . plugin_dir_url(__FILE__) . "sql-debug.php'>SQL Debug</a></p>";
?>