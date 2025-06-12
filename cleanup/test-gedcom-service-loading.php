<?php
/**
 * Test GedcomService Loading
 */

echo "<h1>GedcomService Loading Test</h1>\n";

// Test manual loading
echo "<h3>1. Manual File Loading Test</h3>\n";
$gedcom_file = __DIR__ . '/includes/Services/GedcomService.php';
if (file_exists($gedcom_file)) {
    echo "<p>✓ GedcomService.php file exists at: $gedcom_file</p>\n";
    require_once $gedcom_file;
    echo "<p>✓ GedcomService.php file loaded manually</p>\n";
} else {
    echo "<p>✗ GedcomService.php file NOT found at: $gedcom_file</p>\n";
}

// Test with WordPress environment
echo "<h3>2. WordPress Environment Test</h3>\n";
define('WP_USE_THEMES', false);
require_once('c:\MAMP\htdocs\wordpress\wp-config.php');

// Load plugin autoloader
require_once(__DIR__ . '/includes/class-heritagepress-autoloader.php');

echo "<p>✓ Autoloader loaded</p>\n";

// Test class instantiation
echo "<h3>3. Class Instantiation Test</h3>\n";
try {
    $service = new \HeritagePress\Services\GedcomService();
    echo "<p style='color: green;'>✓ GedcomService instantiated successfully!</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception: " . htmlspecialchars($e->getMessage()) . "</p>\n";
} catch (Error $e) {
    echo "<p style='color: red;'>✗ Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test through BaseManager
echo "<h3>4. BaseManager Integration Test</h3>\n";
try {
    $base_manager = new \HeritagePress\Admin\ImportExport\BaseManager();
    echo "<p style='color: green;'>✓ BaseManager instantiated successfully (includes GedcomService)!</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ BaseManager Exception: " . htmlspecialchars($e->getMessage()) . "</p>\n";
} catch (Error $e) {
    echo "<p style='color: red;'>✗ BaseManager Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<h3>Test Complete</h3>\n";
?>