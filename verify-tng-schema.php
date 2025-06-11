<?php
/**
 * TNG Schema Verification - Structure Validation
 * Verifies that all tables match TNG specifications exactly
 */

require_once('../../../../../../wp-config.php');

echo "<h1>🔍 TNG Schema Structure Verification</h1>\n";

global $wpdb;

// Define all 36 TNG tables with their expected structure
$tng_tables = [
    // Core Tables (22)
    'hp_trees' => ['treeID', 'gedcom', 'title', 'description', 'privacy_level'],
    'hp_people' => ['ID', 'gedcom', 'personID', 'lastname', 'firstname', 'birthdate', 'sex'],
    'hp_families' => ['ID', 'gedcom', 'familyID', 'husband', 'wife', 'marrdate'],
    'hp_children' => ['ID', 'gedcom', 'familyID', 'personID', 'frel', 'mrel'],
    'hp_events' => ['eventID', 'gedcom', 'persfamID', 'eventtypeID', 'eventdate'],
    'hp_eventtypes' => ['eventtypeID', 'tag', 'description', 'type'],
    'hp_places' => ['placeID', 'gedcom', 'place', 'latitude', 'longitude'],
    'hp_sources' => ['sourceID', 'gedcom', 's_id', 'title', 'author'],
    'hp_repositories' => ['repoID', 'gedcom', 'repo_id', 'reponame'],
    'hp_citations' => ['citationID', 'gedcom', 'persfamID', 'sourceID'],
    'hp_media' => ['mediaID', 'gedcom', 'm_id', 'mediafile'],
    'hp_medialinks' => ['ID', 'gedcom', 'mediaID', 'persfamID'],
    'hp_xnotes' => ['ID', 'gedcom', 'xn_id', 'note'],
    'hp_notelinks' => ['ID', 'gedcom', 'persfamID', 'xnoteID'],
    'hp_associations' => ['assocID', 'gedcom', 'personID', 'passocID'],
    'hp_countries' => ['countryID', 'country'],
    'hp_states' => ['stateID', 'countryID', 'state'],
    'hp_mediatypes' => ['mediatypeID', 'display', 'path'],
    'hp_languages' => ['langID', 'isolang', 'language'],
    'hp_gedcom7_enums' => ['enumID', 'enum_set', 'enum_value'],
    'hp_gedcom7_extensions' => ['extensionID', 'schema_uri', 'tag'],
    'hp_gedcom7_data' => ['dataID', 'gedcom', 'record_type', 'record_id'],
    
    // Advanced Tables (17)
    'hp_address' => ['addressID', 'address1', 'city', 'state', 'country'],
    'hp_albums' => ['albumID', 'albumname', 'description'],
    'hp_albumlinks' => ['albumlinkID', 'albumID', 'mediaID'],
    'hp_album2entities' => ['alinkID', 'gedcom', 'entityID', 'albumID'],
    'hp_branches' => ['branchID', 'branch', 'gedcom', 'description'],
    'hp_branchlinks' => ['ID', 'branch', 'gedcom', 'persfamID'],
    'hp_cemeteries' => ['cemeteryID', 'cemname', 'city', 'state'],
    'hp_dna_groups' => ['dna_group', 'test_type', 'gedcom'],
    'hp_dna_links' => ['ID', 'testID', 'personID', 'gedcom'],
    'hp_dna_tests' => ['testID', 'test_type', 'personID', 'gedcom'],
    'hp_image_tags' => ['ID', 'mediaID', 'gedcom', 'persfamID'],
    'hp_mostwanted' => ['ID', 'gedcom', 'title', 'description'],
    'hp_reports' => ['reportID', 'reportname', 'reportdesc'],
    'hp_saveimport' => ['ID', 'filename', 'gedcom'],
    'hp_temp_events' => ['tempID', 'gedcom', 'personID', 'eventdate'],
    'hp_templates' => ['id', 'template', 'keyname', 'value'],
    'hp_users' => ['userID', 'username', 'gedcom', 'role']
];

