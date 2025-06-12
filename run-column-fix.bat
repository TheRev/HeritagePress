@echo off
echo Running HeritagePress Column Fix...
cd /d "C:\MAMP\htdocs\wordpress\wp-content\plugins\heritagepress\HeritagePress"

"C:\MAMP\bin\php\php8.2.0\php.exe" -r "
require_once('../../../wp-config.php');
global $wpdb;

echo 'Adding missing columns...' . PHP_EOL;

$fixes = [
    'ALTER TABLE wp_hp_people ADD COLUMN person_id VARCHAR(50) NOT NULL DEFAULT '''' AFTER gedcom',
    'ALTER TABLE wp_hp_families ADD COLUMN family_id VARCHAR(50) NOT NULL DEFAULT '''' AFTER gedcom', 
    'ALTER TABLE wp_hp_sources ADD COLUMN source_id VARCHAR(50) NOT NULL DEFAULT '''' AFTER gedcom',
    'ALTER TABLE wp_hp_repositories ADD COLUMN name VARCHAR(255) NOT NULL DEFAULT '''' AFTER repo_id',
    'ALTER TABLE wp_hp_media ADD COLUMN media_id VARCHAR(50) NOT NULL DEFAULT '''' AFTER gedcom'
];

foreach ($fixes as $sql) {
    echo 'Running: ' . $sql . PHP_EOL;
    $result = $wpdb->query($sql);
    if ($result !== false) {
        echo 'SUCCESS' . PHP_EOL;
    } else {
        echo 'ERROR: ' . $wpdb->last_error . PHP_EOL;
    }
}

echo 'Column fix complete!' . PHP_EOL;
"

pause
