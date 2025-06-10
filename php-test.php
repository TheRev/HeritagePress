<?php
// Simple PHP test file to verify PHP execution
echo "PHP is working correctly!\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Extension loaded: " . (extension_loaded('mysqli') ? 'Yes' : 'No') . "\n";
echo "Memory limit: " . ini_get('memory_limit') . "\n";
?>