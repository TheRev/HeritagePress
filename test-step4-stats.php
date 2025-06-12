<?php
/**
 * Test to verify Step 4 statistics fix
 */

// Load WordPress
require_once('../../../../wp-config.php');

echo "<h1>🔍 Step 4 Statistics Debugging</h1>\n";

// Check for recent import progress files
$upload_info = wp_upload_dir();
$heritagepress_dir = $upload_info['basedir'] . '/heritagepress/gedcom/';

echo "<h2>Progress Files Check:</h2>\n";
echo "<p>Looking in: $heritagepress_dir</p>\n";

if (is_dir($heritagepress_dir)) {
    $progress_files = glob($heritagepress_dir . '*_progress.json');
    echo "<p>Progress files found: " . count($progress_files) . "</p>\n";

    if (!empty($progress_files)) {
        // Sort by modification time, newest first
        usort($progress_files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        echo "<h3>Recent Progress Files:</h3>\n";
        $count = 0;
        foreach ($progress_files as $file) {
            if ($count >= 3)
                break; // Only show 3 most recent
            $basename = basename($file);
            $mtime = date('Y-m-d H:i:s', filemtime($file));
            echo "<h4>$basename (Modified: $mtime)</h4>\n";

            $content = file_get_contents($file);
            $data = json_decode($content, true);

            if ($data) {
                echo "<p><strong>Status:</strong> " . ($data['completed'] ? 'Completed' : 'In Progress') . "</p>\n";
                echo "<p><strong>Operation:</strong> " . ($data['operation'] ?? 'Unknown') . "</p>\n";

                if (isset($data['stats'])) {
                    echo "<p><strong>Statistics Found:</strong></p>\n";
                    echo "<ul>\n";
                    foreach ($data['stats'] as $key => $value) {
                        if (is_array($value)) {
                            echo "<li>$key: " . count($value) . " items</li>\n";
                        } else {
                            echo "<li>$key: $value</li>\n";
                        }
                    }
                    echo "</ul>\n";
                } else {
                    echo "<p style='color: orange;'>⚠️ No stats found in this progress file</p>\n";
                }

                echo "<details><summary>Raw Data</summary><pre>" . htmlspecialchars(print_r($data, true)) . "</pre></details>\n";
            } else {
                echo "<p style='color: red;'>❌ Invalid JSON in file</p>\n";
            }
            $count++;
        }
    } else {
        echo "<p style='color: orange;'>No progress files found</p>\n";
    }
} else {
    echo "<p style='color: red;'>Directory does not exist: $heritagepress_dir</p>\n";
}

echo "<h2>Database Statistics Check:</h2>\n";
global $wpdb;

// Get all trees with data
$trees_with_data = $wpdb->get_results("
    SELECT t.id, t.title, 
           (SELECT COUNT(*) FROM {$wpdb->prefix}hp_individuals WHERE tree_id = t.id) as individuals,
           (SELECT COUNT(*) FROM {$wpdb->prefix}hp_families WHERE tree_id = t.id) as families,
           (SELECT COUNT(*) FROM {$wpdb->prefix}hp_sources WHERE tree_id = t.id) as sources
    FROM {$wpdb->prefix}hp_trees t 
    ORDER BY t.id
");

if (!empty($trees_with_data)) {
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Tree ID</th><th>Title</th><th>Individuals</th><th>Families</th><th>Sources</th></tr>\n";
    foreach ($trees_with_data as $tree) {
        echo "<tr>";
        echo "<td>{$tree->id}</td>";
        echo "<td>" . esc_html($tree->title) . "</td>";
        echo "<td>{$tree->individuals}</td>";
        echo "<td>{$tree->families}</td>";
        echo "<td>{$tree->sources}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p>No trees found</p>\n";
}

echo "<h2>GedcomServiceTNG Statistics Format:</h2>\n";
echo "<p>The system uses GedcomServiceTNG which returns stats in this format:</p>\n";
echo "<ul>\n";
echo "<li><strong>people</strong> (not 'individuals')</li>\n";
echo "<li><strong>families</strong></li>\n";
echo "<li><strong>sources</strong></li>\n";
echo "<li><strong>repositories</strong></li>\n";
echo "<li><strong>notes</strong></li>\n";
echo "<li><strong>media</strong></li>\n";
echo "<li><strong>errors</strong></li>\n";
echo "</ul>\n";

echo "<h2>✅ Fixes Applied:</h2>\n";
echo "<ol>\n";
echo "<li>✅ ImportHandler now stores actual import statistics in progress file</li>\n";
echo "<li>✅ Added duration calculation with start_time tracking</li>\n";
echo "<li>✅ Step 4 template now handles both 'people' and 'individuals' field names</li>\n";
echo "<li>✅ Added comprehensive debugging logs for statistics retrieval</li>\n";
echo "<li>✅ Enhanced fallback database querying for real counts</li>\n";
echo "</ol>\n";

echo "<h2>🧪 Test Instructions:</h2>\n";
echo "<ol>\n";
echo "<li>Perform a new GEDCOM import</li>\n";
echo "<li>Check the progress file is created with real statistics</li>\n";
echo "<li>Verify Step 4 shows the actual import counts, not zeros</li>\n";
echo "<li>Check the error log for 'Step 4:' entries to see what data source is being used</li>\n";
echo "</ol>\n";
?>