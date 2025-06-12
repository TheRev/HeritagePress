<?php
/**
 * Detailed debugging of source system parsing with your exact GEDCOM file
 */

// Include WordPress
require_once('c:/MAMP/htdocs/wordpress/wp-config.php');

echo "<h1>Detailed Source System Debug</h1>\n";

$test_file = 'c:/Users/Joe/Documents/Cox Family Tree_2025-05-26.ged';

if (!file_exists($test_file)) {
    echo "<p style='color: red;'>File not found: $test_file</p>\n";
    exit;
}

echo "<h2>Step 1: Raw File Analysis</h2>\n";

// First, let's see the exact content of the source section
$file_content = file_get_contents($test_file);
$lines = preg_split('/\r\n|\r|\n/', $file_content);

echo "<h3>Source Section from Raw File:</h3>\n";
echo "<pre>";
$show_lines = false;
$line_count = 0;
foreach ($lines as $line) {
    $line_count++;
    $trimmed = trim($line);
    
    if (strpos($trimmed, '1 SOUR') === 0) {
        $show_lines = true;
    }
    
    if ($show_lines) {
        echo sprintf("%2d: %s\n", $line_count, htmlspecialchars($line));
        
        // Stop at next level 1 tag that's not SOUR
        if (strpos($trimmed, '1 ') === 0 && strpos($trimmed, '1 SOUR') !== 0) {
            break;
        }
    }
    
    if ($line_count > 30) break; // Safety limit
}
echo "</pre>\n";

echo "<h2>Step 2: Manual Implementation Debug</h2>\n";

// Now let's manually implement the exact logic with detailed debugging
$file_handle = fopen($test_file, 'r');
$line_number = 0;
$in_header = false;
$in_source_section = false;
$source_code = '';
$source_name = '';
$source_version = '';

echo "<h3>Line-by-line Processing:</h3>\n";
echo "<table border='1' style='border-collapse: collapse; font-family: monospace; font-size: 11px;'>\n";
echo "<tr style='background: #f0f0f0;'><th>Line#</th><th>Raw Content</th><th>Level</th><th>Content</th><th>In Header</th><th>In Source</th><th>Action</th><th>Variables After</th></tr>\n";

while (($line = fgets($file_handle)) !== false) {
    $line_number++;
    $original_line = rtrim($line); // Keep original for display
    $line = trim($line);
    
    if (empty($line)) {
        continue;
    }
    
    // Stop after we've gone past the header
    if ($line_number > 25) break;
    
    $action = '';
    $level = '';
    $content_part = '';
    
    // Parse GEDCOM line
    if (preg_match('/^(\d+)\s+(.+)$/', $line, $matches)) {
        $level = intval($matches[1]);
        $content_part = $matches[2];
        
        // Level 0 records (main records)
        if ($level === 0) {
            if ($content_part === 'HEAD') {
                $in_header = true;
                $action = 'START HEADER';
            } elseif ($content_part === 'TRLR') {
                $action = 'END FILE';
                break;
            } else {
                $in_header = false;
                $action = 'START RECORD (exit header)';
                if (strpos($content_part, '@') === 0) {
                    break; // Stop at first record for this test
                }
            }
        } else {
            // Parse header information
            if ($in_header) {
                if ($level === 1) {
                    // Reset source section tracking for new level 1 tags
                    $old_in_source = $in_source_section;
                    $in_source_section = false;
                    
                    if (preg_match('/^SOUR\s*(.*)$/', $content_part, $sour_matches)) {
                        $in_source_section = true;
                        $source_code = trim($sour_matches[1]);
                        $action = "FOUND SOUR - extracted code='$source_code'";
                    } elseif (preg_match('/^CHAR\s+(.+)$/', $content_part, $char_matches)) {
                        $action = 'FOUND CHAR';
                    } else {
                        $action = 'LEVEL 1 TAG';
                        if ($old_in_source) {
                            $action .= ' (exited source section)';
                        }
                    }
                } elseif ($level === 2) {
                    if ($in_source_section) {
                        if (preg_match('/^NAME\s+(.+)$/', $content_part, $name_matches)) {
                            $source_name = trim($name_matches[1]);
                            $action = "FOUND SOURCE NAME - extracted name='$source_name'";
                        } elseif (preg_match('/^VERS\s+(.+)$/', $content_part, $version_matches)) {
                            $source_version = trim($version_matches[1]);
                            $action = "FOUND SOURCE VERSION - extracted version='$source_version'";
                        } else {
                            $action = 'OTHER LEVEL 2 IN SOURCE';
                        }
                    } else {
                        $action = 'LEVEL 2 OUTSIDE SOURCE';
                    }
                } else {
                    $action = "LEVEL $level IN HEADER";
                }
            } else {
                $action = 'NOT IN HEADER';
            }
        }
        
        $variables = "code='$source_code', name='$source_name', vers='$source_version'";
        
        $color = '';
        if (strpos($action, 'FOUND SOUR') !== false) $color = 'background: #ffeb3b;';
        if (strpos($action, 'FOUND SOURCE NAME') !== false) $color = 'background: #4caf50; color: white;';
        if (strpos($action, 'FOUND SOURCE VERSION') !== false) $color = 'background: #2196f3; color: white;';
        
        echo "<tr style='$color'>";
        echo "<td>$line_number</td>";
        echo "<td style='font-family: monospace; font-size: 10px; max-width: 200px; word-break: break-all;'>" . htmlspecialchars($original_line) . "</td>";
        echo "<td>$level</td>";
        echo "<td style='max-width: 150px; word-break: break-all;'>" . htmlspecialchars($content_part) . "</td>";
        echo "<td>" . ($in_header ? 'YES' : 'NO') . "</td>";
        echo "<td>" . ($in_source_section ? 'YES' : 'NO') . "</td>";
        echo "<td style='max-width: 200px; word-break: break-all;'>$action</td>";
        echo "<td style='font-size: 9px; max-width: 200px; word-break: break-all;'>$variables</td>";
        echo "</tr>\n";
    } else {
        echo "<tr style='background: #ffcdd2;'>";
        echo "<td>$line_number</td>";
        echo "<td colspan='7'>UNPARSEABLE LINE: " . htmlspecialchars($original_line) . "</td>";
        echo "</tr>\n";
    }
}

