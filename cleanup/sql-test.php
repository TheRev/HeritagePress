<?php
/**
 * Direct SQL Test
 * Tests SQL file reading and processing
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load WordPress
$wp_load_path = realpath(__DIR__ . '/../../../wp-load.php');
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
    echo "✓ WordPress loaded successfully\n";
} else {
    die("✗ WordPress not found\n");
}

global $wpdb;
echo "Database prefix: " . $wpdb->prefix . "\n";

// Test SQL file processing
$schema_dir = __DIR__ . '/includes/Database/schema/';
$sql_files = ['core-tables.sql', 'gedcom7-tables.sql', 'compliance-tables.sql', 'documentation-tables.sql'];

foreach ($sql_files as $sql_file) {
    echo "\n--- Processing $sql_file ---\n";

    $file_path = $schema_dir . $sql_file;
    if (!file_exists($file_path)) {
        echo "✗ File not found: $file_path\n";
        continue;
    }

    $sql = file_get_contents($file_path);
    if ($sql === false) {
        echo "✗ Failed to read file: $sql_file\n";
        continue;
    }

    echo "✓ File read successfully, " . strlen($sql) . " bytes\n";

    // Replace prefix
    $sql_processed = str_replace(['{$prefix}'], $wpdb->prefix, $sql);
    echo "✓ Prefix replaced\n";

    // Split into statements
    $statements = preg_split('/;\s*\n/', $sql_processed);
    $create_statements = 0;

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (stripos($statement, 'CREATE TABLE') === 0) {
            $create_statements++;
            echo "  Found CREATE TABLE statement " . $create_statements . "\n";

            // Try to execute
            if (!function_exists('dbDelta')) {
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            }

            try {
                $result = dbDelta($statement . ";");
                if (is_array($result) && !empty($result)) {
                    echo "    ✓ Executed successfully\n";
                    foreach ($result as $table => $action) {
                        echo "      $table: $action\n";
                    }
                } else {
                    echo "    ⚠️ No result from dbDelta\n";
                }
            } catch (Exception $e) {
                echo "    ✗ Error executing: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "Total CREATE TABLE statements found: $create_statements\n";
}

// Final table count
$final_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "\n--- Final Results ---\n";
echo "Total HeritagePress tables: " . count($final_tables) . "\n";

if (count($final_tables) > 0) {
    foreach ($final_tables as $table) {
        echo "  ✓ $table\n";
    }
} else {
    echo "⚠️ No HeritagePress tables found\n";
}

echo "\nTest completed.\n";
?>