<?php
/**
 * Admin Notices for Evidence System Removal
 *
 * @package HeritagePress
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display admin notice about Evidence System Removal Tool
 */
function heritage_press_evidence_removal_notice() {
    // Only show on Heritage Press admin pages
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'heritage-press') === false) {
        return;
    }
    
    // Don't show on the remover page itself
    if (isset($_GET['page']) && $_GET['page'] === 'heritage-evidence-remover') {
        return;
    }
    
    // Check if we've already shown this notice and user dismissed it
    $dismissed = get_option('heritage_press_evidence_removal_notice_dismissed', false);
    if ($dismissed) {
        return;
    }
    
    ?>
    <div class="notice notice-info is-dismissible heritage-press-evidence-notice">
        <h3>Heritage Press Plugin Update</h3>
        <p>
            The Heritage Press plugin has been refactored as a standard genealogy plugin. 
            You can now remove the Evidence Explained system components that are no longer needed.
        </p>
        <p>
            <a href="<?php echo admin_url('admin.php?page=heritage-evidence-remover'); ?>" class="button button-primary">
                Remove Evidence System
            </a>
            <a href="<?php echo admin_url('admin.php?page=heritage-evidence-cleanup'); ?>" class="button">
                View File Cleanup
            </a>
            <a href="#" class="dismiss-forever" style="margin-left: 15px;">
                Dismiss this notice forever
            </a>
        </p>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '.heritage-press-evidence-notice .dismiss-forever', function(e) {
            e.preventDefault();
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'heritage_press_dismiss_evidence_notice',
                    nonce: '<?php echo wp_create_nonce('heritage_press_dismiss_notice'); ?>'
                }
            });
            $(this).closest('.notice').fadeTo(100, 0, function() {
                $(this).slideUp(100, function() {
                    $(this).remove();
                });
            });
        });
    });
    </script>
    <?php
}
add_action('admin_notices', 'heritage_press_evidence_removal_notice');

/**
 * AJAX handler for dismissing the notice permanently
 */
function heritage_press_dismiss_evidence_notice_ajax() {
    // Verify nonce
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'heritage_press_dismiss_notice')) {
        wp_send_json_error('Invalid nonce');
        exit;
    }
    
    // Update option to mark notice as dismissed
    update_option('heritage_press_evidence_removal_notice_dismissed', true);
    
    wp_send_json_success('Notice dismissed');
    exit;
}
add_action('wp_ajax_heritage_press_dismiss_evidence_notice', 'heritage_press_dismiss_evidence_notice_ajax');
