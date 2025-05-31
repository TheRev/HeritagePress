<?php
/**
 * Heritage Press Plugin Package Verification
 */

echo "Heritage Press Plugin Package Verification\n";
echo "==========================================\n\n";

$zip_path = __DIR__ . '/dist/heritage-press-1.0.0.zip';

// Check if zip file exists
if (!file_exists($zip_path)) {
    echo "❌ ERROR: Zip file not found at: $zip_path\n";
    exit(1);
}

echo "✅ Zip file found\n";
echo "   Path: $zip_path\n";
echo "   Size: " . number_format(filesize($zip_path)) . " bytes\n\n";

// Check if ZipArchive is available
if (!class_exists('ZipArchive')) {
    echo "❌ ERROR: ZipArchive not available\n";
    exit(1);
}

echo "✅ ZipArchive class available\n";

// Open and examine zip file
$zip = new ZipArchive();
$result = $zip->open($zip_path);

if ($result !== TRUE) {
    echo "❌ ERROR: Cannot open zip file (Error code: $result)\n";
    exit(1);
}

echo "✅ Zip file opens successfully\n";
echo "   Number of files: " . $zip->numFiles . "\n\n";

// Check for required files
$required_files = [
    'heritage-press/heritage-press.php',
    'heritage-press/readme.txt',
    'heritage-press/includes/class-autoloader.php',
    'heritage-press/includes/core/class-plugin.php'
];

echo "Checking required files:\n";
$all_files_found = true;

for ($i = 0; $i < $zip->numFiles; $i++) {
    $filename = $zip->getNameIndex($i);
    
    foreach ($required_files as $key => $required) {
        if ($filename === $required) {
            echo "✅ Found: $required\n";
            unset($required_files[$key]);
        }
    }
}

if (!empty($required_files)) {
    echo "\n❌ Missing required files:\n";
    foreach ($required_files as $missing) {
        echo "   - $missing\n";
    }
    $all_files_found = false;
}

// Extract and check main plugin file
$temp_dir = sys_get_temp_dir() . '/heritage-press-verify';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}

$zip->extractTo($temp_dir);
$zip->close();

$main_file = $temp_dir . '/heritage-press/heritage-press.php';
if (file_exists($main_file)) {
    echo "\n✅ Main plugin file extracted successfully\n";
    
    // Check PHP syntax
    $output = [];
    $return_code = 0;
    exec("php -l \"$main_file\" 2>&1", $output, $return_code);
    
    if ($return_code === 0) {
        echo "✅ PHP syntax is valid\n";
    } else {
        echo "❌ PHP syntax error:\n";
        echo implode("\n", $output) . "\n";
        $all_files_found = false;
    }
    
    // Check plugin headers
    $content = file_get_contents($main_file);
    if (strpos($content, 'Plugin Name: Heritage Press') !== false) {
        echo "✅ Plugin headers are valid\n";
    } else {
        echo "❌ Plugin headers missing or invalid\n";
        $all_files_found = false;
    }
} else {
    echo "\n❌ Cannot extract main plugin file\n";
    $all_files_found = false;
}

// Cleanup
if (is_dir($temp_dir)) {
    // Remove extracted files
    function removeDir($dir) {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                is_dir($path) ? removeDir($path) : unlink($path);
            }
            rmdir($dir);
        }
    }
    removeDir($temp_dir);
}

echo "\n" . str_repeat("=", 50) . "\n";

if ($all_files_found) {
    echo "🎉 VERIFICATION PASSED\n";
    echo "   The plugin package is valid and ready for installation.\n\n";
    echo "Installation methods:\n";
    echo "1. WordPress Admin: Plugins > Add New > Upload Plugin\n";
    echo "2. FTP: Extract to /wp-content/plugins/ directory\n";
    echo "3. File Manager: Upload and extract in plugins directory\n";
} else {
    echo "❌ VERIFICATION FAILED\n";
    echo "   The plugin package has issues that need to be resolved.\n";
    echo "   Please rebuild the plugin package.\n";
}

echo "\nFor troubleshooting help, see: TROUBLESHOOTING-GUIDE.md\n";
