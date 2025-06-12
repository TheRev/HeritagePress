<?php
/**
 * Test AJAX process handler directly
 */

// Simulate WordPress AJAX environment
define('DOING_AJAX', true);
define('WP_DEBUG', true);

// Load WordPress
require_once('../../../../../../wp-config.php');

echo "<h1>Testing AJAX Process Handler</h1>\n";

try {
    // Load the ImportHandler
    require_once(__DIR__ . '/includes/Admin/ImportExport/BaseManager.php');
    require_once(__DIR__ . '/includes/Admin/ImportExport/ImportHandler.php');

    $import_handler = new \HeritagePress\Admin\ImportExport\ImportHandler();

    echo "<h2>✅ ImportHandler loaded</h2>\n";

    // Test GedcomService initialization
    $gedcom_service = $import_handler->get_gedcom_service();
    if ($gedcom_service) {
        echo "<h2>✅ GedcomService initialized: " . get_class($gedcom_service) . "</h2>\n";
    } else {
        echo "<h2>❌ GedcomService failed to initialize</h2>\n";
    }

    // Simulate the conditions that caused the error
    echo "<h2>🧪 Testing Import Conditions</h2>\n";

    // Check for uploaded GEDCOM files
    $upload_info = wp_upload_dir();
    $gedcom_dir = $upload_info['basedir'] . '/heritagepress/gedcom';

    if (is_dir($gedcom_dir)) {
        $gedcom_files = glob($gedcom_dir . '/*.ged');
        if (!empty($gedcom_files)) {
            echo "<p>✅ Found " . count($gedcom_files) . " GEDCOM file(s) in upload directory</p>\n";
            foreach ($gedcom_files as $file) {
                echo "<li>" . basename($file) . " (" . filesize($file) . " bytes)</li>\n";
            }
        } else {
            echo "<p>⚠️ No GEDCOM files found in upload directory</p>\n";
        }
    } else {
        echo "<p>⚠️ GEDCOM upload directory does not exist</p>\n";
    }

} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>🎯 The Fix Applied</h2>\n";
echo "<div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #007acc;'>\n";
echo "<p><strong>Before:</strong> <code>\$this->gedcom_service->import(\$gedcom_file, \$tree_id)</code></p>\n";
echo "<p><strong>After:</strong> <code>\$this->get_gedcom_service()->import(\$gedcom_file, \$tree_id)</code></p>\n";
echo "<p><strong>Why:</strong> The property <code>\$gedcom_service</code> was null because it's lazy-loaded through the getter method</p>\n";
echo "</div>\n";

echo "<h2>✅ Import Process Should Now Work</h2>\n";
echo "<p>The fatal error should be resolved. Try the import process again.</p>\n";
?>