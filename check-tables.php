<?php
/**
 * Test script to verify database tables
 * 
 * This script checks if the HeritagePress database tables have been created
 */

// Load WordPress - using absolute path to ensure we find it
require_once 'C:/MAMP/htdocs/wordpress/wp-load.php';

// Check for admin privileges
if (!current_user_can('manage_options')) {
    die('You do not have sufficient permissions to access this page.');
}

global $wpdb;

echo '<h1>HeritagePress Database Tables Check</h1>';

// Get list of expected tables
$expected_tables = [
    'hp_individuals',
    'hp_names',
    'hp_families',
    'hp_family_links',
    'hp_events',
    'hp_event_links',
    'hp_places',
    'hp_event_types',
    'hp_trees',
    'hp_repositories',
    'hp_sources',
    'hp_citations',
    'hp_citation_links',
    'hp_notes',
    'hp_note_links',
    'hp_media_objects',
    'hp_media_links',
    'hp_aliases',
    'hp_ages',
    'hp_relationships',
    // New GEDCOM 7 tables
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
];

$total_tables = count($expected_tables);
$tables_found = 0;
$missing_tables = [];

echo "<p>Checking for $total_tables HeritagePress database tables...</p>";
echo '<ul>';

foreach ($expected_tables as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") == $full_table_name;

    if ($exists) {
        echo "<li style='color: green;'>✓ Table $full_table_name exists</li>";
        $tables_found++;
    } else {
        echo "<li style='color: red;'>✗ Table $full_table_name is missing</li>";
        $missing_tables[] = $full_table_name;
    }
}

echo '</ul>';

// Summary
echo '<h2>Summary</h2>';
if ($tables_found == $total_tables) {
    echo "<p style='color: green;'>All $total_tables tables are present. The database setup is complete.</p>";
} else {
    echo "<p style='color: red;'>Only $tables_found of $total_tables tables are present. There are " . count($missing_tables) . " missing tables.</p>";

    // Show options
    echo '<h3>Options to fix missing tables:</h3>';
    echo '<ol>';
    echo '<li>Deactivate and reactivate the plugin from the WordPress Plugins page</li>';
    echo '<li>Run the <a href="' . plugin_dir_url(__FILE__) . 'create-database.php">Database Creation Script</a></li>';
    echo '<li>Run the <a href="' . plugin_dir_url(__FILE__) . 'reactivate-plugin.php">Plugin Reactivation Script</a></li>';
    echo '</ol>';
}

// Database Options
echo '<h2>HeritagePress Database Options</h2>';
$db_version = get_option('heritagepress_db_version', 'Not set');
echo "<p>Database Version: $db_version</p>";

// Admin Links
echo '<p><a href="' . admin_url('admin.php?page=heritagepress') . '">Go to HeritagePress Admin</a></p>';
echo '<p><a href="' . admin_url('plugins.php') . '">Manage Plugins</a></p>';
