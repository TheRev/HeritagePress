<?php
/**
 * Simple HeritagePress Table Check
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting simple table check...\n";

// Load WordPress
$wp_config = dirname(__FILE__) . '/../../../../wp-config.php';
if (!file_exists($wp_config)) {
    die("WordPress config not found\n");
}

require_once $wp_config;

global $wpdb;

echo "WordPress loaded, checking tables...\n";

// Check a few key tables
$tables_to_check = [
    'hp_individuals',
    'hp_families',
    'hp_media_links',
    'hp_gedcom_files',
    'hp_compliance_checks'
];

$prefix = $wpdb->prefix;
echo "Database prefix: {$prefix}\n\n";

foreach ($tables_to_check as $table) {
    $full_name = $prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_name}'");
    echo ($exists ? "✓" : "✗") . " {$full_name}\n";
}

echo "\nDone.\n";
