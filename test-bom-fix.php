<?php
/**
 * Test BOM removal fix for GEDCOM validation
 */

// Simulate the BOM removal logic
function test_bom_removal()
{
    echo "<h2>Testing BOM Removal Logic</h2>\n";

    // Test cases with different BOM types
    $test_cases = array(
        'UTF-8 BOM' => "\xEF\xBB\xBF0 HEAD",
        'UTF-16 BE BOM' => "\xFE\xFF0 HEAD",
        'UTF-16 LE BOM' => "\xFF\xFE0 HEAD",
        'No BOM' => "0 HEAD",
        'Visible BOM char' => "﻿0 HEAD" // This is what we saw in the debug log
    );

    foreach ($test_cases as $description => $test_string) {
        echo "<h3>$description</h3>\n";
        echo "Original: " . json_encode($test_string) . "\n<br>";

        // Apply the same BOM removal logic as in the validation function
        $cleaned = $test_string;

        // UTF-8 BOM (EF BB BF)
        $cleaned = preg_replace('/^\xEF\xBB\xBF/', '', $cleaned);
        // UTF-16 BE BOM (FE FF)
        $cleaned = preg_replace('/^\xFE\xFF/', '', $cleaned);
        // UTF-16 LE BOM (FF FE)
        $cleaned = preg_replace('/^\xFF\xFE/', '', $cleaned);
        // UTF-32 BE BOM (00 00 FE FF)
        $cleaned = preg_replace('/^\x00\x00\xFE\xFF/', '', $cleaned);
        // UTF-32 LE BOM (FF FE 00 00)
        $cleaned = preg_replace('/^\xFF\xFE\x00\x00/', '', $cleaned);
        // Also remove the common UTF-8 BOM character that might appear as a visible character
        $cleaned = ltrim($cleaned, "\xEF\xBB\xBF\xFE\xFF");

        $cleaned = trim($cleaned);

        echo "Cleaned: " . json_encode($cleaned) . "\n<br>";

        // Test if it matches the expected GEDCOM header pattern
        $matches = preg_match('/^0\s*HEAD\s*$/i', $cleaned);
        echo "Matches GEDCOM pattern: " . ($matches ? 'YES' : 'NO') . "\n<br>";
        echo "<hr>\n";
    }
}

test_bom_removal();
?>