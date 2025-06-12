<?php
/**
 * Complete TNG Database Schema Synchronization
 * Compares actual TNG database structure with our wp_hp_ tables
 */

// Load WordPress
require_once('../../../wp-config.php');

if (!current_user_can('manage_options')) {
    die('Access denied.');
}

global $wpdb;

echo "<h1>Complete TNG Database Schema Synchronization</h1>";
echo "<p>Comparing actual TNG database with our HeritagePress tables</p>";

// Connect to TNG database
$tng_connection = new mysqli('localhost', 'root', 'root', 'tng');
if ($tng_connection->connect_error) {
    die("Connection to TNG database failed: " . $tng_connection->connect_error);
}

echo "<h2>Step 1: Extracting TNG Database Structure</h2>";

// Get all TNG tables
$tng_tables = [];
$result = $tng_connection->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $table_name = $row[0];

    // Get column structure
    $columns_result = $tng_connection->query("DESCRIBE $table_name");
    $columns = [];

    while ($col = $columns_result->fetch_assoc()) {
        $columns[$col['Field']] = [
            'type' => $col['Type'],
            'null' => $col['Null'],
            'key' => $col['Key'],
            'default' => $col['Default'],
            'extra' => $col['Extra']
        ];
    }

    $tng_tables[$table_name] = $columns;
}

echo "<p>✅ Found " . count($tng_tables) . " tables in TNG database</p>";

// List key TNG tables for GEDCOM import
$key_tng_tables = ['people', 'families', 'sources', 'repositories', 'media', 'children', 'xnotes', 'citations', 'events', 'places'];
echo "<h3>Key TNG Tables for GEDCOM Import:</h3>";
echo "<ul>";
foreach ($key_tng_tables as $table) {
    if (isset($tng_tables[$table])) {
        echo "<li style='color: green;'>✅ $table (" . count($tng_tables[$table]) . " columns)</li>";
    } else {
        echo "<li style='color: red;'>❌ $table (missing)</li>";
    }
}
echo "</ul>";

echo "<h2>Step 2: Checking Our HeritagePress Database Structure</h2>";

// Get our wp_hp_ tables
$our_tables = [];
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

    $our_tables[$table_name] = $columns;
}

echo "<p>✅ Found " . count($our_tables) . " wp_hp_ tables in WordPress database</p>";

echo "<h2>Step 3: Table-by-Table Comparison</h2>";

$missing_tables = [];
$missing_columns = [];
$extra_columns = [];
$type_mismatches = [];

// Map TNG tables to our wp_hp_ tables
$table_mapping = [
    'people' => 'wp_hp_people',
    'families' => 'wp_hp_families',
    'sources' => 'wp_hp_sources',
    'repositories' => 'wp_hp_repositories',
    'media' => 'wp_hp_media',
    'children' => 'wp_hp_children',
    'xnotes' => 'wp_hp_xnotes',
    'citations' => 'wp_hp_citations',
    'events' => 'wp_hp_events',
    'places' => 'wp_hp_places',
    'trees' => 'wp_hp_trees',
    'eventtypes' => 'wp_hp_eventtypes',
    'users' => 'wp_hp_users',
    'albums' => 'wp_hp_albums',
    'albumlinks' => 'wp_hp_albumlinks',
    'medialinks' => 'wp_hp_medialinks',
    'notelinks' => 'wp_hp_notelinks',
    'image_tags' => 'wp_hp_image_tags',
    'mediatypes' => 'wp_hp_mediatypes'
];

