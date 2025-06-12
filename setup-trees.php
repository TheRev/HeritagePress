<?php
/**
 * Setup script to create trees table and sample data
 */

// Load WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "Setting up hp_trees table...\n";

// Check if hp_trees table exists
$table_name = $wpdb->prefix . 'hp_trees';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if (!$table_exists) {
    echo "Creating hp_trees table...\n";

    // Create the table
    $sql = "CREATE TABLE $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        is_public tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $result = $wpdb->query($sql);
    if ($result === false) {
        echo "Error creating table: " . $wpdb->last_error . "\n";
        exit(1);
    } else {
        echo "Table created successfully!\n";
    }
} else {
    echo "hp_trees table already exists.\n";
}

// Insert sample data
echo "Inserting sample trees...\n";
$sample_trees = [
    ['name' => 'Smith Family Tree', 'description' => 'The Smith family genealogy research'],
    ['name' => 'Johnson Family Tree', 'description' => 'Johnson family lineage from 1800s'],
    ['name' => 'Williams Heritage', 'description' => 'Williams family ancestry and heritage']
];

foreach ($sample_trees as $tree) {
    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE name = %s", $tree['name']));
    if (!$existing) {
        $result = $wpdb->insert($table_name, $tree);
        if ($result) {
            echo "Inserted: " . $tree['name'] . "\n";
        } else {
            echo "Error inserting " . $tree['name'] . ": " . $wpdb->last_error . "\n";
        }
    } else {
        echo "Already exists: " . $tree['name'] . "\n";
    }
}

echo "Sample data setup complete!\n";

// Test the get_trees method
echo "\nTesting get_trees method...\n";
$trees = $wpdb->get_results("SELECT * FROM $table_name ORDER BY name ASC");
echo "Found " . count($trees) . " trees:\n";
foreach ($trees as $tree) {
    echo "  - " . $tree->name . " (ID: " . $tree->id . ")\n";
}
?>