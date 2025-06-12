<?php
/**
 * Comprehensive TNG Schema vs Database Comparison
 * This script extracts ALL table definitions from TNG and compares with our current database
 */

// Load WordPress
require_once('../../../wp-config.php');

if (!current_user_can('manage_options')) {
    die('Access denied.');
}

global $wpdb;

echo "<h1>Comprehensive TNG Schema vs Database Comparison</h1>";

// First, let's extract all TNG table definitions
echo "<h2>Step 1: Extracting TNG Table Definitions</h2>";

$tng_file = __DIR__ . '/references/genealogy-reference/tabledefs.php';
if (!file_exists($tng_file)) {
    die("TNG tabledefs.php not found at: $tng_file");
}

$tng_content = file_get_contents($tng_file);

// Extract table definitions using regex
preg_match_all('/\$(\w+_table).*?CREATE TABLE.*?\((.*?)\) ENGINE/s', $tng_content, $matches, PREG_SET_ORDER);

$tng_tables = [];
foreach ($matches as $match) {
    $table_var = $match[1];
    $table_definition = $match[2];

    // Extract table name from variable (remove _table suffix and add wp_hp_ prefix)
    $table_name = 'wp_hp_' . str_replace('_table', '', $table_var);

    // Parse columns from definition
    $columns = [];
    $lines = explode("\n", $table_definition);

    foreach ($lines as $line) {
        $line = trim($line);
        if (
            empty($line) || strpos($line, 'PRIMARY KEY') === 0 || strpos($line, 'INDEX') === 0 ||
            strpos($line, 'UNIQUE') === 0 || strpos($line, 'FULLTEXT') === 0
        ) {
            continue;
        }

        // Remove trailing comma
        $line = rtrim($line, ',');

        // Extract column name and type
        if (preg_match('/^(\w+)\s+(.+)$/', $line, $col_match)) {
            $col_name = $col_match[1];
            $col_definition = $col_match[2];
            $columns[$col_name] = $col_definition;
        }
    }

    $tng_tables[$table_name] = $columns;
}

echo "<p>Found " . count($tng_tables) . " TNG table definitions</p>";

// Now check our current database structure
echo "<h2>Step 2: Checking Current Database Tables</h2>";

$current_tables = [];
$tables_result = $wpdb->get_results("SHOW TABLES LIKE 'wp_hp_%'");

foreach ($tables_result as $table) {
    $table_name = array_values((array) $table)[0];

    // Get column structure
    $columns_result = $wpdb->get_results("DESCRIBE $table_name");
    $columns = [];

    foreach ($columns_result as $col) {
        $columns[$col->Field] = [
            'type' => $col->Type,
            'null' => $col->Null,
            'key' => $col->Key,
            'default' => $col->Default,
            'extra' => $col->Extra
        ];
    }

    $current_tables[$table_name] = $columns;
}

echo "<p>Found " . count($current_tables) . " current database tables</p>";

// Compare structures
echo "<h2>Step 3: Detailed Comparison</h2>";

echo "<h3>Tables in TNG but missing from database:</h3>";
$missing_tables = array_diff(array_keys($tng_tables), array_keys($current_tables));
if (empty($missing_tables)) {
    echo "<p style='color: green;'>✅ All TNG tables exist in database</p>";
} else {
    echo "<ul>";
    foreach ($missing_tables as $table) {
        echo "<li style='color: red;'>❌ Missing: $table</li>";
    }
    echo "</ul>";
}

echo "<h3>Extra tables in database (not in TNG):</h3>";
$extra_tables = array_diff(array_keys($current_tables), array_keys($tng_tables));
if (empty($extra_tables)) {
    echo "<p style='color: green;'>✅ No extra tables</p>";
} else {
    echo "<ul>";
    foreach ($extra_tables as $table) {
        echo "<li style='color: orange;'>⚠️ Extra: $table</li>";
    }
    echo "</ul>";
}

echo "<h3>Column-by-Column Comparison:</h3>";

$all_issues = [];

