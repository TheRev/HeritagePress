<?php
// Simple script to check HeritagePress tables
require_once('../../../wp-config.php');

global $wpdb;

echo "Checking HeritagePress tables...\n";
echo "WordPress database prefix: " . $wpdb->prefix . "\n";

// Get all HeritagePress tables
$tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");

echo "Found " . count($tables) . " HeritagePress tables:\n";
foreach ($tables as $table) {
    echo "- $table\n";
}

// List expected tables
$expected_tables = [
    'hp_media_links',
    'hp_aliases',
    'hp_ages',
    'hp_relationships',
    'hp_gedzip_archives',
    'hp_gedzip_files',
    'hp_calendar_systems',
    'hp_calendar_dates',
    'hp_extended_characters',
    'hp_extended_character_mappings',
    'hp_trees',
    'hp_individuals',
    'hp_names',
    'hp_families',
    'hp_family_links',
    'hp_events',
    'hp_event_links',
    'hp_places',
    'hp_event_types',
    'hp_event_roles',
    'hp_family_groups',
    'hp_family_group_members',
    'hp_dna_tests',
    'hp_dna_matches',
    'hp_dna_segments',
    'hp_citations',
    'hp_sources',
    'hp_repositories',
    'hp_source_citations',
    'hp_source_repository_links',
    'hp_media',
    'hp_notes'
];

echo "\nExpected 32 tables, found " . count($tables) . " tables.\n";

$missing_tables = [];
foreach ($expected_tables as $expected) {
    $full_table_name = $wpdb->prefix . $expected;
    if (!in_array($full_table_name, $tables)) {
        $missing_tables[] = $expected;
    }
}

if (!empty($missing_tables)) {
    echo "\nMissing tables:\n";
    foreach ($missing_tables as $missing) {
        echo "- " . $wpdb->prefix . $missing . "\n";
    }
}

echo "\nDone.\n";
?>