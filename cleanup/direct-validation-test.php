<?php
/**
 * Direct GEDCOM validation test
 */

// Include WordPress
$wp_config_path = 'c:/MAMP/htdocs/wordpress/wp-config.php';
if (file_exists($wp_config_path)) {
    require_once($wp_config_path);
    echo "<p>WordPress loaded successfully</p>\n";
} else {
    echo "<p>WordPress config not found at: $wp_config_path</p>\n";
}

echo "<h1>Direct GEDCOM Validation Test</h1>\n";

// Test with one of the uploaded files
$upload_dir = wp_upload_dir();
$gedcom_dir = $upload_dir['basedir'] . '/heritagepress/gedcom';

if (is_dir($gedcom_dir)) {
    $files = glob($gedcom_dir . '/*.ged');
    if (!empty($files)) {
        $test_file = $files[0]; // Use first available file
        echo "<h2>Testing file: " . basename($test_file) . "</h2>\n";

        // Test manual validation
        $file_handle = fopen($test_file, 'r');
        if ($file_handle) {
            echo "<h3>Manual Validation Test:</h3>\n";

            $line_count = 0;
            $found_header = false;

            while (($line = fgets($file_handle)) !== false && $line_count < 10) {
                $original_line = $line;
                $line = trim($line);
                $line_count++;

                echo "<p>Line $line_count: <code>" . htmlspecialchars($original_line) . "</code> (trimmed: <code>" . htmlspecialchars($line) . "</code>)</p>\n";

                if (empty($line)) {
                    echo "<p style='margin-left: 20px; color: orange;'>↳ Empty line, skipping</p>\n";
                    continue;
                }

                // Test the regex
                if (preg_match('/^0\s+HEAD\s*$/i', $line)) {
                    echo "<p style='margin-left: 20px; color: green;'>↳ ✅ HEADER FOUND!</p>\n";
                    $found_header = true;
                    break;
                } else {
                    echo "<p style='margin-left: 20px; color: red;'>↳ ❌ Not a header line</p>\n";
                }
            }

            fclose($file_handle);

            if ($found_header) {
                echo "<h3 style='color: green;'>✅ Validation Result: PASS</h3>\n";
            } else {
                echo "<h3 style='color: red;'>❌ Validation Result: FAIL</h3>\n";
            }
        } else {
            echo "<p style='color: red;'>Could not open file: $test_file</p>\n";
        }
    } else {
        echo "<p style='color: orange;'>No GEDCOM files found in upload directory</p>\n";
    }
} else {
    echo "<p style='color: red;'>Upload directory not found: $gedcom_dir</p>\n";
}

// Test with local test files too
echo "<h2>Testing Local Test Files</h2>\n";
$test_files = ['test-small.ged', 'test-family.ged'];

foreach ($test_files as $filename) {
    if (file_exists($filename)) {
        echo "<h3>Testing: $filename</h3>\n";

        $file_handle = fopen($filename, 'r');
        if ($file_handle) {
            $first_line = fgets($file_handle);
            fclose($file_handle);

            $trimmed = trim($first_line);
            echo "<p>First line: <code>" . htmlspecialchars($first_line) . "</code></p>\n";
            echo "<p>Trimmed: <code>" . htmlspecialchars($trimmed) . "</code></p>\n";

            if (preg_match('/^0\s+HEAD\s*$/i', $trimmed)) {
                echo "<p style='color: green;'>✅ Valid GEDCOM header</p>\n";
            } else {
                echo "<p style='color: red;'>❌ Invalid GEDCOM header</p>\n";
            }
        }
    } else {
        echo "<p style='color: orange;'>File not found: $filename</p>\n";
    }
}

?>