foreach ($table_mapping as $tng_table => $our_table) {
    echo "<h4>Comparing $tng_table → $our_table</h4>";

    if (!isset($tng_tables[$tng_table])) {
        echo "<p style='color: orange;'>⚠️ TNG table '$tng_table' not found</p>";
        continue;
    }

    if (!isset($our_tables[$our_table])) {
        echo "<p style='color: red;'>❌ Our table '$our_table' missing</p>";
        $missing_tables[] = ['tng' => $tng_table, 'our' => $our_table];
        continue;
    }

    $tng_columns = $tng_tables[$tng_table];
    $our_columns = $our_tables[$our_table];

    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr><th>Column</th><th>TNG Type</th><th>Our Type</th><th>Status</th></tr>";

    // Get all column names from both tables
    $all_columns = array_unique(array_merge(
        array_keys($tng_columns),
        array_keys($our_columns)
    ));

    foreach ($all_columns as $col_name) {
        $tng_type = isset($tng_columns[$col_name]) ? $tng_columns[$col_name]['type'] : '❌ MISSING';
        $our_type = isset($our_columns[$col_name]) ? $our_columns[$col_name]['type'] : '❌ MISSING';

        $status = '✅ OK';
        $row_color = '';

        if (!isset($tng_columns[$col_name])) {
            $status = '⚠️ EXTRA in our DB';
            $row_color = 'background-color: #fff3cd;';
            $extra_columns[] = ['table' => $our_table, 'column' => $col_name];
        } elseif (!isset($our_columns[$col_name])) {
            $status = '❌ MISSING in our DB';
            $row_color = 'background-color: #f8d7da;';
            $missing_columns[] = [
                'table' => $our_table,
                'column' => $col_name,
                'type' => $tng_columns[$col_name]['type'],
                'null' => $tng_columns[$col_name]['null'],
                'default' => $tng_columns[$col_name]['default'],
                'extra' => $tng_columns[$col_name]['extra']
            ];
        } elseif (strtoupper($tng_type) !== strtoupper($our_type)) {
            $status = '⚠️ TYPE MISMATCH';
            $row_color = 'background-color: #fff3cd;';
            $type_mismatches[] = [
                'table' => $our_table,
                'column' => $col_name,
                'tng_type' => $tng_type,
                'our_type' => $our_type
            ];
        }

        echo "<tr style='$row_color'>";
        echo "<td>$col_name</td>";
        echo "<td>$tng_type</td>";
        echo "<td>$our_type</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }

    echo "</table>";
}

echo "<h2>Step 4: Summary of Issues</h2>";

echo "<h3>Missing Tables (" . count($missing_tables) . ")</h3>";
if (empty($missing_tables)) {
    echo "<p style='color: green;'>✅ All required tables exist</p>";
} else {
    foreach ($missing_tables as $table) {
        echo "<p style='color: red;'>❌ {$table['our']} (from TNG {$table['tng']})</p>";
    }
}

echo "<h3>Missing Columns (" . count($missing_columns) . ")</h3>";
if (empty($missing_columns)) {
    echo "<p style='color: green;'>✅ All required columns exist</p>";
} else {
    foreach ($missing_columns as $col) {
        echo "<p style='color: red;'>❌ {$col['table']}.{$col['column']} ({$col['type']})</p>";
    }
}

echo "<h3>Type Mismatches (" . count($type_mismatches) . ")</h3>";
if (empty($type_mismatches)) {
    echo "<p style='color: green;'>✅ All column types match</p>";
} else {
    foreach ($type_mismatches as $mismatch) {
        echo "<p style='color: orange;'>⚠️ {$mismatch['table']}.{$mismatch['column']}: TNG({$mismatch['tng_type']}) vs Our({$mismatch['our_type']})</p>";
    }
}

echo "<h2>Step 5: Generate SQL Fix Script</h2>";

$sql_fixes = [];

// Generate CREATE TABLE statements for missing tables
foreach ($missing_tables as $table_info) {
    $tng_table = $table_info['tng'];
    $our_table = $table_info['our'];

    if (isset($tng_tables[$tng_table])) {
        $sql_fixes[] = "-- Create missing table: $our_table (from TNG $tng_table)";
        $sql_fixes[] = "DROP TABLE IF EXISTS $our_table;";
        $sql_fixes[] = "CREATE TABLE $our_table (";

        $column_definitions = [];
        foreach ($tng_tables[$tng_table] as $col_name => $col_info) {
            $definition = "\t$col_name {$col_info['type']}";
            if ($col_info['null'] === 'NO') {
                $definition .= ' NOT NULL';
            }
            if ($col_info['default'] !== null) {
                $definition .= " DEFAULT '{$col_info['default']}'";
            }
            if ($col_info['extra']) {
                $definition .= ' ' . $col_info['extra'];
            }
            $column_definitions[] = $definition;
        }

        $sql_fixes[] = implode(",\n", $column_definitions);
        $sql_fixes[] = ") ENGINE=InnoDB;";
        $sql_fixes[] = "";
    }
}

// Generate ALTER TABLE statements for missing columns
foreach ($missing_columns as $col_info) {
    $definition = "{$col_info['column']} {$col_info['type']}";
    if ($col_info['null'] === 'NO') {
        $definition .= ' NOT NULL';
    }
    if ($col_info['default'] !== null && $col_info['default'] !== '') {
        $definition .= " DEFAULT '{$col_info['default']}'";
    }
    if ($col_info['extra']) {
        $definition .= ' ' . $col_info['extra'];
    }

    $sql_fixes[] = "ALTER TABLE {$col_info['table']} ADD COLUMN $definition;";
}

if (!empty($sql_fixes)) {
    echo "<textarea style='width: 100%; height: 400px;'>";
    echo "-- Complete Database Schema Fix Script\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Source: TNG database comparison\n\n";
    echo "USE wordpress;\n\n";
    echo implode("\n", $sql_fixes);
    echo "</textarea>";

    // Save to file
    $sql_content = "-- Complete Database Schema Fix Script\n";
    $sql_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql_content .= "-- Source: TNG database comparison\n\n";
    $sql_content .= "USE wordpress;\n\n";
    $sql_content .= implode("\n", $sql_fixes);

    file_put_contents(__DIR__ . '/tng-database-sync.sql', $sql_content);
    echo "<p>✅ SQL script saved to: tng-database-sync.sql</p>";

    echo "<h3>Execute SQL Fixes</h3>";
    echo "<p><a href='#' onclick='if(confirm(\"Execute SQL fixes? This will modify the database.\")) { window.location.href=\"?execute_sql=1\"; }' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Execute SQL Fixes</a></p>";
}

// Execute SQL fixes if requested
if (isset($_GET['execute_sql']) && $_GET['execute_sql'] == '1') {
    echo "<h3>Executing SQL Fixes...</h3>";

    foreach ($sql_fixes as $sql) {
        if (trim($sql) && strpos($sql, '--') !== 0) {
            $result = $wpdb->query($sql);
            if ($result !== false) {
                echo "<p style='color: green;'>✅ " . substr($sql, 0, 100) . "...</p>";
            } else {
                echo "<p style='color: red;'>❌ " . substr($sql, 0, 100) . "... Error: " . $wpdb->last_error . "</p>";
            }
        }
    }

    echo "<p><strong>SQL execution complete!</strong> <a href='?' style='background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>Refresh Analysis</a></p>";
}

echo "<h2>Step 6: GEDCOM Service Column Mapping</h2>";

// Generate column mapping for GEDCOM service
echo "<h3>Required GEDCOM Service Updates:</h3>";

$gedcom_mappings = [
    'wp_hp_people' => ['personID', 'firstname', 'lastname', 'birthdate', 'birthdatetr', 'deathdate', 'deathdatetr', 'sex', 'birthplace', 'deathplace'],
    'wp_hp_families' => ['familyID', 'husband', 'wife', 'marrdate', 'marrdatetr', 'marrplace', 'divdate', 'divdatetr', 'divplace'],
    'wp_hp_sources' => ['sourceID', 'title', 'author', 'publisher', 'callnum', 'repoID'],
    'wp_hp_repositories' => ['repoID', 'reponame'],
    'wp_hp_media' => ['mediaID', 'path', 'description', 'mediatypeID'],
    'wp_hp_children' => ['familyID', 'personID', 'ordernum']
];

foreach ($gedcom_mappings as $table => $required_cols) {
    echo "<h4>$table</h4>";
    if (isset($our_tables[$table])) {
        $existing_cols = array_keys($our_tables[$table]);
        $missing_gedcom_cols = array_diff($required_cols, $existing_cols);

        if (empty($missing_gedcom_cols)) {
            echo "<p style='color: green;'>✅ All required GEDCOM columns exist</p>";
        } else {
            echo "<p style='color: red;'>❌ Missing GEDCOM columns: " . implode(', ', $missing_gedcom_cols) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Table does not exist</p>";
    }
}

$tng_connection->close();

echo "<h2>Analysis Complete</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Execute the SQL fixes above to sync database schema</li>";
echo "<li>Update GEDCOM service to use correct column names</li>";
echo "<li>Test GEDCOM import functionality</li>";
echo "</ol>";
?>