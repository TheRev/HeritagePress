<?php
/**
 * Complete TNG Schema Extraction and Database Synchronization
 * This script extracts ALL TNG table definitions and ensures our database matches exactly
 */

// Load WordPress
require_once('../../../wp-config.php');

if (!current_user_can('manage_options')) {
    die('Access denied.');
}

global $wpdb;

echo "<h1>Complete TNG Schema Synchronization</h1>";

// Read the TNG tabledefs.php file
$tng_file = __DIR__ . '/references/tng/tabledefs.php';
if (!file_exists($tng_file)) {
    die("TNG tabledefs.php not found at: $tng_file");
}

echo "<h2>Step 1: Extracting ALL TNG Table Definitions</h2>";

$tng_content = file_get_contents($tng_file);

// Extract all table creation statements
preg_match_all('/\$(\w+_table).*?CREATE TABLE.*?\((.*?)\)\s*ENGINE/s', $tng_content, $matches, PREG_SET_ORDER);

$tng_schema = [];
$table_mappings = [];

foreach ($matches as $match) {
    $table_var = $match[1];
    $table_definition = $match[2];

    // Convert TNG table variable to our table name
    $base_name = str_replace('_table', '', $table_var);
    $our_table = 'wp_hp_' . $base_name;

    // Store mapping
    $table_mappings[$table_var] = $our_table;

    // Parse column definitions
    $columns = [];
    $lines = explode("\n", $table_definition);

    foreach ($lines as $line) {
        $line = trim($line);
        if (
            empty($line) ||
            strpos($line, 'PRIMARY KEY') === 0 ||
            strpos($line, 'INDEX') === 0 ||
            strpos($line, 'UNIQUE') === 0 ||
            strpos($line, 'FULLTEXT') === 0
        ) {
            continue;
        }

        $line = rtrim($line, ',');

        if (preg_match('/^(\w+)\s+(.+)$/', $line, $col_match)) {
            $col_name = $col_match[1];
            $col_definition = trim($col_match[2]);
            $columns[$col_name] = $col_definition;
        }
    }

    $tng_schema[$our_table] = $columns;
}

echo "<p>✅ Extracted " . count($tng_schema) . " TNG table definitions</p>";

// List all TNG tables
echo "<h3>TNG Tables Found:</h3>";
echo "<ul>";
foreach (array_keys($tng_schema) as $table) {
    echo "<li>$table (" . count($tng_schema[$table]) . " columns)</li>";
}
echo "</ul>";

echo "<h2>Step 2: Checking Current Database Schema</h2>";

// Get current database tables
$current_tables = [];
$tables_result = $wpdb->get_results("SHOW TABLES LIKE 'wp_hp_%'");