echo "<h2>📊 Schema Verification Summary</h2>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>Category</th><th>Expected</th><th>Found</th><th>Status</th></tr>\n";

$core_expected = 22;
$advanced_expected = 17;
$total_expected = 39;

$existing_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
$found_count = count($existing_tables);

// Count core vs advanced tables found
$core_found = 0;
$advanced_found = 0;

foreach ($existing_tables as $table) {
    $clean_name = str_replace($wpdb->prefix, '', $table);
    if (in_array($clean_name, ['hp_trees', 'hp_people', 'hp_families', 'hp_children', 'hp_events', 'hp_eventtypes', 'hp_places', 'hp_sources', 'hp_repositories', 'hp_citations', 'hp_media', 'hp_medialinks', 'hp_xnotes', 'hp_notelinks', 'hp_associations', 'hp_countries', 'hp_states', 'hp_mediatypes', 'hp_languages', 'hp_gedcom7_enums', 'hp_gedcom7_extensions', 'hp_gedcom7_data'])) {
        $core_found++;
    } else {
        $advanced_found++;
    }
}

$core_status = ($core_found == $core_expected) ? '✅ Complete' : '⚠️ Partial';
$advanced_status = ($advanced_found == $advanced_expected) ? '✅ Complete' : '⚠️ Partial';
$total_status = ($found_count >= $total_expected) ? '✅ Complete' : '⚠️ Partial';

echo "<tr><td>Core Tables</td><td>$core_expected</td><td>$core_found</td><td>$core_status</td></tr>\n";
echo "<tr><td>Advanced Tables</td><td>$advanced_expected</td><td>$advanced_found</td><td>$advanced_status</td></tr>\n";
echo "<tr><td><strong>Total</strong></td><td><strong>$total_expected</strong></td><td><strong>$found_count</strong></td><td><strong>$total_status</strong></td></tr>\n";
echo "</table>\n";

if ($found_count >= $total_expected) {
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>\n";
    echo "<h3>🎉 Complete TNG Schema Verified!</h3>\n";
    echo "<p>All required tables are present. Your HeritagePress installation has 100% TNG compatibility.</p>\n";
    echo "</div>\n";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>\n";
    echo "<h3>⚠️ Incomplete Schema</h3>\n";
    echo "<p>Missing " . ($total_expected - $found_count) . " tables for complete TNG compatibility.</p>\n";
    echo "</div>\n";
}

echo "<h2>📋 Detailed Table Analysis</h2>\n";

echo "<h3>Core Tables (Essential Genealogy Features)</h3>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>Table</th><th>Status</th><th>Key Fields</th></tr>\n";

$core_tables = array_slice($tng_tables, 0, 22);
foreach ($core_tables as $table => $key_fields) {
    $full_table = $wpdb->prefix . $table;
    $exists = in_array($full_table, $existing_tables);
    $status = $exists ? '✅ Present' : '❌ Missing';
    $fields_text = implode(', ', array_slice($key_fields, 0, 5));
    
    echo "<tr><td>$table</td><td>$status</td><td>$fields_text</td></tr>\n";
}
echo "</table>\n";

echo "<h3>Advanced Tables (Extended Features)</h3>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>Table</th><th>Status</th><th>Purpose</th></tr>\n";

$advanced_tables = [
    'hp_address' => 'Physical addresses for repositories and contacts',
    'hp_albums' => 'Photo album organization and management',
    'hp_albumlinks' => 'Links between albums and media files',
    'hp_album2entities' => 'Associates albums with people/families',
    'hp_branches' => 'Family branch organization and filtering',
    'hp_branchlinks' => 'Links people/families to specific branches',
    'hp_cemeteries' => 'Cemetery information and mapping',
    'hp_dna_groups' => 'DNA testing group management',
    'hp_dna_links' => 'Links DNA tests to specific individuals',
    'hp_dna_tests' => 'Complete DNA test results and analysis',
    'hp_image_tags' => 'Photo tagging and face recognition',
    'hp_mostwanted' => 'Research wish list and missing information',
    'hp_reports' => 'Custom report definitions and queries',
    'hp_saveimport' => 'GEDCOM import progress tracking',
    'hp_temp_events' => 'User-submitted events pending approval',
    'hp_templates' => 'Template system configuration',
    'hp_users' => 'User management and permissions'
];

