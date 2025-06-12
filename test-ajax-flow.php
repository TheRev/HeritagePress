<?php
/**
 * Test the complete AJAX upload flow
 */

// Set up WordPress environment
define('WP_USE_THEMES', false);
require_once('../../../../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>AJAX Upload Flow Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <h2>Test AJAX Upload Flow</h2>

    <form id="test-upload-form" enctype="multipart/form-data">
        <?php wp_nonce_field('hp_gedcom_upload', 'hp_gedcom_nonce'); ?>
        <input type="file" name="gedcom_file" id="test-file" accept=".ged,.gedcom">
        <select name="tree_id">
            <option value="new">Create New Tree</option>
        </select>
        <input type="text" name="new_tree_name" value="Test Tree">
        <input type="radio" name="import_option" value="replace" checked>
        <button type="submit" id="test-submit">Test Upload</button>
    </form>

    <div id="test-results"></div>

    <script>
        var hp_vars = {
            ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
            hp_admin_url: '<?php echo admin_url(); ?>',
            nonce: '<?php echo wp_create_nonce('hp_admin_nonce'); ?>'
        };

        console.log('Test hp_vars:', hp_vars);

        $('#test-upload-form').on('submit', function (e) {
            e.preventDefault();

            console.log('Test form submitted');

            var formData = new FormData(this);
            formData.append('action', 'hp_upload_gedcom');

            console.log('FormData entries:');
            for (var pair of formData.entries()) {
                console.log(pair[0] + ':', pair[1]);
            }

            $.ajax({
                url: hp_vars.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    console.log('AJAX Success Response:', response);
                    $('#test-results').html('<h3>Success Response:</h3><pre>' + JSON.stringify(response, null, 2) + '</pre>');

                    if (response.success) {
                        var redirectUrl = hp_vars.hp_admin_url + 'admin.php?page=heritagepress-importexport&tab=import&step=2&file=' + encodeURIComponent(response.data.file_key);
                        console.log('Would redirect to:', redirectUrl);
                        $('#test-results').append('<p><strong>Would redirect to:</strong> <a href="' + redirectUrl + '" target="_blank">' + redirectUrl + '</a></p>');
                    }
                },
                error: function (xhr, status, error) {
                    console.log('AJAX Error:', { xhr: xhr, status: status, error: error });
                    $('#test-results').html('<h3>Error Response:</h3><pre>' + xhr.responseText + '</pre>');
                }
            });
        });
    </script>

    <p><strong>Instructions:</strong></p>
    <ol>
        <li>Select a .ged or .gedcom file</li>
        <li>Click "Test Upload"</li>
        <li>Check console for detailed logging</li>
        <li>Check the response below</li>
    </ol>

    <p><a href="http://localhost:8888/wordpress/wp-admin/admin.php?page=heritagepress-importexport&tab=import">Back to
            Import Page</a></p>
</body>

</html>