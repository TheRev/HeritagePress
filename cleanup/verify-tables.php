<?php
/**
 * HeritagePress Table Verification Script
 * 
 * Run this after plugin activation to verify all 32 tables were created
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting table verification...\n";

// Load WordPress
$wp_config = dirname(__FILE__) . '/../../../../wp-config.php';
if (!file_exists($wp_config)) {
    die("WordPress config not found at: $wp_config\n");
}

require_once $wp_config;

global $wpdb;

if (!$wpdb) {
    die("WordPress database connection not available\n");
}

echo "WordPress loaded successfully\n";

global $wpdb;

echo "=== HeritagePress Table Verification ===\n\n";

// Define all expected tables
$expected_tables = [
    // Core tables (9)
    'hp_individuals',
    'hp_families',
    'hp_sources',
    'hp_citations',
    'hp_events',
    'hp_places',
    'hp_media',
    'hp_repositories',
    'hp_notes',

    // GEDCOM 7 tables (9)
    'hp_gedcom_files',
    'hp_gedcom_records',
    'hp_gedcom_structures',
    'hp_gedcom_tags',
    'hp_gedcom_values',
    'hp_gedcom_cross_references',
    'hp_gedcom_extensions',
    'hp_gedcom_metadata',
    'hp_gedcom_validation',

    // Compliance tables (6)
    'hp_compliance_checks',
    'hp_compliance_issues',
    'hp_compliance_rules',
    'hp_extended_characters',
    'hp_media_links',
    'hp_calendar_conversions',

    // Documentation tables (8)
    'hp_documentation_pages',
    'hp_documentation_sections',
    'hp_documentation_links',
    'hp_user_guides',
    'hp_api_documentation',
    'hp_changelog_entries',
    'hp_configuration_options',
    'hp_system_requirements'
];

$existing_tables = [];
$missing_tables = [];

// Check each table
foreach ($expected_tables as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $result = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");

    if ($result) {
        $existing_tables[] = $table;
        echo "✓ {$full_table_name} - EXISTS\n";
    } else {
        $missing_tables[] = $table;
        echo "✗ {$full_table_name} - MISSING\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Expected tables: " . count($expected_tables) . "\n";
echo "Existing tables: " . count($existing_tables) . "\n";
echo "Missing tables: " . count($missing_tables) . "\n";

if (count($missing_tables) > 0) {
    echo "\nMISSING TABLES:\n";
    foreach ($missing_tables as $table) {
        echo "- {$wpdb->prefix}{$table}\n";
    }
}

if (count($existing_tables) === count($expected_tables)) {
    echo "\n🎉 SUCCESS: All HeritagePress tables are present!\n";
} else {
    echo "\n⚠️  WARNING: " . count($missing_tables) . " tables are missing.\n";
}

// Check if plugin is active
$active_plugins = get_option('active_plugins', []);
$is_active = false;
foreach ($active_plugins as $plugin) {
    if (strpos($plugin, 'heritagepress') !== false) {
        $is_active = true;
        break;
    }
}

echo "\nPlugin Status: " . ($is_active ? "ACTIVE" : "INACTIVE") . "\n";

echo "\n=== END VERIFICATION ===\n";