foreach ($tables_result as $table) {
    $table_name = array_values((array) $table)[0];

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

echo "<p>✅ Found " . count($current_tables) . " current database tables</p>";

echo "<h2>Step 3: Comprehensive Comparison</h2>";

$missing_tables = [];
$missing_columns = [];
$extra_columns = [];
$type_mismatches = [];

// Check for missing tables
foreach ($tng_schema as $tng_table => $tng_columns) {
    if (!isset($current_tables[$tng_table])) {
        $missing_tables[] = $tng_table;
        continue;
    }

    $current_columns = $current_tables[$tng_table];

    // Check for missing columns
    foreach ($tng_columns as $col_name => $col_def) {
        if (!isset($current_columns[$col_name])) {
            $missing_columns[] = [
                'table' => $tng_table,
                'column' => $col_name,
                'definition' => $col_def
            ];
        }
    }

    // Check for extra columns (in our DB but not in TNG)
    foreach ($current_columns as $col_name => $col_info) {
        if (!isset($tng_columns[$col_name])) {
            $extra_columns[] = [
                'table' => $tng_table,
                'column' => $col_name,
                'type' => $col_info['type']
            ];
        }
    }

    // Check for type mismatches
    foreach ($tng_columns as $col_name => $tng_def) {
        if (isset($current_columns[$col_name])) {
            $current_type = $current_columns[$col_name]['type'];

            // Extract just the type part from TNG definition
            $tng_type = strtoupper(trim(preg_replace('/\s+(NOT\s+NULL|NULL|DEFAULT.*|AUTO_INCREMENT).*$/i', '', $tng_def)));
            $current_type_clean = strtoupper($current_type);

            if ($tng_type !== $current_type_clean) {
                $type_mismatches[] = [
                    'table' => $tng_table,
                    'column' => $col_name,
                    'tng_type' => $tng_type,
                    'current_type' => $current_type_clean
                ];
            }
        }
    }
}

// Report results
if (!empty($missing_tables)) {
    echo "<h3 style='color: red;'>❌ Missing Tables (" . count($missing_tables) . ")</h3>";
    echo "<ul>";
    foreach ($missing_tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
} else {
    echo "<h3 style='color: green;'>✅ All Required Tables Exist</h3>";
}

if (!empty($missing_columns)) {
    echo "<h3 style='color: red;'>❌ Missing Columns (" . count($missing_columns) . ")</h3>";
    foreach ($missing_columns as $col) {
        echo "<p><strong>{$col['table']}</strong>.{$col['column']}: {$col['definition']}</p>";
    }
} else {
    echo "<h3 style='color: green;'>✅ All Required Columns Exist</h3>";
}

if (!empty($extra_columns)) {
    echo "<h3 style='color: orange;'>⚠️ Extra Columns (" . count($extra_columns) . ")</h3>";
    foreach ($extra_columns as $col) {
        echo "<p><strong>{$col['table']}</strong>.{$col['column']}: {$col['type']}</p>";
    }
} else {
    echo "<h3 style='color: green;'>✅ No Extra Columns</h3>";
}

if (!empty($type_mismatches)) {
    echo "<h3 style='color: orange;'>⚠️ Type Mismatches (" . count($type_mismatches) . ")</h3>";
    foreach ($type_mismatches as $mismatch) {
        echo "<p><strong>{$mismatch['table']}</strong>.{$mismatch['column']}: TNG({$mismatch['tng_type']}) vs DB({$mismatch['current_type']})</p>";
    }
} else {
    echo "<h3 style='color: green;'>✅ All Column Types Match</h3>";
}

echo "<h2>Step 4: Generate Schema Fix Script</h2>";

$sql_fixes = [];

// Generate SQL for missing tables
foreach ($missing_tables as $table) {
    $columns_sql = [];
    foreach ($tng_schema[$table] as $col_name => $col_def) {
        $columns_sql[] = "\t$col_name $col_def";
    }

    $sql_fixes[] = "-- Create missing table: $table";
    $sql_fixes[] = "CREATE TABLE $table (";
    $sql_fixes[] = implode(",\n", $columns_sql);
    $sql_fixes[] = ") ENGINE=InnoDB;";
    $sql_fixes[] = "";
}

// Generate SQL for missing columns
foreach ($missing_columns as $col) {
    $sql_fixes[] = "ALTER TABLE {$col['table']} ADD COLUMN {$col['column']} {$col['definition']};";
}

if (!empty($sql_fixes)) {
    echo "<textarea style='width: 100%; height: 400px;'>";
    echo "-- SQL Script to Fix Database Schema\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    echo "USE wordpress;\n\n";
    echo implode("\n", $sql_fixes);
    echo "</textarea>";

    // Save to file
    $sql_content = "-- SQL Script to Fix Database Schema\n";
    $sql_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $sql_content .= "USE wordpress;\n\n";
    $sql_content .= implode("\n", $sql_fixes);

    file_put_contents(__DIR__ . '/complete-schema-fix.sql', $sql_content);
    echo "<p>✅ SQL script saved to: complete-schema-fix.sql</p>";
}

echo "<h2>Step 5: Key GEDCOM Import Tables Analysis</h2>";

$gedcom_tables = ['wp_hp_people', 'wp_hp_families', 'wp_hp_sources', 'wp_hp_repositories', 'wp_hp_media', 'wp_hp_children', 'wp_hp_xnotes'];

foreach ($gedcom_tables as $table) {
    echo "<h4>$table</h4>";
    if (isset($current_tables[$table])) {
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr><th>Column</th><th>Current Type</th><th>TNG Type</th><th>Status</th></tr>";

        if (isset($tng_schema[$table])) {
            $all_columns = array_unique(array_merge(
                array_keys($current_tables[$table]),
                array_keys($tng_schema[$table])
            ));

            foreach ($all_columns as $col) {
                $current_type = isset($current_tables[$table][$col]) ? $current_tables[$table][$col]['type'] : '❌ MISSING';
                $tng_type = isset($tng_schema[$table][$col]) ? $tng_schema[$table][$col] : '⚠️ EXTRA';

                $status = '✅ OK';
                if (!isset($current_tables[$table][$col])) {
                    $status = '❌ MISSING';
                } elseif (!isset($tng_schema[$table][$col])) {
                    $status = '⚠️ EXTRA';
                } else {
                    // Check type match
                    $tng_clean = strtoupper(trim(preg_replace('/\s+(NOT\s+NULL|NULL|DEFAULT.*|AUTO_INCREMENT).*$/i', '', $tng_type)));
                    $current_clean = strtoupper($current_type);
                    if ($tng_clean !== $current_clean) {
                        $status = '⚠️ TYPE MISMATCH';
                    }
                }

                echo "<tr>";
                echo "<td>$col</td>";
                echo "<td>$current_type</td>";
                echo "<td>" . (is_string($tng_type) ? $tng_type : $tng_type) . "</td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4'>⚠️ Table not found in TNG schema</td></tr>";
        }

        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Table does not exist in database!</p>";
    }
}

echo "<h2>Analysis Complete</h2>";
echo "<p>Next steps:</p>";
echo "<ol>";
echo "<li>Run the generated SQL script to fix missing tables/columns</li>";
echo "<li>Update GEDCOM service to use correct column names</li>";
echo "<li>Test GEDCOM import functionality</li>";
echo "</ol>";
?>