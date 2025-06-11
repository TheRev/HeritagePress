<?php
// Simple GEDCOM file check
$source_file = 'C:\Users\Joe\Documents\Cox Family Tree_2025-05-26.ged';

echo "Checking GEDCOM file...\n";
echo "File exists: " . (file_exists($source_file) ? "YES" : "NO") . "\n";

if (file_exists($source_file)) {
    $size = filesize($source_file);
    echo "File size: " . number_format($size) . " bytes\n";

    $first_lines = array_slice(file($source_file), 0, 5);
    echo "First 5 lines:\n";
    foreach ($first_lines as $line) {
        echo "  " . trim($line) . "\n";
    }
}
?>