<?php
require_once 'includes/Core/Database.php';
require_once 'includes/Core/Config.php';

$config = new \HeritagePress\Core\Config();
$db = new \HeritagePress\Core\Database($config);

echo "Sources table structure:\n";
$result = $db->query('DESCRIBE wp_hp_sources');
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")" . ($row['Null'] === 'NO' ? ' NOT NULL' : '') . "\n";
}

echo "\nRepositories table structure:\n";
$result = $db->query('DESCRIBE wp_hp_repositories');
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")" . ($row['Null'] === 'NO' ? ' NOT NULL' : '') . "\n";
}

echo "\nMedia table structure:\n";
$result = $db->query('DESCRIBE wp_hp_media');
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")" . ($row['Null'] === 'NO' ? ' NOT NULL' : '') . "\n";
}