echo "</table>\n";
fclose($file_handle);

// Build final result
echo "<h2>Step 3: Final Source System Construction</h2>\n";
echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>\n";
echo "<h3>Extracted Variables:</h3>\n";
echo "<ul>\n";
echo "<li><strong>source_code:</strong> '" . htmlspecialchars($source_code) . "' (length: " . strlen($source_code) . ")</li>\n";
echo "<li><strong>source_name:</strong> '" . htmlspecialchars($source_name) . "' (length: " . strlen($source_name) . ")</li>\n";
echo "<li><strong>source_version:</strong> '" . htmlspecialchars($source_version) . "' (length: " . strlen($source_version) . ")</li>\n";
echo "</ul>\n";

$final_source_system = 'Unknown';
if (!empty($source_name) && !empty($source_version)) {
    $final_source_system = $source_name . ' (Version: ' . $source_version . ')';
    echo "<p><strong>Logic used:</strong> Name + Version (both present)</p>\n";
} elseif (!empty($source_name)) {
    $final_source_system = $source_name;
    echo "<p><strong>Logic used:</strong> Name only</p>\n";
} elseif (!empty($source_code)) {
    $final_source_system = $source_code;
    echo "<p><strong>Logic used:</strong> Code only</p>\n";
} else {
    echo "<p><strong>Logic used:</strong> Default (no source info found)</p>\n";
}

echo "<p><strong>Final Source System:</strong> <span style='color: blue; font-weight: bold; background: yellow; padding: 3px;'>" . htmlspecialchars($final_source_system) . "</span></p>\n";
echo "</div>\n";

if ($final_source_system === 'Family Tree Maker for Windows (Version: 25.0.0.1164)') {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>\n";
    echo "<h3 style='color: #155724;'>✅ SUCCESS!</h3>\n";
    echo "<p style='color: #155724;'>Manual parsing works correctly!</p>\n";
    echo "</div>\n";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>\n";
    echo "<h3 style='color: #721c24;'>❌ PROBLEM IDENTIFIED</h3>\n";
    echo "<p style='color: #721c24;'>Manual parsing failed. Expected: 'Family Tree Maker for Windows (Version: 25.0.0.1164)'</p>\n";
    echo "</div>\n";
}

echo "<h2>Step 4: Testing Current ImportHandler</h2>\n";
try {
    require_once('c:/MAMP/htdocs/wordpress/wp-content/plugins/heritagepress/HeritagePress/includes/Admin/ImportExport/ImportHandler.php');
    $import_handler = new \HeritagePress\Admin\ImportExport\ImportHandler();
    $analysis = $import_handler->analyze_gedcom_file($test_file);
    
    if (isset($analysis['error'])) {
        echo "<p style='color: red;'>❌ ImportHandler failed: " . $analysis['message'] . "</p>\n";
    } else {
        echo "<p><strong>ImportHandler Result:</strong> <span style='color: purple; font-weight: bold;'>" . htmlspecialchars($analysis['source_system']) . "</span></p>\n";
        
        if ($analysis['source_system'] === $final_source_system) {
            echo "<p style='color: green;'>✅ ImportHandler matches manual parsing</p>\n";
        } else {
            echo "<p style='color: red;'>❌ ImportHandler differs from manual parsing</p>\n";
            echo "<p>Manual: '$final_source_system'</p>\n";
            echo "<p>ImportHandler: '" . $analysis['source_system'] . "'</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
