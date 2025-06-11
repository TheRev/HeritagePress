<?php
/**
 * Debug GEDCOM validation issue
 */

echo "<h1>Debug GEDCOM Validation Issue</h1>\n";

// Test file reading with different methods
$test_file = 'test-small.ged';

if (file_exists($test_file)) {
    echo "<h2>File Analysis: $test_file</h2>\n";

    // Method 1: file_get_contents
    $content = file_get_contents($test_file);
    echo "<h3>Using file_get_contents:</h3>\n";
    echo "<p>File size: " . strlen($content) . " bytes</p>\n";
    echo "<p>First 50 characters: <code>" . htmlspecialchars(substr($content, 0, 50)) . "</code></p>\n";
    echo "<p>First 20 bytes (hex): " . bin2hex(substr($content, 0, 20)) . "</p>\n";

    // Method 2: fgets (like the validation function)
    echo "<h3>Using fgets (like validation function):</h3>\n";
    $handle = fopen($test_file, 'r');
    if ($handle) {
        $first_line = fgets($handle);
        fclose($handle);
        echo "<p>First line: <code>" . htmlspecialchars($first_line) . "</code></p>\n";
        echo "<p>First line length: " . strlen($first_line) . " characters</p>\n";
        echo "<p>First line (hex): " . bin2hex($first_line) . "</p>\n";

        // Test the old validation logic
        echo "<h3>Old Validation Logic Test:</h3>\n";
        if (strpos($first_line, '0 HEAD') !== 0) {
            echo "<p style='color: red;'>❌ Old validation would FAIL</p>\n";
            echo "<p>strpos result: " . var_export(strpos($first_line, '0 HEAD'), true) . "</p>\n";
            echo "<p>Expected: 0, Got: " . strpos($first_line, '0 HEAD') . "</p>\n";
        } else {
            echo "<p style='color: green;'>✅ Old validation would PASS</p>\n";
        }

        // Test the new validation logic
        echo "<h3>New Validation Logic Test:</h3>\n";
        $trimmed = trim($first_line);
        if (preg_match('/^0\s+HEAD\s*$/i', $trimmed)) {
            echo "<p style='color: green;'>✅ New validation would PASS</p>\n";
        } else {
            echo "<p style='color: red;'>❌ New validation would FAIL</p>\n";
            echo "<p>Trimmed line: <code>" . htmlspecialchars($trimmed) . "</code></p>\n";
            echo "<p>Regex pattern: /^0\s+HEAD\s*$/i</p>\n";
        }
    }
} else {
    echo "<p style='color: red;'>Test file not found: $test_file</p>\n";
}

// Test a basic valid GEDCOM format
echo "<h2>Test Basic GEDCOM Creation</h2>\n";

$test_content = "0 HEAD\n1 SOUR Test\n1 GEDC\n2 VERS 5.5.1\n1 CHAR UTF-8\n0 TRLR\n";
$test_file_path = 'temp-test.ged';

file_put_contents($test_file_path, $test_content);

echo "<p>Created test GEDCOM file: $test_file_path</p>\n";
echo "<p>Content:</p>\n";
echo "<pre>" . htmlspecialchars($test_content) . "</pre>\n";

// Test validation on the created file
$handle = fopen($test_file_path, 'r');
if ($handle) {
    $first_line = fgets($handle);
    fclose($handle);

    echo "<h3>Validation Test on Created File:</h3>\n";
    echo "<p>First line: <code>" . htmlspecialchars($first_line) . "</code></p>\n";

    // Old method
    if (strpos($first_line, '0 HEAD') !== 0) {
        echo "<p style='color: red;'>❌ Old method: FAIL</p>\n";
    } else {
        echo "<p style='color: green;'>✅ Old method: PASS</p>\n";
    }

    // New method
    $trimmed = trim($first_line);
    if (preg_match('/^0\s+HEAD\s*$/i', $trimmed)) {
        echo "<p style='color: green;'>✅ New method: PASS</p>\n";
    } else {
        echo "<p style='color: red;'>❌ New method: FAIL</p>\n";
    }
}

// Clean up
unlink($test_file_path);

echo "<div style='background: #fff3cd; padding: 15px; margin: 20px 0; border-left: 4px solid #ffc107;'>\n";
echo "<h3>⚠️ Debugging Complete</h3>\n";
echo "<p>This test should help identify if the issue is:</p>\n";
echo "<ul>\n";
echo "<li>File encoding (BOM, UTF-8 issues)</li>\n";
echo "<li>Whitespace handling</li>\n";
echo "<li>Case sensitivity</li>\n";
echo "<li>Regex pattern matching</li>\n";
echo "</ul>\n";
echo "</div>\n";

?>