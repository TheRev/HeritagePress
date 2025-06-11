<?php
/**
 * Fix SQL prefix placeholders
 */

$sql_file = 'includes/Database/schema/complete-genealogy-schema.sql';
$content = file_get_contents($sql_file);

if ($content === false) {
    die("Could not read SQL file\n");
}

// Replace all occurrences of { $prefix } with {$prefix}
$fixed_content = str_replace('{ $prefix }', '{$prefix}', $content);

// Count replacements
$replacements = substr_count($content, '{ $prefix }');
echo "Made $replacements replacements\n";

// Write back to file
if (file_put_contents($sql_file, $fixed_content)) {
    echo "SQL file fixed successfully\n";
} else {
    echo "Failed to write SQL file\n";
}
?>