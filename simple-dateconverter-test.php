<?php
/**
 * Simple DateConverter Test
 */

echo "Starting test...\n";

try {
    // Load WordPress
    require_once dirname(__FILE__) . '/../../../../wp-config.php';
    echo "WordPress loaded\n";

    // Load autoloader
    require_once dirname(__FILE__) . '/includes/class-heritagepress-autoloader.php';
    echo "Autoloader loaded\n";

    HeritagePress_Autoloader::register();
    echo "Autoloader registered\n";

    // Test DateConverter
    $date_converter = new \HeritagePress\Models\DateConverter();
    echo "DateConverter created successfully!\n";

    // Simple test
    $result = $date_converter->parseDateValue('25 DEC 1990');
    echo "Date parsed: " . print_r($result, true) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "Test complete\n";
