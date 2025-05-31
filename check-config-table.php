<?php
// Quick script to examine ConfigTable structure
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting ConfigTable examination...\n";
$file = 'C:\Users\Joe\Documents\coxfamilytree.rmtree';
echo "Target file: $file\n";
echo "File exists: " . (file_exists($file) ? 'YES' : 'NO') . "\n";

try {
    echo "Opening database...\n";
    $pdo = new PDO("sqlite:$file", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Database opened successfully.\n";
    
    echo "\n=== ConfigTable Structure ===\n";
    $stmt = $pdo->prepare("PRAGMA table_info(ConfigTable)");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        echo "No columns found or table doesn't exist.\n";
    } else {
        foreach ($columns as $column) {
            echo "Column: {$column['name']} ({$column['type']})\n";
        }
    }
    
    echo "\n=== Sample ConfigTable Data ===\n";
    $stmt = $pdo->prepare("SELECT * FROM ConfigTable LIMIT 10");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) {
        echo "No data found in ConfigTable.\n";
    } else {
        foreach ($rows as $i => $row) {
            echo "Row $i: " . print_r($row, true) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Script completed.\n";
?>
