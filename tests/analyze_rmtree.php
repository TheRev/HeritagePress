<?php
/**
 * Simple script to analyze RootsMagic database structure
 * 
 * Usage: php analyze_rmtree.php <rm9|rm10>
 */

// Check for SQLite3 extension
if (!extension_loaded('sqlite3')) {
    die("SQLite3 extension is required.\n");
}

// Get database version to analyze from command line
$version = isset($argv[1]) ? strtolower($argv[1]) : null;

if ($version !== 'rm9' && $version !== 'rm10') {
    die("Usage: php analyze_rmtree.php <rm9|rm10>\n");
}

// Define file paths based on version
$dbFile = ($version === 'rm9') 
    ? 'C:\\Users\\Joe\\Documents\\jeanstuff\\John church_AutoBackup.rmtree'
    : 'C:\\Users\\Joe\\Documents\\coxfamilytree.rmtree';

if (!file_exists($dbFile)) {
    die("Database file not found: $dbFile\n");
}

try {
    // Connect to database
    $db = new SQLite3($dbFile);
      echo "Successfully connected to $version database: $dbFile\n\n";

    // Get list of tables
    $tables = [];
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tables[] = $row['name'];
    }
    
    echo "Tables in database (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "\n=== $table ===\n";
        
        // Get table schema
        $result = $db->query("PRAGMA table_info('$table')");
        while ($column = $result->fetchArray(SQLITE3_ASSOC)) {
            echo "{$column['name']} ({$column['type']})" . 
                 ($column['pk'] ? " PRIMARY KEY" : "") . "\n";
        }
          // Count rows in table
        $result = $db->query("SELECT COUNT(*) as count FROM \"$table\"");
        $count = $result->fetchArray(SQLITE3_ASSOC)['count'];
        echo "Row count: $count\n";
        
        // Show sample row for people and families
        if (in_array($table, ['Person', 'NameTable', 'Family', 'FamilyTable']) && $count > 0) {
            $result = $db->query("SELECT * FROM \"$table\" LIMIT 1");
            $sample = $result->fetchArray(SQLITE3_ASSOC);
            echo "Sample data:\n";
            foreach ($sample as $key => $value) {
                if (strlen($value) > 100) {
                    $value = substr($value, 0, 100) . "... (truncated)";
                }
                echo "  $key: $value\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
