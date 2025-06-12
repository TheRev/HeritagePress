<?php
/**
 * Simple GEDCOM Import Test
 * Tests if the GEDCOM import functionality works after refactoring
 */

// WordPress Bootstrap
require_once 'c:\MAMP\htdocs\wordpress\wp-config.php';
require_once ABSPATH . 'wp-includes/wp-db.php';
require_once ABSPATH . 'wp-includes/pluggable.php';
require_once ABSPATH . 'wp-includes/functions.php';

// Plugin Bootstrap
define('HERITAGEPRESS_PLUGIN_DIR', __DIR__ . '/');
define('HERITAGEPRESS_PLUGIN_URL', 'http://localhost/wordpress/wp-content/plugins/heritagepress/HeritagePress/');
define('HERITAGEPRESS_VERSION', '1.0.0');

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/class-heritagepress-autoloader.php';

// Test file path
$gedcom_file = __DIR__ . '/test-cox-family.ged';

echo "<h1>GEDCOM Import Test</h1>\n";

// Check if file exists
if (!file_exists($gedcom_file)) {
    echo "<p style='color: red;'>❌ GEDCOM file not found: $gedcom_file</p>\n";
    exit;
}

echo "<p>✅ GEDCOM file found: " . basename($gedcom_file) . "</p>\n";
echo "<p>File size: " . number_format(filesize($gedcom_file)) . " bytes</p>\n";

// Read first few lines to verify format
$handle = fopen($gedcom_file, 'r');
$header_lines = [];
for ($i = 0; $i < 10 && !feof($handle); $i++) {
    $header_lines[] = trim(fgets($handle));
}
fclose($handle);

echo "<h2>GEDCOM Header Preview:</h2>\n";
echo "<pre>\n";
foreach ($header_lines as $line) {
    echo htmlspecialchars($line) . "\n";
}
echo "</pre>\n";

// Test database connection
global $wpdb;
echo "<h2>Database Connection Test:</h2>\n";
echo "<p>✅ Database connected: " . $wpdb->dbname . "</p>\n";

// Check if tables exist
$tables_to_check = [
    'hp_individuals',
    'hp_families', 
    'hp_sources',
    'hp_notes'
];

$missing_tables = [];
foreach ($tables_to_check as $table) {
    $full_table = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'");
    if (!$exists) {
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    echo "<p style='color: red;'>❌ Missing tables: " . implode(', ', $missing_tables) . "</p>\n";
    echo "<p>Please run plugin activation to create tables.</p>\n";
    exit;
}

echo "<p>✅ All required tables exist</p>\n";

// Test simple GEDCOM parsing without full import
echo "<h2>Basic GEDCOM Parsing Test:</h2>\n";

$content = file_get_contents($gedcom_file);
$lines = explode("\n", $content);
$total_lines = count($lines);

echo "<p>Total lines in GEDCOM: " . number_format($total_lines) . "</p>\n";

// Count record types
$record_counts = [];
$current_record = null;

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    
    if (preg_match('/^0\s+@.+@\s+(.+)/', $line, $matches)) {
        $record_type = $matches[1];
        if (!isset($record_counts[$record_type])) {
            $record_counts[$record_type] = 0;
        }
        $record_counts[$record_type]++;
    }
}

echo "<h3>Record Types Found:</h3>\n";
echo "<ul>\n";
foreach ($record_counts as $type => $count) {
    echo "<li><strong>$type:</strong> " . number_format($count) . " records</li>\n";
}
echo "</ul>\n";

// Test if we can access the GedcomService
echo "<h2>GedcomService Test:</h2>\n";

try {
    // Check if the class file exists and can be loaded
    $service_file = __DIR__ . '/includes/Services/GedcomService.php';
    if (!file_exists($service_file)) {
        echo "<p style='color: red;'>❌ GedcomService.php file not found</p>\n";
        exit;
    }
    
    // Check file syntax
    $output = shell_exec("php -l \"$service_file\" 2>&1");
    if (strpos($output, 'No syntax errors') === false) {
        echo "<p style='color: red;'>❌ GedcomService.php has syntax errors:</p>\n";
        echo "<pre>" . htmlspecialchars($output) . "</pre>\n";
        exit;
    }
    
    echo "<p>✅ GedcomService.php syntax is valid</p>\n";
    
    // Try to instantiate the service
    use HeritagePress\Services\GedcomService;
    $service = new GedcomService();
    echo "<p>✅ GedcomService instantiated successfully</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error with GedcomService: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    exit;
}

echo "<h2>🎉 All Tests Passed!</h2>\n";
echo "<p>The GEDCOM import system appears to be working. You can now test actual import through the WordPress admin interface.</p>\n";
echo "<p><strong>Next steps:</strong></p>\n";
echo "<ol>\n";
echo "<li>Go to WordPress Admin → HeritagePress → Import/Export</li>\n";
echo "<li>Upload the GEDCOM file: <code>" . basename($gedcom_file) . "</code></li>\n";
echo "<li>Run the import and check the results</li>\n";
echo "</ol>\n";
?>
