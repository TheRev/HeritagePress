<?php
/**
 * Create sample tree data with correct column names
 */

require_once('../../../../../../wp-config.php');

echo "<h1>🌳 Creating Sample Tree Data</h1>\n";

global $wpdb;

$table_name = $wpdb->prefix . 'hp_trees';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

if (!$table_exists) {
    echo "<p style='color: red;'>❌ Table {$table_name} does not exist. Please install the schema first.</p>\n";
    exit;
}

echo "<p>✅ Table {$table_name} exists</p>\n";

// Check current data
$existing_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
echo "<p>Current tree count: {$existing_count}</p>\n";

if ($existing_count > 0) {
    echo "<h2>Existing Trees:</h2>\n";
    $existing_trees = $wpdb->get_results("SELECT * FROM {$table_name}");
    foreach ($existing_trees as $tree) {
        echo "<p>- ID: {$tree->id}, Title: " . (isset($tree->title) ? $tree->title : 'N/A') . "</p>\n";
    }
}

// Add sample trees if none exist
if ($existing_count == 0) {
    echo "<h2>Creating Sample Trees...</h2>\n";

    $sample_trees = [
        [
            'title' => 'Smith Family Tree',
            'description' => 'Complete genealogy of the Smith family lineage',
            'privacy_level' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ],
        [
            'title' => 'Johnson Family Tree',
            'description' => 'Johnson family history and ancestry records',
            'privacy_level' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ],
        [
            'title' => 'Williams Heritage',
            'description' => 'Williams family genealogical research',
            'privacy_level' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]
    ];

    foreach ($sample_trees as $tree_data) {
        $result = $wpdb->insert($table_name, $tree_data);

        if ($result) {
            echo "<p style='color: green;'>✅ Created tree: {$tree_data['title']}</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Failed to create tree: {$tree_data['title']}</p>\n";
            echo "<p>Error: " . $wpdb->last_error . "</p>\n";
        }
    }
} else {
    echo "<p>Trees already exist, skipping creation.</p>\n";
}

// Test the get_trees query
echo "<h2>Testing get_trees Query</h2>\n";

$test_query = "SELECT * FROM `{$table_name}` WHERE 1=1 ORDER BY `title` ASC";
echo "<p><strong>Query:</strong> {$test_query}</p>\n";

$result = $wpdb->get_results($test_query);
$error = $wpdb->last_error;

if ($error) {
    echo "<p style='color: red;'><strong>Error:</strong> {$error}</p>\n";
} else {
    echo "<p style='color: green;'><strong>Success:</strong> Query returned " . count($result) . " rows</p>\n";

    if (!empty($result)) {
        echo "<h3>Trees Found:</h3>\n";
        foreach ($result as $tree) {
            echo "<p>- ID: {$tree->id}, Title: {$tree->title}, Privacy: {$tree->privacy_level}</p>\n";
        }
    }
}

echo "<h2>✅ Complete!</h2>\n";
echo "<p>Tree data is ready for GEDCOM import/export testing.</p>\n";
?>