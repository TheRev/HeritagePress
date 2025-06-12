<?php
// Test database connection and table status
echo "Testing database connection...\n";

// WordPress database configuration (adjust as needed)
$db_host = 'localhost';  // Standard MySQL port (3306)
$db_name = 'wordpress';
$db_user = 'root';
$db_pass = 'root';
$table_prefix = 'wp_';

try {
    // Try using mysqli instead of PDO
    $connection = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($connection->connect_error) {
        throw new Exception("Connection failed: " . $connection->connect_error);
    }

    echo "Connected to database successfully.\n";
    echo "Database: $db_name\n";
    echo "Prefix: $table_prefix\n";

    // Check HeritagePress tables
    $result = $connection->query("SHOW TABLES LIKE '{$table_prefix}hp_%'");
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    echo "\nFound " . count($tables) . " HeritagePress tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

    if (count($tables) === 0) {
        echo "\nNo HeritagePress tables found. Plugin may not be activated or tables not created.\n";
    } elseif (count($tables) < 32) {
        echo "\nFound " . count($tables) . " tables, but expecting 32. Table creation may have failed.\n";
    } else {
        echo "\nAll tables appear to be present!\n";
    }

} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    echo "Make sure MAMP is running and database credentials are correct.\n";
    echo "Also check that MySQL service is running on port 3306.\n";
}

echo "\nTest completed.\n";
?>