foreach ($tng_tables as $table_name => $tng_columns) {
    if (!isset($current_tables[$table_name])) {
        continue; // Already reported as missing
    }

    $current_columns = $current_tables[$table_name];

    echo "<h4>Table: $table_name</h4>";

    // Check for missing columns
    $missing_columns = array_diff(array_keys($tng_columns), array_keys($current_columns));
    if (!empty($missing_columns)) {
        echo "<p style='color: red;'>❌ Missing columns:</p><ul>";
        foreach ($missing_columns as $col) {
            echo "<li>$col ({$tng_columns[$col]})</li>";
            $all_issues[] = "ALTER TABLE $table_name ADD COLUMN $col {$tng_columns[$col]};";
        }
        echo "</ul>";
    }

    // Check for extra columns
    $extra_columns = array_diff(array_keys($current_columns), array_keys($tng_columns));
    if (!empty($extra_columns)) {
        echo "<p style='color: orange;'>⚠️ Extra columns:</p><ul>";
        foreach ($extra_columns as $col) {
            echo "<li>$col ({$current_columns[$col]['type']})</li>";
        }
        echo "</ul>";
    }

    // Check for type mismatches
    $type_mismatches = [];
    foreach ($tng_columns as $col_name => $tng_type) {
        if (isset($current_columns[$col_name])) {
            $current_type = $current_columns[$col_name]['type'];

            // Normalize types for comparison
            $tng_type_clean = strtoupper(trim(preg_replace('/\s+(NOT\s+NULL|NULL|DEFAULT.*|AUTO_INCREMENT).*$/i', '', $tng_type)));
            $current_type_clean = strtoupper($current_type);

            // Handle common variations
            $tng_type_clean = str_replace(['VARCHAR(127)', 'VARCHAR(22)', 'VARCHAR(20)'], ['VARCHAR(127)', 'VARCHAR(22)', 'VARCHAR(20)'], $tng_type_clean);

            if ($tng_type_clean !== $current_type_clean) {
                $type_mismatches[] = "$col_name: TNG($tng_type_clean) vs DB($current_type_clean)";
            }
        }
    }

    if (!empty($type_mismatches)) {
        echo "<p style='color: orange;'>⚠️ Type mismatches:</p><ul>";
        foreach ($type_mismatches as $mismatch) {
            echo "<li>$mismatch</li>";
        }
        echo "</ul>";
    }

    if (empty($missing_columns) && empty($extra_columns) && empty($type_mismatches)) {
        echo "<p style='color: green;'>✅ Table structure matches TNG</p>";
    }
}

// Generate SQL fix script
if (!empty($all_issues)) {
    echo "<h2>Step 4: SQL Fix Script</h2>";
    echo "<textarea style='width: 100%; height: 300px;'>";
    echo "-- SQL commands to fix database schema\n";
    echo "-- Run these in phpMyAdmin or MySQL console\n\n";
    foreach ($all_issues as $sql) {
        echo "$sql\n";
    }
    echo "</textarea>";

    // Also save to file
    file_put_contents(__DIR__ . '/schema-fix-commands.sql', implode("\n", $all_issues));
    echo "<p>SQL commands also saved to: schema-fix-commands.sql</p>";
}

echo "<h2>Step 5: Key Tables for GEDCOM Import</h2>";

$key_tables = ['wp_hp_people', 'wp_hp_families', 'wp_hp_sources', 'wp_hp_repositories', 'wp_hp_media', 'wp_hp_children'];

foreach ($key_tables as $table) {
    if (isset($current_tables[$table])) {
        echo "<h4>$table columns:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

        foreach ($current_tables[$table] as $col_name => $col_info) {
            echo "<tr>";
            echo "<td>$col_name</td>";
            echo "<td>{$col_info['type']}</td>";
            echo "<td>{$col_info['null']}</td>";
            echo "<td>{$col_info['key']}</td>";
            echo "<td>{$col_info['default']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "<p style='color: red;'>❌ $table does not exist!</p>";
    }
}

echo "<h2>Comparison Complete</h2>";
?>