<?php
/**
 * Test URL encoding/decoding for tree name
 */

// Add WordPress header
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

?>
<!DOCTYPE html>
<html>

<head>
    <title>URL Encoding Test</title>
</head>

<body>
    <h2>URL Encoding Test for Tree Name</h2>

    <h3>Test Different Tree Name Values:</h3>

    <?php
    $test_names = [
        'My Family Tree',
        'Smith Family',
        'Test Tree',
        'Tree with (special) chars!',
        'Très français',
        ''
    ];

    foreach ($test_names as $name) {
        $encoded_name = urlencode($name);
        $step2_url = admin_url('admin.php?page=heritagepress-importexport&tab=import&step=2&file=test&new_tree_name=' . $encoded_name . '&tree_id=new&import_option=replace');

        echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ccc;'>";
        echo "<strong>Original:</strong> '" . htmlspecialchars($name) . "'<br>";
        echo "<strong>Encoded:</strong> '" . htmlspecialchars($encoded_name) . "'<br>";
        echo "<strong>URL:</strong> <a href='" . $step2_url . "'>Test this name</a><br>";
        echo "</div>";
    }
    ?>

    <h3>Current GET Parameters:</h3>
    <pre><?php print_r($_GET); ?></pre>

    <h3>Test the step 2 extraction logic:</h3>
    <?php
    if (!empty($_GET)) {
        $tree_id = isset($_GET['tree_id']) ? sanitize_text_field($_GET['tree_id']) : 'new';
        $new_tree_name = isset($_GET['new_tree_name']) ? sanitize_text_field($_GET['new_tree_name']) : '';
        $import_option = isset($_GET['import_option']) ? sanitize_text_field($_GET['import_option']) : 'replace';

        echo "<ul>";
        echo "<li>Raw GET['new_tree_name']: '" . ($_GET['new_tree_name'] ?? 'NOT SET') . "'</li>";
        echo "<li>After sanitize_text_field(): '" . $new_tree_name . "'</li>";
        echo "<li>Length: " . strlen($new_tree_name) . "</li>";
        echo "<li>Is empty: " . (empty($new_tree_name) ? 'YES' : 'NO') . "</li>";
        echo "</ul>";

        echo "<h4>What would be put in the form:</h4>";
        echo "<code>&lt;input type=\"hidden\" name=\"new_tree_name\" value=\"" . esc_attr($new_tree_name) . "\"&gt;</code>";
    }
    ?>

    <h3>Test Manual Import:</h3>
    <a href="<?php echo admin_url('admin.php?page=heritagepress-importexport&tab=import'); ?>">Go to Import Step 1</a>
</body>

</html>