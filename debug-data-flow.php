<?php
// Test to see the difference between POST and AJAX data flow

echo "<h1>Import Data Flow Test</h1>";

echo "<h2>Current Page POST Data (Step 2 to Step 3):</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h2>What JavaScript will send via AJAX:</h2>";
$tree_id = isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : 'new';
$new_tree_name = isset($_POST['new_tree_name']) ? sanitize_text_field($_POST['new_tree_name']) : '';
$import_option = isset($_POST['import_option']) ? sanitize_text_field($_POST['import_option']) : 'replace';

echo "Tree ID: " . $tree_id . "<br>";
echo "New Tree Name: '" . $new_tree_name . "'<br>";
echo "Import Option: " . $import_option . "<br>";

?>

<script>
    // Simulate what step 3 JavaScript does
    var treeId = '<?php echo esc_js($tree_id); ?>';
    var newTreeName = '<?php echo esc_js($new_tree_name); ?>';
    var importOption = '<?php echo esc_js($import_option); ?>';

    console.log('JavaScript variables:');
    console.log('treeId:', treeId);
    console.log('newTreeName:', newTreeName);
    console.log('importOption:', importOption);

    // Show what AJAX data would look like
    var ajaxData = {
        action: 'hp_process_gedcom',
        tree_id: treeId,
        new_tree_name: newTreeName,
        import_option: importOption
    };

    console.log('AJAX data that would be sent:');
    console.log(ajaxData);

    document.addEventListener('DOMContentLoaded', function () {
        document.body.innerHTML += '<h2>JavaScript Debug (check browser console)</h2>';
        document.body.innerHTML += '<p>Tree ID: ' + treeId + '</p>';
        document.body.innerHTML += '<p>New Tree Name: "' + newTreeName + '"</p>';
        document.body.innerHTML += '<p>Import Option: ' + importOption + '</p>';
    });
</script>