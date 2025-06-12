<?php
/**
 * Extract exact TNG table structures and generate perfect schema
 */

// Load WordPress
require_once('../../../wp-config.php');

if (!current_user_can('manage_options')) {
    die('Access denied.');
}

echo "<h1>TNG Schema Extraction and Database Sync</h1>";

// Read TNG tabledefs.php
$tng_file = __DIR__ . '/references/genealogy-reference/tabledefs.php';
if (!file_exists($tng_file)) {
    die("TNG tabledefs.php not found at: $tng_file");
}

$tng_content = file_get_contents($tng_file);

// Extract all CREATE TABLE statements
preg_match_all('/\$(\w+_table).*?CREATE TABLE[^(]*\((.*?)\)\s*ENGINE/s', $tng_content, $matches, PREG_SET_ORDER);

$tng_tables = [];
echo "<h2>Extracted TNG Tables:</h2>";

foreach ($matches as $match) {
    $table_var = $match[1];
    $table_definition = $match[2];

    // Convert table variable name to our naming convention
    $table_name = 'wp_hp_' . str_replace('_table', '', $table_var);

    // Clean up the table definition
    $table_definition = str_replace(['\n', '\t'], ["\n", "\t"], $table_definition);
    $table_definition = trim($table_definition);

    $tng_tables[$table_name] = $table_definition;

    echo "<h3>$table_name</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($table_definition);
    echo "</pre>";
}

echo "<h2>Generate Complete Schema SQL</h2>";

$schema_sql = "-- Complete HeritagePress Database Schema\n";
$schema_sql .= "-- Based on TNG " . date('Y-m-d H:i:s') . "\n\n";

$schema_sql .= "USE wordpress;\n\n";

foreach ($tng_tables as $table_name => $definition) {
    $schema_sql .= "-- Table: $table_name\n";
    $schema_sql .= "DROP TABLE IF EXISTS $table_name;\n";
    $schema_sql .= "CREATE TABLE $table_name (\n";
    $schema_sql .= $definition . "\n";
    $schema_sql .= ") ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";
}

// Save schema to file
file_put_contents(__DIR__ . '/complete-tng-schema.sql', $schema_sql);

echo "<h3>Complete Schema Generated</h3>";
echo "<p>Schema saved to: <strong>complete-tng-schema.sql</strong></p>";

echo "<h3>Schema Preview:</h3>";
echo "<textarea style='width: 100%; height: 400px;'>";
echo htmlspecialchars($schema_sql);
echo "</textarea>";

// Now check what we have vs what we need
global $wpdb;

echo "<h2>Current Database vs TNG Schema</h2>";

$current_tables = [];
$tables_result = $wpdb->get_results("SHOW TABLES LIKE 'wp_hp_%'");

echo "<h3>Current Tables:</h3>";
echo "<ul>";
foreach ($tables_result as $table) {
    $table_name = array_values((array) $table)[0];
    $current_tables[] = $table_name;
    echo "<li>$table_name</li>";
}
echo "</ul>";

echo "<h3>TNG Tables:</h3>";
echo "<ul>";
foreach (array_keys($tng_tables) as $table_name) {
    $exists = in_array($table_name, $current_tables);
    $status = $exists ? "✅" : "❌";
    echo "<li>$status $table_name</li>";
}
echo "</ul>";

// Generate update SQL for existing database
$missing_tables = array_diff(array_keys($tng_tables), $current_tables);
$extra_tables = array_diff($current_tables, array_keys($tng_tables));

echo "<h2>Database Update Required</h2>";

if (!empty($missing_tables)) {
    echo "<h3>Missing Tables (will be created):</h3>";
    echo "<ul>";
    foreach ($missing_tables as $table) {
        echo "<li style='color: red;'>$table</li>";
    }
    echo "</ul>";
}

if (!empty($extra_tables)) {
    echo "<h3>Extra Tables (will be kept):</h3>";
    echo "<ul>";
    foreach ($extra_tables as $table) {
        echo "<li style='color: orange;'>$table</li>";
    }
    echo "</ul>";
}

// Generate incremental update SQL
$update_sql = "-- Incremental Database Update\n";
$update_sql .= "-- Run this to add missing tables\n\n";

foreach ($missing_tables as $table_name) {
    if (isset($tng_tables[$table_name])) {
        $update_sql .= "-- Create missing table: $table_name\n";
        $update_sql .= "CREATE TABLE IF NOT EXISTS $table_name (\n";
        $update_sql .= $tng_tables[$table_name] . "\n";
        $update_sql .= ") ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";
    }
}

file_put_contents(__DIR__ . '/database-update.sql', $update_sql);

echo "<h3>Update SQL Generated</h3>";
echo "<p>Update SQL saved to: <strong>database-update.sql</strong></p>";

echo "<h3>Update SQL Preview:</h3>";
echo "<textarea style='width: 100%; height: 200px;'>";
echo htmlspecialchars($update_sql);
echo "</textarea>";

echo "<h2>Key Tables for GEDCOM Import</h2>";

$key_tables = ['wp_hp_people', 'wp_hp_families', 'wp_hp_sources', 'wp_hp_repositories', 'wp_hp_media', 'wp_hp_children'];

foreach ($key_tables as $table) {
    if (in_array($table, $current_tables)) {
        echo "<p style='color: green;'>✅ $table exists</p>";

        // Show current structure
        $columns = $wpdb->get_results("DESCRIBE $table");
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col->Field}</td><td>{$col->Type}</td><td>{$col->Null}</td><td>{$col->Key}</td><td>{$col->Default}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ $table missing</p>";
    }
}

?>