<?php
/**
 * Debug script for RootsMagic importer
 */

require_once __DIR__ . '/vendor/autoload.php';

use HeritagePress\Importers\RootsMagicImporter;

$dbFile = 'C:\\Users\\Joe\\Documents\\coxfamilytree.rmtree';

echo "=== Debug RootsMagic Import ===\n";
echo "File: $dbFile\n";
echo "File exists: " . (file_exists($dbFile) ? 'YES' : 'NO') . "\n";
echo "File readable: " . (is_readable($dbFile) ? 'YES' : 'NO') . "\n";

if (file_exists($dbFile)) {
    $filesize = filesize($dbFile);
    echo "File size: " . $filesize . " bytes\n";
    
    // Check SQLite header
    $header = file_get_contents($dbFile, false, null, 0, 16);
    echo "Header (hex): " . bin2hex($header) . "\n";
    echo "Header (text): " . substr($header, 0, 16) . "\n";
    echo "SQLite format check: " . (substr($header, 0, 16) === 'SQLite format 3' ? 'PASS' : 'FAIL') . "\n";
}

// Test PDO connection
echo "\n=== PDO Test ===\n";
echo "PDO SQLite available: " . (extension_loaded('pdo_sqlite') ? 'YES' : 'NO') . "\n";

if (extension_loaded('pdo_sqlite')) {
    try {
        $pdo = new PDO("sqlite:$dbFile");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "PDO connection: SUCCESS\n";
        
        // List tables
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables found: " . count($tables) . "\n";
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
        
    } catch (Exception $e) {
        echo "PDO connection: FAILED - " . $e->getMessage() . "\n";
    }
}

// Test importer
echo "\n=== Importer Test ===\n";
$importer = new RootsMagicImporter();
$canImport = $importer->can_import($dbFile);
echo "Can import: " . ($canImport ? 'YES' : 'NO') . "\n";

// Use reflection to access private errors property
$reflection = new ReflectionClass($importer);
$errorsProperty = $reflection->getProperty('errors');
$errorsProperty->setAccessible(true);
$errors = $errorsProperty->getValue($importer);

if (!empty($errors)) {
    echo "Errors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}
