<?php
/**
 * Test GEDCOM validation with BOM fix by creating a test GEDCOM file with BOM
 */

// Create a test GEDCOM file with UTF-8 BOM
$test_gedcom_with_bom = "\xEF\xBB\xBF0 HEAD\n1 SOUR Family Historian\n1 GEDC\n2 VERS 5.5.1\n0 @I1@ INDI\n1 NAME John /Doe/";
$test_gedcom_without_bom = "0 HEAD\n1 SOUR Family Historian\n1 GEDC\n2 VERS 5.5.1\n0 @I1@ INDI\n1 NAME John /Doe/";

$test_dir = __DIR__ . '/test_files/';
if (!is_dir($test_dir)) {
    mkdir($test_dir, 0755, true);
}

// Write test files
file_put_contents($test_dir . 'test_with_bom.ged', $test_gedcom_with_bom);
file_put_contents($test_dir . 'test_without_bom.ged', $test_gedcom_without_bom);

echo "<h2>GEDCOM BOM Validation Test</h2>\n";

// Test both files with our validation logic
function test_gedcom_validation($filepath, $description)
{
    echo "<h3>Testing: $description</h3>\n";
    echo "File: $filepath\n<br>";

    $file_handle = fopen($filepath, 'r');
    if (!$file_handle) {
        echo "ERROR: Cannot open file\n<br>";
        return;
    }

    $found_header = false;
    $line_count = 0;
    $debug_lines = array();

    while (($line = fgets($file_handle)) !== false && $line_count < 10) {
        $original_line = $line;
        $line = trim($line);
        $line_count++;

        // Remove BOM characters if present (UTF-8, UTF-16, UTF-32)
        if ($line_count === 1) {
            // UTF-8 BOM (EF BB BF)
            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            // UTF-16 BE BOM (FE FF)
            $line = preg_replace('/^\xFE\xFF/', '', $line);
            // UTF-16 LE BOM (FF FE)
            $line = preg_replace('/^\xFF\xFE/', '', $line);
            // UTF-32 BE BOM (00 00 FE FF)
            $line = preg_replace('/^\x00\x00\xFE\xFF/', '', $line);
            // UTF-32 LE BOM (FF FE 00 00)
            $line = preg_replace('/^\xFF\xFE\x00\x00/', '', $line);
            // Also remove the common UTF-8 BOM character that might appear as a visible character
            $line = ltrim($line, "\xEF\xBB\xBF\xFE\xFF");
        }

        // Debug: collect first few lines
        if ($line_count <= 3) {
            $debug_lines[] = 'Line ' . $line_count . ': "' . $original_line . '" (after BOM removal: "' . $line . '")';
        }

        // Skip empty lines and look for GEDCOM header
        if (empty($line)) {
            continue;
        }

        // Check for GEDCOM header (various valid formats)
        if (preg_match('/^0\s*HEAD\s*$/i', $line)) {
            $found_header = true;
            echo "Found header on line $line_count: '$line'\n<br>";
            break;
        }
    }

    fclose($file_handle);

    echo "Debug lines: " . implode(' | ', $debug_lines) . "\n<br>";
    echo "Header found: " . ($found_header ? 'YES ✅' : 'NO ❌') . "\n<br>";
    echo "<hr>\n";

    return $found_header;
}

// Test both files
$result1 = test_gedcom_validation($test_dir . 'test_with_bom.ged', 'File WITH BOM');
$result2 = test_gedcom_validation($test_dir . 'test_without_bom.ged', 'File WITHOUT BOM');

echo "<h3>Summary</h3>\n";
echo "File with BOM validated: " . ($result1 ? 'SUCCESS ✅' : 'FAILED ❌') . "\n<br>";
echo "File without BOM validated: " . ($result2 ? 'SUCCESS ✅' : 'FAILED ❌') . "\n<br>";

if ($result1 && $result2) {
    echo "<strong>🎉 BOM fix is working! Both files validate correctly.</strong>\n<br>";
} else {
    echo "<strong>⚠️ There are still issues with the validation logic.</strong>\n<br>";
}

// Clean up test files
unlink($test_dir . 'test_with_bom.ged');
unlink($test_dir . 'test_without_bom.ged');
rmdir($test_dir);
?>