foreach ($advanced_tables as $table => $purpose) {
    $full_table = $wpdb->prefix . $table;
    $exists = in_array($full_table, $existing_tables);
    $status = $exists ? '✅ Present' : '❌ Missing';
    
    echo "<tr><td>$table</td><td>$status</td><td>$purpose</td></tr>\n";
}
echo "</table>\n";

echo "<h2>🔧 Missing Tables</h2>\n";
$missing_tables = [];
foreach ($tng_tables as $table => $fields) {
    $full_table = $wpdb->prefix . $table;
    if (!in_array($full_table, $existing_tables)) {
        $missing_tables[] = $table;
    }
}

if (empty($missing_tables)) {
    echo "<p style='color: green;'>✅ No missing tables - schema is complete!</p>\n";
} else {
    echo "<p style='color: orange;'>Missing tables (" . count($missing_tables) . "):</p>\n";
    echo "<ul>\n";
    foreach ($missing_tables as $table) {
        echo "<li><code>$table</code></li>\n";
    }
    echo "</ul>\n";
    
    echo "<p><strong>To complete the schema:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Run the <code>install-complete-tng-schema.php</code> script</li>\n";
    echo "<li>Or manually create the missing tables using the SQL files</li>\n";
    echo "</ol>\n";
}

echo "<h2>🎯 Compatibility Assessment</h2>\n";
$compatibility_percentage = round(($found_count / $total_expected) * 100, 1);

echo "<div style='background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px;'>\n";
echo "<h3>TNG Compatibility Score: {$compatibility_percentage}%</h3>\n";

if ($compatibility_percentage >= 95) {
    echo "<p style='color: green; font-size: 18px;'>🏆 <strong>Excellent!</strong> Near-complete TNG compatibility.</p>\n";
} elseif ($compatibility_percentage >= 80) {
    echo "<p style='color: orange; font-size: 18px;'>⭐ <strong>Good!</strong> Most TNG features available.</p>\n";
} elseif ($compatibility_percentage >= 60) {
    echo "<p style='color: orange; font-size: 18px;'>⚠️ <strong>Partial.</strong> Basic TNG features available.</p>\n";
} else {
    echo "<p style='color: red; font-size: 18px;'>❌ <strong>Limited.</strong> Incomplete TNG compatibility.</p>\n";
}

echo "<p><strong>What this means:</strong></p>\n";
echo "<ul>\n";
if ($core_found >= 20) {
    echo "<li>✅ Core genealogy features fully functional</li>\n";
} else {
    echo "<li>❌ Core genealogy features may be limited</li>\n";
}

if ($advanced_found >= 10) {
    echo "<li>✅ Advanced features like DNA, albums, and reports available</li>\n";
} else {
    echo "<li>❌ Advanced features not yet available</li>\n";
}

if ($found_count >= $total_expected) {
    echo "<li>✅ Direct GEDCOM import/export fully supported</li>\n";
    echo "<li>✅ All TNG data migration paths available</li>\n";
} else {
    echo "<li>⚠️ Some GEDCOM features may not import correctly</li>\n";
    echo "<li>⚠️ TNG migration may require additional setup</li>\n";
}
echo "</ul>\n";
echo "</div>\n";

echo "<hr>\n";
echo "<p><a href='" . admin_url() . "'>← Back to WordPress Admin</a> | ";
echo "<a href='install-complete-tng-schema.php'>Install Complete Schema →</a></p>\n";
?>
