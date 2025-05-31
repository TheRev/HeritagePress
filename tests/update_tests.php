<?php

// Pattern to match test class declaration
$pattern = '/class\s+(\w+)\s+extends\s+WP_UnitTestCase/';
$replacement = 'class $1 extends HeritageTestCase';

// Get all test files
$testFiles = glob(__DIR__ . '/*.php');

foreach ($testFiles as $file) {
    // Skip special files
    if (basename($file) === 'bootstrap.php' || 
        basename($file) === 'HeritageTestCase.php' ||
        basename($file) === 'update_tests.php' ||
        strpos($file, 'Mocks/') !== false) {
        continue;
    }

    $content = file_get_contents($file);
      // Add namespace if missing
    if (!preg_match('/^namespace\s+HeritagePress\\\\Tests;/m', $content)) {
        $content = preg_replace('/^<\?php\s+/m', "<?php\nnamespace HeritagePress\\Tests;\n\n", $content);
    }
    
    // Update base test case class
    $content = preg_replace('/use\s+WP_UnitTestCase\s*;/', '', $content);
    $content = preg_replace($pattern, $replacement, $content);
    
    // Save changes
    file_put_contents($file, $content);
    echo "Updated " . basename($file) . "\n";
}
