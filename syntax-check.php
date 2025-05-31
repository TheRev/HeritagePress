<?php
// Simple syntax check without executing code
echo "Starting syntax check...\n";

$files_to_check = [
    'heritage-press.php',
    'includes/class-autoloader.php',
    'includes/HeritagePress/Plugin.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "Checking: $file\n";
        $output = shell_exec("php -l \"$file\" 2>&1");
        echo "Result: $output\n";
    } else {
        echo "File not found: $file\n";
    }
}

echo "Syntax check complete.\n";
