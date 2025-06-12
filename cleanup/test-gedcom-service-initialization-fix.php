<?php
/**
 * Test the fixed GedcomService initialization issue
 */

// Add WordPress environment
define('WP_DEBUG', true);
require_once('../../../../../../wp-config.php');

echo "<h1>Testing GedcomService Initialization Fix</h1>\n";

try {
    // Load the required classes
    require_once(__DIR__ . '/includes/Admin/ImportExport/BaseManager.php');
    require_once(__DIR__ . '/includes/Admin/ImportExport/ImportHandler.php');

    echo "<h2>✅ Classes loaded successfully</h2>\n";

    // Create ImportHandler instance
    $import_handler = new \HeritagePress\Admin\ImportExport\ImportHandler();
    echo "<h2>✅ ImportHandler instantiated</h2>\n";

    // Test if get_gedcom_service() method works
    $gedcom_service = $import_handler->get_gedcom_service();

    if ($gedcom_service !== null) {
        echo "<h2>✅ GedcomService initialized successfully</h2>\n";
        echo "<p>Service class: " . get_class($gedcom_service) . "</p>\n";

        // Check if the import method exists
        if (method_exists($gedcom_service, 'import')) {
            echo "<h2>✅ GedcomService::import() method exists</h2>\n";

            // Get method info
            $reflection = new ReflectionMethod($gedcom_service, 'import');
            $params = $reflection->getParameters();
            echo "<h3>Method signature:</h3>\n";
            echo "<code>import(";
            foreach ($params as $i => $param) {
                if ($i > 0)
                    echo ", ";
                echo "$" . $param->getName();
                if ($param->hasType()) {
                    echo ": " . $param->getType();
                }
            }
            echo ")</code>\n";

        } else {
            echo "<h2>❌ GedcomService::import() method not found</h2>\n";
        }
    } else {
        echo "<h2>❌ GedcomService is null</h2>\n";
    }

} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>🎯 Fix Summary</h2>\n";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>\n";
echo "<p><strong>Issue:</strong> <code>\$this->gedcom_service</code> was null causing fatal error</p>\n";
echo "<p><strong>Fix:</strong> Changed to use <code>\$this->get_gedcom_service()</code> which properly initializes the service</p>\n";
echo "<p><strong>Location:</strong> ImportHandler.php line 172</p>\n";
echo "</div>\n";

echo "<h2>🧪 Test the Import Process</h2>\n";
echo "<p>The import process should now work properly. Try accessing:</p>\n";
echo "<ul>\n";
echo "<li><a href='/wordpress/wp-admin/admin.php?page=heritagepress-import&step=3'>Step 3 Import</a></li>\n";
echo "<li>Upload a GEDCOM file and proceed through the process</li>\n";
echo "</ul>\n";
?>