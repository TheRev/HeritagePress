<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';

global $wpdb;
$trees_table = $wpdb->prefix . 'hp_trees';

// Get column info
$columns = $wpdb->get_results("DESCRIBE $trees_table");
$primary_key = '';
foreach ($columns as $col) {
    if ($col->Key == 'PRI') {
        $primary_key = $col->Field;
        break;
    }
}

echo "Primary key column: $primary_key\n";

// Test tree data
$trees = $wpdb->get_results("SELECT * FROM $trees_table LIMIT 1");
if ($trees) {
    echo "Sample tree object:\n";
    print_r($trees[0]);
}
?>