<?php
// Test GEDCOM validation directly
$filepath = 'cox-family.ged';

if (!file_exists($filepath)) {
    die("File not found: $filepath\n");
}

echo "Testing GEDCOM validation for: $filepath\n";
echo "File size: " . filesize($filepath) . " bytes\n";

// Basic GEDCOM validation
$file_handle = fopen($filepath, 'r');
if (!$file_handle) {
    die("Cannot read GEDCOM file\n");
}

// Read first few lines to validate header structure
$lines = array();
for ($i = 0; $i < 5 && !feof($file_handle); $i++) {
    $line = fgets($file_handle);
    if ($line !== false) {
        $lines[] = $line; // Don't trim yet - let's see what we get
    }
}
fclose($file_handle);

echo "\nFirst 5 lines (raw):\n";
foreach ($lines as $i => $line) {
    echo "Line $i: [" . bin2hex($line) . "] = '" . $line . "'\n";
}

echo "\nFirst 5 lines (trimmed):\n";
foreach ($lines as $i => $line) {
    $trimmed = trim($line);
    echo "Line $i: '" . $trimmed . "'\n";
}

// Check for valid GEDCOM header
$first_line = trim($lines[0]);
echo "\nFirst line comparison:\n";
echo "Expected: '0 HEAD'\n";
echo "Found: '$first_line'\n";
echo "Match: " . ($first_line === '0 HEAD' ? 'YES' : 'NO') . "\n";
echo "Length - Expected: " . strlen('0 HEAD') . ", Found: " . strlen($first_line) . "\n";

// Character by character comparison
$expected = '0 HEAD';
echo "\nCharacter comparison:\n";
for ($i = 0; $i < max(strlen($expected), strlen($first_line)); $i++) {
    $exp_char = isset($expected[$i]) ? $expected[$i] : 'N/A';
    $found_char = isset($first_line[$i]) ? $first_line[$i] : 'N/A';
    $exp_ord = isset($expected[$i]) ? ord($expected[$i]) : 'N/A';
    $found_ord = isset($first_line[$i]) ? ord($first_line[$i]) : 'N/A';
    echo "Pos $i: Expected '$exp_char' ($exp_ord), Found '$found_char' ($found_ord)\n";
}
?>