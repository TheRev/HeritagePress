<?php
/**
 * Display a notice when the Evidence Explained system has been successfully removed
 * 
 * @package HeritagePress\Admin\Notices
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Add an admin notice for successful Evidence System removal
 */
function heritage_press_evidence_removal_success_notice() {
    // Only show if the evidence system has been removed
    if (get_option('heritage_press_evidence_system_removed') === 'yes') {
        // Only show this notice once a day at most
        $last_shown = get_option('heritage_press_evidence_removal_notice_shown');
        $current_date = date('Y-m-d');
        
        if ($last_shown !== $current_date) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong><?php _e('Heritage Press Update:', 'heritage-press'); ?></strong> 
                <?php _e('Evidence Explained system has been successfully removed. Heritage Press is now running as a standard genealogy plugin.', 'heritage-press'); ?></p>
                <p><?php _e('Run the Health Check tool to verify everything is working correctly:', 'heritage-press'); ?> 
                <a href="<?php echo admin_url('admin.php?page=heritage-health-check'); ?>"><?php _e('System Health Check', 'heritage-press'); ?></a></p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text"><?php _e('Dismiss this notice.', 'heritage-press'); ?></span>
                </button>
            </div>
            <?php
            update_option('heritage_press_evidence_removal_notice_shown', $current_date);
        }
    }
}
add_action('admin_notices', 'heritage_press_evidence_removal_success_notice');

/**
 * Register dismissal handler for the notice
 */
function heritage_press_evidence_removal_notice_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '.notice-dismiss', function() {
            var $notice = $(this).closest('.notice');
            if ($notice.find('p:contains("Evidence Explained system")').length) {
                $.ajax({
                    url: ajaxurl,
                    data: {
                        action: 'heritage_press_dismiss_evidence_notice'
                    }
                });
            }
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'heritage_press_evidence_removal_notice_scripts');

/**
 * Ajax handler for notice dismissal
 */
function heritage_press_dismiss_evidence_notice_handler() {
    update_option('heritage_press_evidence_removal_notice_shown', date('Y-m-d', strtotime('+7 days')));
    wp_die();
}
add_action('wp_ajax_heritage_press_dismiss_evidence_notice', 'heritage_press_dismiss_evidence_notice_handler');
