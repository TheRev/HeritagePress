<?php
/**
 * Check current database state
 */

// WordPress environment
define('WP_USE_THEMES', false);
require_once('c:\MAMP\htdocs\wordpress\wp-config.php');

echo "<h1>HeritagePress Database Status</h1>\n";

global $wpdb;

// Check each table
$tables = [
    'hp_trees' => 'Trees',
    'hp_individuals' => 'Individuals',
    'hp_families' => 'Families',
    'hp_sources' => 'Sources',
    'hp_notes' => 'Notes'
];

foreach ($tables as $table => $label) {
    $full_table = $wpdb->prefix . $table;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
    echo "<p><strong>$label:</strong> $count records</p>\n";

    if ($count > 0 && $table === 'hp_individuals') {
        // Show sample individuals
        $samples = $wpdb->get_results("SELECT given_names, surname, gender FROM $full_table LIMIT 5");
        echo "<ul>\n";
        foreach ($samples as $sample) {
            $name = trim(($sample->given_names ?? '') . ' ' . ($sample->surname ?? ''));
            echo "<li>" . htmlspecialchars($name ?: 'Unknown') . " (" . ($sample->gender ?? 'U') . ")</li>\n";
        }
        echo "</ul>\n";
    }
}

echo "<h2>Import Test Status</h2>\n";
echo "<p>If you see records above, a previous GEDCOM import test was successful!</p>\n";
echo "<p><a href='http://localhost/wordpress/wp-admin/admin.php?page=heritagepress-individuals'>View Individuals in Admin →</a></p>\n";
echo "<p><a href='http://localhost/wordpress/wp-admin/admin.php?page=heritagepress-importexport'>Try Import Interface →</a></p>\n";
?>