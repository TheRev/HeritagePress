<?php
/**
 * Import Step 1: File upload form
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get available trees from the ImportExportManager (passed from render_import_tab)
// $trees variable is available from the include context
if (!isset($trees)) {
    $trees = array(); // Fallback if no trees are available
    // Try to get trees directly if they weren't passed
    if (class_exists('HeritagePress\Admin\ImportExportManager')) {
        global $wpdb;
        if ($wpdb) {
            $trees = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_trees ORDER BY title ASC");
        }
    }
}

?>
<div class="hp-import-container">
    <h3><?php esc_html_e('Step 1: Select GEDCOM File', 'heritagepress'); ?></h3>

    <form method="post" enctype="multipart/form-data" id="hp-gedcom-upload-form" class="hp-form">
        <?php wp_nonce_field('hp_gedcom_upload', 'hp_gedcom_nonce'); ?>

        <div class="hp-drag-drop-zone" id="hp-drop-zone">
            <p class="drag-instructions">
                <?php esc_html_e('Drag and drop a GEDCOM file here', 'heritagepress'); ?>
            </p>
            <p class="manual-upload">
                <?php esc_html_e('or', 'heritagepress'); ?>
            </p>
            <p>
                <input type="file" name="gedcom_file" id="hp-gedcom-file" accept=".ged,.gedcom">
                <label for="hp-gedcom-file" class="button">
                    <?php esc_html_e('Select File', 'heritagepress'); ?>
                </label>
            </p>
            <p class="description">
                <?php esc_html_e('Maximum file size: 32MB', 'heritagepress'); ?>
            </p>
        </div>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="hp-gedcom-tree"><?php esc_html_e('Destination Tree', 'heritagepress'); ?></label>
                </th>
                <td> <?php
                // Use trees if available, otherwise fetch directly
                $trees_to_use = isset($trees) && !empty($trees) ? $trees : null;

                if (!$trees_to_use) {
                    global $wpdb;
                    if ($wpdb) {
                        $trees_to_use = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_trees ORDER BY title ASC");
                    }
                }
                ?>
                    <select name="tree_id" id="hp-gedcom-tree">
                        <option value="new">Create New Tree</option> <?php if (!empty($trees_to_use)) {
                            foreach ($trees_to_use as $tree) {
                                // Use correct database column names: treeID and title
                                if (property_exists($tree, 'treeID') && property_exists($tree, 'title')) {
                                    $tree_id = $tree->treeID;
                                    $tree_title = $tree->title;
                                    echo '<option value="' . esc_attr($tree_id) . '">' . esc_html($tree_title) . '</option>';
                                }
                            }
                        }
                        ?>
                    </select>
                    <div id="hp-new-tree-name" class="hp-conditional-field">
                        <label for="new_tree_name"><?php esc_html_e('New Tree Name:', 'heritagepress'); ?></label>
                        <input type="text" name="new_tree_name" id="new_tree_name"
                            placeholder="<?php esc_attr_e('My Family Tree', 'heritagepress'); ?>">
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php esc_html_e('Import Options', 'heritagepress'); ?>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php esc_html_e('Import Options', 'heritagepress'); ?>
                        </legend>

                        <label>
                            <input type="radio" name="import_option" value="replace" checked>
                            <?php esc_html_e('Replace existing data (recommended for new trees)', 'heritagepress'); ?>
                        </label><br>

                        <label>
                            <input type="radio" name="import_option" value="add">
                            <?php esc_html_e('Add to existing data (skip duplicates)', 'heritagepress'); ?>
                        </label><br>

                        <label>
                            <input type="radio" name="import_option" value="merge">
                            <?php esc_html_e('Merge with existing data (update duplicates)', 'heritagepress'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>

        <div class="hp-form-actions">
            <button type="submit" class="button button-primary" id="hp-validate-gedcom">
                <?php esc_html_e('Upload and Validate', 'heritagepress'); ?>
            </button>

            <div id="hp-upload-progress" class="hp-progress-container" style="display: none;">
                <div class="hp-progress-bar">
                    <div class="hp-progress-bar-inner"></div>
                </div>
                <div class="hp-progress-text">
                    <?php esc_html_e('Uploading...', 'heritagepress'); ?> <span class="hp-progress-percentage">0%</span>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    jQuery(document).ready(function ($) {
        console.log('=== STEP 1 TEMPLATE DEBUG ===');
        console.log('Step 1 upload template loaded');

        // Check if hp_vars is available
        if (typeof hp_vars !== 'undefined') {
            console.log('✅ hp_vars is available:', hp_vars);
        } else {
            console.log('❌ hp_vars is NOT available - using fallback values');
            // Create fallback hp_vars
            window.hp_vars = {
                ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
                hp_admin_url: '<?php echo admin_url(); ?>',
                nonce: '<?php echo wp_create_nonce('hp_admin_nonce'); ?>'
            };
            console.log('Created fallback hp_vars:', window.hp_vars);
        }        // Tree selection change handler
        $('#hp-gedcom-tree').on('change', function () {
            if ($(this).val() === 'new') {
                $('#hp-new-tree-name').show();
            } else {
                $('#hp-new-tree-name').hide();
            }
        }).trigger('change');

        // File selection change handler to update UI
        $('#hp-gedcom-file').on('change', function () {
            var file = this.files[0];
            if (file) {
                var fileName = file.name;
                var fileSize = file.size;
                var maxSize = 50 * 1024 * 1024; // 50MB

                // Validate file size
                if (fileSize > maxSize) {
                    alert('File is too large. Maximum size is 50MB.');
                    $(this).val('');
                    $('#hp-drop-zone .drag-instructions').text('Drag and drop a GEDCOM file here');
                    return;
                }

                // Validate file extension
                var extension = fileName.split('.').pop().toLowerCase();
                if (!['ged', 'gedcom'].includes(extension)) {
                    alert('Invalid file type. Only .ged and .gedcom files are allowed.');
                    $(this).val('');
                    $('#hp-drop-zone .drag-instructions').text('Drag and drop a GEDCOM file here');
                    return;
                }                // Update UI with selected filename
                $('#hp-drop-zone .drag-instructions').text('Selected: ' + fileName);
                $('#hp-drop-zone').addClass('file-selected');
            } else {
                // Reset to default text if no file selected
                $('#hp-drop-zone .drag-instructions').text('Drag and drop a GEDCOM file here');
                $('#hp-drop-zone').removeClass('file-selected');
            }
        });

        // Drag and drop functionality
        var $dropZone = $('#hp-drop-zone');

        $dropZone.on('dragover dragenter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        });

        $dropZone.on('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (!$(this).has(e.relatedTarget).length) {
                $(this).removeClass('drag-over');
            }
        });

        $dropZone.on('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');

            var files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                var fileInput = $('#hp-gedcom-file')[0];
                fileInput.files = files;
                $(fileInput).trigger('change'); // Trigger change event to update UI
            }
        });

        // Click to select file
        $dropZone.on('click', function () {
            $('#hp-gedcom-file').click();
        });

        // Override form submission with working AJAX handler
        $('#hp-gedcom-upload-form').off('submit').on('submit', function (e) {
            e.preventDefault();
            console.log('Form submission intercepted by template override');

            var $form = $(this);
            var $submitButton = $form.find('#hp-validate-gedcom');
            var formData = new FormData(this);

            // Add action
            formData.append('action', 'hp_upload_gedcom');

            // Validate form
            var $fileInput = $form.find('#hp-gedcom-file');
            if ($fileInput[0].files.length === 0) {
                alert('Please select a GEDCOM file to upload.');
                return false;
            }

            var $treeSelect = $form.find('#hp-gedcom-tree');
            if ($treeSelect.val() === 'new') {
                var $newTreeName = $form.find('#new_tree_name');
                if ($newTreeName.val().trim() === '') {
                    alert('Please enter a name for the new tree.');
                    $newTreeName.focus();
                    return false;
                }
            }

            console.log('Form validation passed, starting AJAX upload...');

            // Disable button and show progress
            $submitButton.prop('disabled', true);
            $('#hp-upload-progress').show();

            // Send AJAX request
            $.ajax({
                url: window.hp_vars.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function () {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function (e) {
                        if (e.lengthComputable) {
                            var percent = Math.round((e.loaded / e.total) * 100);
                            $('.hp-progress-bar-inner').css('width', percent + '%');
                            $('.hp-progress-percentage').text(percent + '%');
                        }
                    });
                    return xhr;
                }, success: function (response) {
                    console.log('AJAX Success Response:', response);
                    if (response.success) {
                        console.log('Upload successful, redirecting to step 2...');                        // Redirect to step 2 with all form data
                        var redirectUrl = window.hp_vars.hp_admin_url + 'admin.php?page=heritagepress-import-export&tab=import&step=2&file=' + encodeURIComponent(response.data.file_key);// Add form data to the redirect URL
                        var treeId = $form.find('#hp-gedcom-tree').val();
                        var newTreeName = $form.find('#new_tree_name').val();
                        var importOption = $form.find('input[name="import_option"]:checked').val();

                        // Debug logging
                        console.log('STEP 1 DEBUG - Form values being read:');
                        console.log('  treeId:', treeId);
                        console.log('  newTreeName:', newTreeName, '(length:', newTreeName ? newTreeName.length : 0, ')');
                        console.log('  importOption:', importOption);
                        console.log('  newTreeName field visible:', $form.find('#new_tree_name').is(':visible'));
                        console.log('  newTreeName field parent visible:', $form.find('#hp-new-tree-name').is(':visible'));

                        if (treeId) {
                            redirectUrl += '&tree_id=' + encodeURIComponent(treeId);
                        }
                        if (newTreeName) {
                            redirectUrl += '&new_tree_name=' + encodeURIComponent(newTreeName);
                        }
                        if (importOption) {
                            redirectUrl += '&import_option=' + encodeURIComponent(importOption);
                        }

                        console.log('Redirect URL with form data:', redirectUrl);
                        window.location.href = redirectUrl;
                    } else {
                        console.error('Upload failed:', response.data);
                        alert(response.data.message || 'Upload failed. Please try again.');
                        $submitButton.prop('disabled', false);
                        $('#hp-upload-progress').hide();
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', { xhr: xhr, status: status, error: error });
                    console.error('Response text:', xhr.responseText);
                    alert('Upload failed. Please try again. Check console for details.');
                    $submitButton.prop('disabled', false);
                    $('#hp-upload-progress').hide();
                }
            });

            return false;
        }); console.log('=== TEMPLATE OVERRIDE FORM HANDLER READY ===');
    });
</script>

<style>
    /* File selection styling */
    .hp-drag-drop-zone {
        border: 2px dashed #ccc;
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        background: #fafafa;
        transition: all 0.3s ease;
        margin-bottom: 20px;
    }

    .hp-drag-drop-zone:hover {
        border-color: #0073aa;
        background: #f0f8ff;
    }

    .hp-drag-drop-zone.drag-over {
        border-color: #0073aa;
        background: #e6f3ff;
        border-style: solid;
    }

    .hp-drag-drop-zone.file-selected {
        border-color: #46b450;
        background: #f0fff0;
        border-style: solid;
    }

    .hp-drag-drop-zone.file-selected .drag-instructions {
        color: #46b450;
        font-weight: bold;
    }

    .hp-drag-drop-zone .drag-instructions {
        font-size: 16px;
        margin: 0 0 10px 0;
        color: #666;
    }

    .hp-drag-drop-zone .manual-upload {
        margin: 10px 0;
        color: #999;
        font-style: italic;
    }

    .hp-drag-drop-zone input[type="file"] {
        display: none;
    }

    .hp-drag-drop-zone .button {
        margin: 10px 0;
    }

    .hp-drag-drop-zone .description {
        margin: 10px 0 0 0;
        color: #999;
        font-size: 14px;
    }

    /* Progress bar styling */
    .hp-progress-container {
        margin-top: 20px;
    }

    .hp-progress-bar {
        width: 100%;
        height: 20px;
        background: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 10px;
    }

    .hp-progress-bar-inner {
        height: 100%;
        background: linear-gradient(90deg, #0073aa, #005a87);
        width: 0%;
        transition: width 0.3s ease;
    }

    .hp-progress-text {
        text-align: center;
        color: #666;
    }

    .hp-progress-percentage {
        font-weight: bold;
        color: #0073aa;
    }
</style>