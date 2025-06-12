<?php
/**
 * Direct Database Column Fix - Simple and Fast
 */

// Load WordPress
require_once('../../../wp-config.php');

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied.');
}

global $wpdb;

echo "<h1>Quick Column Fix</h1>";

// Just run the SQL directly - simple and fast
$fixes = [
    "ALTER TABLE wp_hp_people ADD COLUMN person_id VARCHAR(50) NOT NULL AFTER gedcom",
    "ALTER TABLE wp_hp_families ADD COLUMN family_id VARCHAR(50) NOT NULL AFTER gedcom",
    "ALTER TABLE wp_hp_sources ADD COLUMN source_id VARCHAR(50) NOT NULL AFTER gedcom",
    "ALTER TABLE wp_hp_repositories ADD COLUMN name VARCHAR(255) NOT NULL AFTER repo_id",
    "ALTER TABLE wp_hp_media ADD COLUMN media_id VARCHAR(50) NOT NULL AFTER gedcom"
];

foreach ($fixes as $sql) {
    echo "<p>Running: <code>$sql</code></p>";
    $result = $wpdb->query($sql);

    if ($result !== false) {
        echo "<p style='color: green;'>✅ Success</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $wpdb->last_error . "</p>";
    }
}

echo "<h2>Done!</h2>";
echo "<p><a href='" . admin_url('admin.php?page=heritagepress-import') . "'>Test Import Again</a></p>";
?>