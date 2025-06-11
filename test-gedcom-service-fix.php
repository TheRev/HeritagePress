<?php
/**
 * Quick Error Resolution Test - GedcomService Loading
 */

// WordPress environment
define('WP_USE_THEMES', false);
require_once('c:\MAMP\htdocs\wordpress\wp-config.php');

// Load plugin autoloader
require_once(__DIR__ . '/includes/class-heritagepress-autoloader.php');

echo "<h1>GedcomService Error Resolution Test</h1>\n";

try {
    echo "<h3>1. Testing BaseManager instantiation...</h3>\n";
    $base_manager = new \HeritagePress\Admin\ImportExport\BaseManager();
    echo "<p style='color: green;'>✓ BaseManager created successfully</p>\n";

    echo "<h3>2. Testing GedcomService access...</h3>\n";
    $gedcom_service = $base_manager->get_gedcom_service();
    echo "<p style='color: green;'>✓ GedcomService accessible via BaseManager</p>\n";

    echo "<h3>3. Testing ImportHandler...</h3>\n";
    $import_handler = new \HeritagePress\Admin\ImportExport\ImportHandler();
    echo "<p style='color: green;'>✓ ImportHandler created successfully</p>\n";

    echo "<h3>4. Testing ImportExportManager...</h3>\n";
    $manager = new \HeritagePress\Admin\ImportExportManager();
    echo "<p style='color: green;'>✓ ImportExportManager created successfully</p>\n";

    echo "<h2 style='color: green;'>🎉 ALL TESTS PASSED!</h2>\n";
    echo "<p><strong>The GedcomService loading error has been resolved!</strong></p>\n";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Exception:</h3>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
} catch (Error $e) {
    echo "<h3 style='color: red;'>❌ Fatal Error:</h3>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>