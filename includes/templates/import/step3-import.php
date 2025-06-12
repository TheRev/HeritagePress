<?php
/**
 * Import Step 3: GEDCOM Processing
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get file key from URL (optional)
$file_key = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : '';

// Note: File key validation removed - proceeding with import regardless

// Get import options from post data
$tree_id = isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : 'new';
$new_tree_name = isset($_POST['new_tree_name']) ? sanitize_text_field($_POST['new_tree_name']) : '';
$import_option = isset($_POST['import_option']) ? sanitize_text_field($_POST['import_option']) : 'replace';
$import_media = isset($_POST['import_media']) ? (bool) $_POST['import_media'] : false;
$privacy_living = isset($_POST['privacy_living']) ? (bool) $_POST['privacy_living'] : false;
$privacy_notes = isset($_POST['privacy_notes']) ? (bool) $_POST['privacy_notes'] : false;

?>
<div class="hp-import-container">
    <h3><?php esc_html_e('Step 3: Importing GEDCOM', 'heritagepress'); ?></h3>

    <div class="hp-import-destination">
        <h4><?php esc_html_e('Import Destination', 'heritagepress'); ?></h4>
        <div class="destination-info">
            <strong>
                <?php if ($tree_id === 'new'): ?>
                    <?php printf(esc_html__('Creating new tree: %s', 'heritagepress'), esc_html($new_tree_name)); ?>
                <?php else: ?>
                    <?php
                    if (!empty($selected_tree_name)) {
                        printf(esc_html__('Importing into: %s', 'heritagepress'), esc_html($selected_tree_name));
                    } else {
                        printf(esc_html__('Importing into tree ID: %s', 'heritagepress'), esc_html($tree_id));
                    }
                    ?>
                <?php endif; ?>
            </strong>
        </div>
    </div>

    <div class="hp-import-progress">
        <div class="hp-progress-status">
            <span id="hp-current-operation"><?php esc_html_e('Initializing import...', 'heritagepress'); ?></span>
            <span id="hp-progress-percentage" class="hp-progress-right">0%</span>
        </div>

        <div class="hp-progress-bar">
            <div class="hp-progress-bar-inner" style="width: 0%"></div>
        </div>

        <div id="hp-current-detail" class="hp-progress-detail">
            <?php esc_html_e('Preparing data for import...', 'heritagepress'); ?>
        </div>
    </div>

    <div class="hp-import-stats">
        <h4><?php esc_html_e('Import Statistics', 'heritagepress'); ?></h4>

        <table class="widefat hp-data-table">
            <tbody>
                <tr>
                    <th><?php esc_html_e('Records Processed', 'heritagepress'); ?></th>
                    <td id="hp-stat-processed">0</td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Individuals', 'heritagepress'); ?></th>
                    <td id="hp-stat-individuals">0</td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Families', 'heritagepress'); ?></th>
                    <td id="hp-stat-families">0</td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Sources', 'heritagepress'); ?></th>
                    <td id="hp-stat-sources">0</td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Media Objects', 'heritagepress'); ?></th>
                    <td id="hp-stat-media">0</td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Notes', 'heritagepress'); ?></th>
                    <td id="hp-stat-notes">0</td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Time Elapsed', 'heritagepress'); ?></th>
                    <td id="hp-stat-time">0:00</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>    jQuery(document).ready(function ($) {
        console.log('=== STEP 3 TEMPLATE DEBUG ===');
        console.log('Step 3 import template loaded');
        
        // Check if ajaxurl is available
        if (typeof ajaxurl !== 'undefined') {
            console.log('✅ ajaxurl is available:', ajaxurl);
        } else {
            console.log('❌ ajaxurl is NOT available - defining fallback');
            // Define ajaxurl as fallback
            window.ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            console.log('Created fallback ajaxurl:', window.ajaxurl);
        }
        
        // Variables
        var fileKey = '<?php echo esc_js($file_key); ?>';
        var treeId = '<?php echo esc_js($tree_id); ?>';
        var newTreeName = '<?php echo esc_js($new_tree_name); ?>';
        var importOption = '<?php echo esc_js($import_option); ?>';
        var importMedia = <?php echo $import_media ? 'true' : 'false'; ?>;
        var privacyLiving = <?php echo $privacy_living ? 'true' : 'false'; ?>;
        var privacyNotes = <?php echo $privacy_notes ? 'true' : 'false'; ?>;

        var importStarted = false;
        var importComplete = false;
        var importError = false;
        var progressInterval;
        var startTime = new Date();        // Start the import process
        function startImport() {
            if (importStarted) return;
            importStarted = true;

            // Prepare import data - file_key is optional
            var importData = {
                action: 'hp_process_gedcom',
                hp_gedcom_nonce: '<?php echo wp_create_nonce('hp_gedcom_upload'); ?>',
                tree_id: treeId,
                new_tree_name: newTreeName,
                import_option: importOption,
                import_media: importMedia ? 1 : 0,
                privacy_living: privacyLiving ? 1 : 0,
                privacy_notes: privacyNotes ? 1 : 0
            };

            // Add file_key only if it exists
            if (fileKey && fileKey.length > 0) {
                importData.file_key = fileKey;
            }            // Make the initial request to start import
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: importData,
                success: function (response) {
                    if (response.success) {                        // Check if import completed immediately (small files complete fast)
                        if (response.data && response.data.completed) {
                            // Import completed in initial request
                            importComplete = true;
                            
                            // Use the redirect URL provided by the server if available
                            if (response.data.redirect_url) {
                                console.log('STEP 3 DEBUG - Import completed immediately, using server-provided redirect URL:', response.data.redirect_url);
                                window.location.href = response.data.redirect_url;
                            } else {
                                // Fallback to our custom redirect logic
                                redirectToResults();
                            }
                        } else {
                            // Import still in progress, start checking progress
                            progressInterval = setInterval(checkImportProgress, 2000);
                        }
                    } else {
                        handleImportError(response.data.message || 'Unknown error occurred');
                    }
                },
                error: function () {
                    handleImportError('Failed to start the import process');
                }
            });
        }        // Check the import progress
        function checkImportProgress() {
            // Prepare progress check data - file_key is optional
            var progressData = {
                action: 'hp_import_progress',
                nonce: '<?php echo wp_create_nonce('hp_import_progress'); ?>'
            };

            // Add file_key only if it exists
            if (fileKey && fileKey.length > 0) {
                progressData.file_key = fileKey;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: progressData, success: function (response) {
                    if (response.success) {
                        updateProgress(response.data);

                        // Check for errors in progress data
                        if (response.data.error) {
                            clearInterval(progressInterval);
                            handleImportError(response.data.error);
                            return;
                        }                        if (response.data.completed) {
                            clearInterval(progressInterval);
                            importComplete = true;
                            
                            // Use the redirect URL provided by the server if available
                            if (response.data.redirect_url) {
                                console.log('STEP 3 DEBUG - Using server-provided redirect URL:', response.data.redirect_url);
                                window.location.href = response.data.redirect_url;
                            } else {
                                // Fallback to our custom redirect logic
                                redirectToResults();
                            }
                        }
                    } else {
                        handleImportError(response.data.message || 'Error checking import progress');
                    }
                },
                error: function () {
                    // Don't fail immediately, maybe just a temporary connection issue
                    console.log('Failed to check progress, will retry');
                }
            });
        }

        // Update the progress display
        function updateProgress(data) {
            var percent = Math.round(data.percent);
            $('#hp-progress-percentage').text(percent + '%');
            $('.hp-progress-bar-inner').css('width', percent + '%');

            $('#hp-current-operation').text(data.operation || 'Processing...');
            $('#hp-current-detail').text(data.detail || '');

            // Update stats
            $('#hp-stat-processed').text(data.stats.processed || 0);
            $('#hp-stat-individuals').text(data.stats.individuals || 0);
            $('#hp-stat-families').text(data.stats.families || 0);
            $('#hp-stat-sources').text(data.stats.sources || 0);
            $('#hp-stat-media').text(data.stats.media || 0);
            $('#hp-stat-notes').text(data.stats.notes || 0);

            // Update elapsed time
            var elapsed = Math.floor((new Date() - startTime) / 1000);
            var minutes = Math.floor(elapsed / 60);
            var seconds = elapsed % 60;
            $('#hp-stat-time').text(minutes + ':' + (seconds < 10 ? '0' : '') + seconds);
        }        // Handle import errors
        function handleImportError(message) {
            clearInterval(progressInterval);
            importError = true;

            $('#hp-current-operation').text('<?php esc_html_e('Import Error', 'heritagepress'); ?>');
            $('#hp-current-detail').text(message);
            $('#hp-current-detail').addClass('hp-error-message');            // Add retry button and go to results page to show error details
            var resultsUrl = '<?php echo esc_url(admin_url('admin.php?page=heritagepress-import-export&tab=import&step=4&error=1')); ?>';
            if (fileKey && fileKey.length > 0) {
                resultsUrl += '&file=' + encodeURIComponent(fileKey);
            }
            if (treeId && treeId.length > 0) {
                resultsUrl += '&tree_id=' + encodeURIComponent(treeId);
            }
            if (newTreeName && newTreeName.length > 0) {
                resultsUrl += '&new_tree_name=' + encodeURIComponent(newTreeName);
            }
            if (importOption && importOption.length > 0) {
                resultsUrl += '&import_option=' + encodeURIComponent(importOption);
            } var backUrl = '<?php echo esc_url(admin_url('admin.php?page=heritagepress-import-export&tab=import&step=2')); ?>';
            if (fileKey && fileKey.length > 0) {
                backUrl += '&file=' + encodeURIComponent(fileKey);
            }
            if (treeId && treeId.length > 0) {
                backUrl += '&tree_id=' + encodeURIComponent(treeId);
            }
            if (newTreeName && newTreeName.length > 0) {
                backUrl += '&new_tree_name=' + encodeURIComponent(newTreeName);
            }
            if (importOption && importOption.length > 0) {
                backUrl += '&import_option=' + encodeURIComponent(importOption);
            }

            $('.hp-import-progress').append(
                '<div class="hp-form-actions">' +
                '<button type="button" class="button" id="hp-retry-import">' +
                '<?php esc_html_e('Retry', 'heritagepress'); ?>' +
                '</button> ' +
                '<a href="' + resultsUrl + '" class="button button-primary">' +
                '<?php esc_html_e('View Error Details', 'heritagepress'); ?>' +
                '</a> ' +
                '<a href="' + backUrl + '" class="button">' +
                '<?php esc_html_e('Back to Validation', 'heritagepress'); ?>' +
                '</a>' +
                '</div>'
            );

            // Bind retry event
            $('#hp-retry-import').on('click', function () {
                location.reload();
            });
        }        // Redirect to the results page
        function redirectToResults() {
            console.log('STEP 3 DEBUG - Redirecting to results page');
            console.log('  fileKey:', fileKey);
            console.log('  treeId:', treeId);
            console.log('  newTreeName:', newTreeName);
            console.log('  importOption:', importOption);
            
            // Create a hidden form to POST the completion data to Step 4
            var form = $('<form>', {
                'method': 'POST',
                'action': '<?php echo esc_url(admin_url('admin.php?page=heritagepress-import-export&tab=import&step=4')); ?>'
            });
            
            // Add all the completion data as hidden fields
            form.append($('<input>', { 'type': 'hidden', 'name': 'file_key', 'value': fileKey }));
            form.append($('<input>', { 'type': 'hidden', 'name': 'tree_id', 'value': treeId }));
            form.append($('<input>', { 'type': 'hidden', 'name': 'new_tree_name', 'value': newTreeName }));
            form.append($('<input>', { 'type': 'hidden', 'name': 'import_option', 'value': importOption }));
            form.append($('<input>', { 'type': 'hidden', 'name': 'import_media', 'value': importMedia ? '1' : '0' }));
            form.append($('<input>', { 'type': 'hidden', 'name': 'privacy_living', 'value': privacyLiving ? '1' : '0' }));
            form.append($('<input>', { 'type': 'hidden', 'name': 'privacy_notes', 'value': privacyNotes ? '1' : '0' }));
            form.append($('<input>', { 'type': 'hidden', 'name': 'completed_at', 'value': Date.now() }));
            form.append($('<input>', { 'type': 'hidden', 'name': 'hp_import_completion_nonce', 'value': '<?php echo wp_create_nonce('hp_import_completion'); ?>' }));
            
            // Append to body and submit
            $('body').append(form);
            console.log('STEP 3 DEBUG - Submitting POST form to Step 4');
            form.submit();
        }

        // Start import on page load
        startImport();
    });
</script>