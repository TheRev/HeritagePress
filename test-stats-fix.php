<?php
/**
 * Quick test for Step 4 statistics debugging - simplified version
 */

// Load WordPress
require_once('../../../../wp-config.php');

echo "<h1>🔧 Step 4 Statistics Fix Test</h1>\n";

global $wpdb;

echo "<h2>Current Database Status:</h2>\n";

// Check trees and their data counts
$trees = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_trees ORDER BY id");
echo "<p>Total trees: " . count($trees) . "</p>\n";

if (!empty($trees)) {
    echo "<table border='1'>\n";
    echo "<tr><th>ID</th><th>Title</th><th>Individuals</th><th>Families</th><th>Sources</th></tr>\n";

    foreach ($trees as $tree) {
        $individuals_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_individuals WHERE tree_id = {$tree->id}");
        $families_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_families WHERE tree_id = {$tree->id}");
        $sources_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hp_sources WHERE tree_id = {$tree->id}");

        echo "<tr>";
        echo "<td>{$tree->id}</td>";
        echo "<td>" . htmlspecialchars($tree->title) . "</td>";
        echo "<td>{$individuals_count}</td>";
        echo "<td>{$families_count}</td>";
        echo "<td>{$sources_count}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
}

echo "<h2>Progress Files Status:</h2>\n";

$upload_info = wp_upload_dir();
$heritagepress_dir = $upload_info['basedir'] . '/heritagepress/gedcom/';

if (is_dir($heritagepress_dir)) {
    $progress_files = glob($heritagepress_dir . '*_progress.json');
    echo "<p>Progress files found: " . count($progress_files) . "</p>\n";

    if (!empty($progress_files)) {
        // Get the most recent progress file
        usort($progress_files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $latest_file = $progress_files[0];
        echo "<h3>Latest Progress File: " . basename($latest_file) . "</h3>\n";

        $content = file_get_contents($latest_file);
        $data = json_decode($content, true);

        if ($data) {
            echo "<p><strong>Completed:</strong> " . ($data['completed'] ? 'Yes' : 'No') . "</p>\n";

            if (isset($data['stats'])) {
                echo "<p><strong>Statistics in file:</strong></p>\n";
                echo "<pre>" . htmlspecialchars(print_r($data['stats'], true)) . "</pre>\n";
            } else {
                echo "<p style='color: red;'>❌ No stats key found in progress file</p>\n";
            }
        } else {
            echo "<p style='color: red;'>❌ Could not parse JSON</p>\n";
        }
    }
} else {
    echo "<p style='color: red;'>❌ GEDCOM directory not found</p>\n";
}

echo "<h2>✅ Applied Fixes Summary:</h2>\n";
echo "<ol>\n";
echo "<li><strong>ImportHandler.php:</strong> Now stores actual import statistics in progress file</li>\n";
echo "<li><strong>ImportHandler.php:</strong> Added start_time for duration calculation</li>\n";
echo "<li><strong>step4-results.php:</strong> Handles both 'people' and 'individuals' field names (TNG vs standard)</li>\n";
echo "<li><strong>step4-results.php:</strong> Enhanced logging and fallback database queries</li>\n";
echo "</ol>\n";

echo "<h2>🎯 What Should Happen Now:</h2>\n";
echo "<ol>\n";
echo "<li>When you do a new import, the statistics should be stored in the progress file</li>\n";
echo "<li>Step 4 should read these real statistics instead of showing zeros</li>\n";
echo "<li>If no progress file, Step 4 will query the database for actual counts</li>\n";
echo "<li>Check the error log for 'Step 4:' entries to see the data source being used</li>\n";
echo "</ol>\n";

echo "<h2>🧪 Test Next:</h2>\n";
echo "<p>Try importing a GEDCOM file and check if Step 4 shows the correct statistics.</p>\n";
?>