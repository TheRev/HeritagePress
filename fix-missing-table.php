<?php
/**
 * Fix Missing Table Test
 * Create the missing wp_hp_extended_characters table
 */

// Load WordPress
$wp_load_path = realpath(__DIR__ . '/../../../wp-load.php');
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
    echo "<h1>Fix Missing Table</h1>";
} else {
    die('WordPress not found');
}

global $wpdb;
echo "<p>Database prefix: <strong>" . $wpdb->prefix . "</strong></p>";

// Check current table count
$tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "<p>Current HeritagePress tables: <strong>" . count($tables) . "</strong></p>";

// Check if the problematic table exists
$missing_table = $wpdb->prefix . 'hp_extended_characters';
$exists = in_array($missing_table, $tables);

if ($exists) {
    echo "<p style='color: green;'>✓ Table $missing_table already exists!</p>";
} else {
    echo "<p style='color: red;'>❌ Table $missing_table is missing. Creating it now...</p>";

    // Create the table with fixed SQL
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$missing_table} (
        id int(11) NOT NULL AUTO_INCREMENT,
        char_value varchar(10) NOT NULL,
        unicode varchar(10) NOT NULL,
        description varchar(255) DEFAULT NULL,
        created_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY char_value (char_value)
    ) ENGINE = InnoDB $charset_collate;";

    echo "<h3>SQL to execute:</h3>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";

    if (!function_exists('dbDelta')) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    try {
        $result = dbDelta($sql);
        echo "<h3>dbDelta result:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($result, true)) . "</pre>";

        // Check if table was created
        $tables_after = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
        $new_count = count($tables_after);

        if ($new_count > count($tables)) {
            echo "<p style='color: green; font-size: 18px;'>🎉 SUCCESS! Table created. Total tables: $new_count</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Table creation may have failed. Still $new_count tables.</p>";
        }

    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Final summary
$final_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "<h2>Final Status</h2>";
echo "<p><strong>Total HeritagePress tables: " . count($final_tables) . " / 32</strong></p>";

if (count($final_tables) === 32) {
    echo "<p style='color: green; font-size: 20px;'>🎉 ALL 32 TABLES CREATED SUCCESSFULLY!</p>";
} else {
    echo "<p style='color: orange;'>Missing " . (32 - count($final_tables)) . " table(s)</p>";
}

echo "<hr>";
echo "<p><a href='" . admin_url('plugins.php') . "'>← Plugins Page</a> | ";
echo "<a href='" . plugin_dir_url(__FILE__) . "table-status.php'>Table Status</a></p>";
?>