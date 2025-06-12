<?php
/**
 * HeritagePress Manual Activation Test
 * 
 * Test the plugin activation programmatically
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../../wp-config.php';

echo "=== HeritagePress Manual Activation Test ===\n\n";

// Include the main plugin file
require_once dirname(__FILE__) . '/heritagepress.php';

echo "Plugin file loaded successfully.\n";

// Check if the class exists
if (class_exists('HeritagePress')) {
    echo "HeritagePress class found.\n";

    // Try to activate
    try {
        echo "Attempting activation...\n";
        HeritagePress::activate();
        echo "✓ Activation completed successfully!\n";
    } catch (Exception $e) {
        echo "✗ Activation failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ HeritagePress class not found!\n";
}

echo "\n=== End Test ===\n";
