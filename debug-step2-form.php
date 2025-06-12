<?php
/**
 * Debug step 2 form submission directly
 */

// Add WordPress header
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

?>
<!DOCTYPE html>
<html>

<head>
    <title>Step 2 Form Debug</title>
</head>

<body>
    <h2>Step 2 Form Submission Debug</h2>

    <!-- Simulate what step 2 should be sending -->
    <h3>Test Form (mimicking step 2):</h3>
    <form method="post"
        action="<?php echo admin_url('admin.php?page=heritagepress-importexport&tab=import&step=3&file=test'); ?>">
        <?php wp_nonce_field('hp_gedcom_upload', 'hp_gedcom_nonce'); ?>
        <input type="hidden" name="tree_id" value="new">
        <input type="hidden" name="new_tree_name" value="Test Tree Name">
        <input type="hidden" name="import_option" value="replace">
        <input type="hidden" name="import_media" value="1">
        <input type="hidden" name="privacy_living" value="0">
        <input type="hidden" name="privacy_notes" value="0">
        <button type="submit">Submit Test Form</button>
    </form>

    <h3>Current URL Parameters:</h3>
    <pre><?php print_r($_GET); ?></pre>

    <h3>Current POST Data:</h3>
    <pre><?php print_r($_POST); ?></pre>

    <h3>Tree Name Simulation:</h3>
    <?php
    // Simulate step 2 logic
    $tree_id = isset($_GET['tree_id']) ? sanitize_text_field($_GET['tree_id']) : (isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : 'new');
    $new_tree_name = isset($_GET['new_tree_name']) ? sanitize_text_field($_GET['new_tree_name']) : (isset($_POST['new_tree_name']) ? sanitize_text_field($_POST['new_tree_name']) : '');
    $import_option = isset($_GET['import_option']) ? sanitize_text_field($_GET['import_option']) : (isset($_POST['import_option']) ? sanitize_text_field($_POST['import_option']) : 'replace');

    echo "<ul>";
    echo "<li>tree_id: '" . htmlspecialchars($tree_id) . "'</li>";
    echo "<li>new_tree_name: '" . htmlspecialchars($new_tree_name) . "'</li>";
    echo "<li>import_option: '" . htmlspecialchars($import_option) . "'</li>";
    echo "</ul>";

    echo "<h4>Form field values that would be generated:</h4>";
    echo "<ul>";
    echo "<li>Hidden tree_id: '" . esc_attr($tree_id) . "'</li>";
    echo "<li>Hidden new_tree_name: '" . esc_attr($new_tree_name) . "'</li>";
    echo "<li>Hidden import_option: '" . esc_attr($import_option) . "'</li>";
    echo "</ul>";
    ?>

    <h3>Test URL for Step 2:</h3>
    <a
        href="<?php echo admin_url('admin.php?page=heritagepress-importexport&tab=import&step=2&file=test&new_tree_name=' . urlencode('Debug Test Tree') . '&tree_id=new&import_option=replace'); ?>">
        Go to Step 2 with Debug Tree Name
    </a>
</body>

</html>