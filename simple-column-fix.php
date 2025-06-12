<?php
/**
 * Simple Database Column Fix
 */

// Database connection parameters
$host = 'localhost';
$dbname = 'wordpress';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Simple Column Fix</h1>";

    // Column fixes needed
    $fixes = [
        "ALTER TABLE wp_hp_people ADD COLUMN person_id VARCHAR(50) NOT NULL AFTER gedcom",
        "ALTER TABLE wp_hp_families ADD COLUMN family_id VARCHAR(50) NOT NULL AFTER gedcom",
        "ALTER TABLE wp_hp_sources ADD COLUMN source_id VARCHAR(50) NOT NULL AFTER gedcom",
        "ALTER TABLE wp_hp_repositories ADD COLUMN name VARCHAR(255) NOT NULL AFTER repo_id",
        "ALTER TABLE wp_hp_media ADD COLUMN media_id VARCHAR(50) NOT NULL AFTER gedcom"
    ];

    foreach ($fixes as $sql) {
        echo "<p>Running: <code>$sql</code></p>";

        try {
            $pdo->exec($sql);
            echo "<p style='color: green;'>✅ Success</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<p style='color: orange;'>⚠️ Column already exists</p>";
            } else {
                echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
            }
        }
    }

    echo "<h2>Done! Column fixes completed.</h2>";
    echo "<p><a href='http://localhost/wordpress/wp-admin/admin.php?page=heritagepress-import'>Test Import Again</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>";
}
?>