<?php
/**
 * SQL Processing Test
 * Tests the exact SQL processing logic used by Manager.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>SQL Processing Test</h1>";
echo "<style>
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
    .sql-statement { margin: 10px 0; padding: 10px; border: 1px solid #ccc; background: #f9f9f9; }
</style>";

// Load WordPress
$wp_load_path = realpath(__DIR__ . '/../../../wp-load.php');
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
    echo "<p class='success'>✓ WordPress loaded successfully</p>";
} else {
    die("<p class='error'>✗ WordPress not found</p>");
}

global $wpdb;
echo "<p class='info'>Database prefix: " . $wpdb->prefix . "</p>";

// Test processing of just one SQL file first
$schema_dir = __DIR__ . '/includes/Database/schema/';
$test_file = 'core-tables.sql';

echo "<h2>Testing: $test_file</h2>";

$file_path = $schema_dir . $test_file;
if (!file_exists($file_path)) {
    die("<p class='error'>✗ File not found: $file_path</p>");
}

$sql = file_get_contents($file_path);
echo "<p class='success'>✓ File read successfully (" . strlen($sql) . " bytes)</p>";

// Show first few lines of original SQL
$lines = explode("\n", $sql);
echo "<h3>Original SQL (first 10 lines):</h3>";
echo "<pre>" . htmlspecialchars(implode("\n", array_slice($lines, 0, 10))) . "</pre>";

// Replace prefix
$sql_processed = str_replace(['{$prefix}'], $wpdb->prefix, $sql);
echo "<p class='success'>✓ Prefix replaced</p>";

// Show first few lines after prefix replacement
$lines_processed = explode("\n", $sql_processed);
echo "<h3>After prefix replacement (first 10 lines):</h3>";
echo "<pre>" . htmlspecialchars(implode("\n", array_slice($lines_processed, 0, 10))) . "</pre>";

// Split into statements
echo "<h3>Splitting into statements...</h3>";
$statements = preg_split('/;\s*\n/', $sql_processed);
echo "<p class='info'>Found " . count($statements) . " statements after splitting</p>";

$create_count = 0;
foreach ($statements as $i => $statement) {
    $statement = trim($statement);
    if (empty($statement)) {
        echo "<p class='warning'>Statement $i: Empty statement (skipped)</p>";
        continue;
    }

    echo "<div class='sql-statement'>";
    echo "<h4>Statement $i:</h4>";

    if (stripos($statement, 'CREATE TABLE') === 0) {
        $create_count++;
        echo "<p class='success'>✓ CREATE TABLE statement found</p>";

        // Extract table name for display
        if (preg_match('/CREATE TABLE IF NOT EXISTS\s+(\w+)/i', $statement, $matches)) {
            $table_name = $matches[1];
            echo "<p class='info'>Table: <strong>$table_name</strong></p>";
        }

        // Show first 200 characters of statement
        $preview = strlen($statement) > 200 ? substr($statement, 0, 200) . '...' : $statement;
        echo "<pre>" . htmlspecialchars($preview) . "</pre>";

        // Test if we can execute this statement
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        try {
            echo "<p class='info'>Attempting to execute with dbDelta...</p>";
            $result = dbDelta($statement . ";");

            if (is_array($result) && !empty($result)) {
                echo "<p class='success'>✓ dbDelta executed successfully:</p>";
                echo "<pre>" . htmlspecialchars(print_r($result, true)) . "</pre>";
            } else {
                echo "<p class='warning'>⚠️ dbDelta returned empty result</p>";
                echo "<pre>Result: " . htmlspecialchars(var_export($result, true)) . "</pre>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ dbDelta failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='info'>Non-CREATE statement (type: " . strtoupper(substr($statement, 0, 10)) . "...)</p>";
        $preview = strlen($statement) > 100 ? substr($statement, 0, 100) . '...' : $statement;
        echo "<pre>" . htmlspecialchars($preview) . "</pre>";
    }

    echo "</div>";
}

echo "<h3>Summary</h3>";
echo "<p class='info'>Total CREATE TABLE statements processed: $create_count</p>";

// Check what tables actually exist now
$existing_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
echo "<p class='info'>HeritagePress tables now in database: " . count($existing_tables) . "</p>";

if (count($existing_tables) > 0) {
    echo "<ul>";
    foreach ($existing_tables as $table) {
        echo "<li class='success'>✓ $table</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='error'>✗ No HeritagePress tables found in database</p>";
}

echo "<hr>";
echo "<p><a href='" . admin_url() . "'>← Back to WordPress Admin</a></p>";
?>