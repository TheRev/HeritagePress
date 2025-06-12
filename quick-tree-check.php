<?php
/**
 * Quick tree structure check
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';

global $wpdb;

$trees_table = $wpdb->prefix . 'hp_trees';
$columns = $wpdb->get_results("DESCRIBE $trees_table");

echo "Table structure:\n";
foreach ($columns as $col) {
    if ($col->Key == 'PRI') {
        echo "PRIMARY KEY: {$col->Field} ({$col->Type})\n";
    }
}

// Get a sample tree to see the data structure
$sample_tree = $wpdb->get_row("SELECT * FROM $trees_table LIMIT 1");
if ($sample_tree) {
    echo "\nSample tree properties:\n";
    foreach (get_object_vars($sample_tree) as $prop => $value) {
        echo "$prop: $value\n";
    }
} else {
    echo "\nNo trees found - creating sample tree...\n";
    $result = $wpdb->insert(
        $trees_table,
        array(
            'title' => 'Debug Test Tree',
            'description' => 'Created for column debugging',
            'privacy_level' => 0,
            'owner_user_id' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        )
    );
    if ($result) {
        echo "Sample tree created with ID: " . $wpdb->insert_id . "\n";

        $sample_tree = $wpdb->get_row("SELECT * FROM $trees_table WHERE title = 'Debug Test Tree'");
        if ($sample_tree) {
            echo "\nSample tree properties:\n";
            foreach (get_object_vars($sample_tree) as $prop => $value) {
                echo "$prop: $value\n";
            }
        }
    } else {
        echo "Failed to create sample tree: " . $wpdb->last_error . "\n";
    }
}
?>