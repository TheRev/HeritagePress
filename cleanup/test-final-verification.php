<?php
/**
 * Final test to verify the source system fix is working
 */

// Include WordPress
require_once('c:/MAMP/htdocs/wordpress/wp-config.php');

echo "<h1>🎉 Source System Fix Verification</h1>\n";

$test_file = 'c:/Users/Joe/Documents/Cox Family Tree_2025-05-26.ged';

if (!file_exists($test_file)) {
    echo "<p style='color: red;'>File not found: $test_file</p>\n";
    exit;
}

echo "<h2>Testing ImportHandler (Used by Step 2)</h2>\n";
try {
    require_once('c:/MAMP/htdocs/wordpress/wp-content/plugins/heritagepress/HeritagePress/includes/Admin/ImportExport/ImportHandler.php');
    $import_handler = new \HeritagePress\Admin\ImportExport\ImportHandler();
    $analysis = $import_handler->analyze_gedcom_file($test_file);

    if (isset($analysis['error'])) {
        echo "<p style='color: red;'>❌ Analysis failed: " . $analysis['message'] . "</p>\n";
    } else {
        echo "<p><strong>Source System:</strong> <span style='color: green; font-weight: bold; font-size: 16px;'>" . htmlspecialchars($analysis['source_system']) . "</span></p>\n";

        if ($analysis['source_system'] === 'Family Tree Maker for Windows (Version: 25.0.0.1164)') {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>\n";
            echo "<h3 style='color: #155724;'>✅ SUCCESS!</h3>\n";
            echo "<p style='color: #155724;'>Source system extraction is now working correctly!</p>\n";
            echo "</div>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ Unexpected result</p>\n";
        }

        echo "<h3>Full Analysis Results:</h3>\n";
        echo "<ul>\n";
        echo "<li><strong>GEDCOM Version:</strong> " . $analysis['gedcom_version'] . "</li>\n";
        echo "<li><strong>Encoding:</strong> " . $analysis['encoding'] . "</li>\n";
        echo "<li><strong>Individuals:</strong> " . number_format($analysis['individuals']) . "</li>\n";
        echo "<li><strong>Families:</strong> " . number_format($analysis['families']) . "</li>\n";
        echo "<li><strong>Sources:</strong> " . number_format($analysis['sources']) . "</li>\n";
        echo "</ul>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Testing Admin (Used by AJAX handler)</h2>\n";
try {
    require_once('c:/MAMP/htdocs/wordpress/wp-content/plugins/heritagepress/HeritagePress/includes/Admin/Admin.php');
    $admin = new \HeritagePress\Admin\Admin('', '');

    // Use reflection to access the private method
    $reflection = new ReflectionClass($admin);
    $method = $reflection->getMethod('analyze_gedcom_file_simple');
    $method->setAccessible(true);
    $analysis2 = $method->invoke($admin, $test_file);

    echo "<p><strong>Source System:</strong> <span style='color: green; font-weight: bold; font-size: 16px;'>" . htmlspecialchars($analysis2['source_system']) . "</span></p>\n";

    if ($analysis2['source_system'] === 'Family Tree Maker for Windows (Version: 25.0.0.1164)') {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>\n";
        echo "<h3 style='color: #155724;'>✅ SUCCESS!</h3>\n";
        echo "<p style='color: #155724;'>Admin analysis is also working correctly!</p>\n";
        echo "</div>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ Unexpected result: " . $analysis2['source_system'] . "</p>\n";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Next Steps</h2>\n";
echo "<ol>\n";
echo "<li><strong>Upload a GEDCOM file</strong> through the HeritagePress import interface</li>\n";
echo "<li><strong>Check Step 2</strong> - The validation page should now display the correct source system</li>\n";
echo "<li><strong>Verify</strong> that 'Source System: Family Tree Maker for Windows (Version: 25.0.0.1164)' appears instead of 'Unknown'</li>\n";
echo "</ol>\n";

echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 20px 0; border-radius: 5px;'>\n";
echo "<h3 style='color: #0c5460;'>🔧 Fix Applied</h3>\n";
echo "<p style='color: #0c5460;'>Added BOM (Byte Order Mark) removal to both ImportHandler and Admin analysis methods. The GEDCOM file contained a UTF-8 BOM character at the beginning that was preventing proper parsing of the header section.</p>\n";
echo "</div>\n";
?>