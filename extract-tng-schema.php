<?php
/**
 * Direct TNG Schema Extraction - Extract ALL table definitions from TNG
 */

$tng_file = __DIR__ . '/references/tng/tabledefs.php';

if (!file_exists($tng_file)) {
    die("TNG tabledefs.php not found at: $tng_file");
}

echo "Reading TNG Schema from: $tng_file\n\n";

$content = file_get_contents($tng_file);

// Extract all CREATE TABLE statements
preg_match_all('/\$(\w+_table).*?CREATE TABLE.*?\((.*?)\)\s*ENGINE/s', $content, $matches, PREG_SET_ORDER);

echo "Found " . count($matches) . " table definitions:\n\n";

$all_tables = [];

foreach ($matches as $match) {
    $table_var = $match[1];
    $table_def = $match[2];

    // Convert to our naming convention
    $table_name = 'wp_hp_' . str_replace('_table', '', $table_var);

    echo "=== $table_name (from $table_var) ===\n";

    // Parse columns
    $lines = explode("\n", $table_def);
    $columns = [];

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
            $col_def = trim($col_match[2]);
            $columns[$col_name] = $col_def;
            echo "  $col_name: $col_def\n";
        }
    }

    $all_tables[$table_name] = $columns;
    echo "\n";
}

// Save complete schema
$schema_output = "<?php\n";
$schema_output .= "// Complete TNG Schema for HeritagePress\n";
$schema_output .= "// Generated: " . date('Y-m-d H:i:s') . "\n\n";
$schema_output .= "return " . var_export($all_tables, true) . ";\n";

file_put_contents(__DIR__ . '/complete-tng-schema.php', $schema_output);

echo "Complete schema saved to: complete-tng-schema.php\n";
echo "\nTotal tables: " . count($all_tables) . "\n";

// Show key tables for GEDCOM import
$key_tables = ['wp_hp_people', 'wp_hp_families', 'wp_hp_sources', 'wp_hp_repositories', 'wp_hp_media', 'wp_hp_children'];

echo "\nKey GEDCOM Import Tables:\n";
foreach ($key_tables as $table) {
    if (isset($all_tables[$table])) {
        echo "\n$table (" . count($all_tables[$table]) . " columns):\n";
        foreach ($all_tables[$table] as $col => $def) {
            echo "  $col\n";
        }
    } else {
        echo "\n$table: NOT FOUND!\n";
    }
}
?>