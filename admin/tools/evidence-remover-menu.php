<?php
/**
 * Add Evidence Remover tool to WordPress admin menu
 *
 * @package HeritagePress
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Add Evidence Remover menu item to the Heritage Press admin menu
 */
function heritage_press_add_evidence_remover_menu() {
    add_submenu_page(
        'heritage-press',
        __('Remove Evidence Explained System', 'heritage-press'),
        __('Remove Evidence System', 'heritage-press'),
        'manage_options',
        'heritage-evidence-remover',
        'heritage_press_render_evidence_remover_page'
    );
}
add_action('admin_menu', 'heritage_press_add_evidence_remover_menu');

/**
 * Render the Evidence Remover page
 */
function heritage_press_render_evidence_remover_page() {
    // Check if the form was submitted
    if (isset($_POST['confirm_remove_evidence']) && $_POST['confirm_remove_evidence'] === 'yes') {
        // Verify nonce
        if (!isset($_POST['evidence_remover_nonce']) || 
            !wp_verify_nonce($_POST['evidence_remover_nonce'], 'heritage_press_remove_evidence')) {
            wp_die('Security check failed');
        }
        
        // Include and run the remover
        require_once HERITAGE_PRESS_PLUGIN_DIR . 'admin/tools/remove-evidence-system.php';
        
    } else {
        // Show confirmation form
        ?>
        <div class="wrap">
            <h1><?php _e('Remove Evidence Explained System', 'heritage-press'); ?></h1>
            
            <div class="notice notice-warning">
                <p><strong><?php _e('Warning:', 'heritage-press'); ?></strong> <?php _e('This action will permanently remove all Evidence Explained system components and data. This cannot be undone.', 'heritage-press'); ?></p>
            </div>
            
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('About This Tool', 'heritage-press'); ?></h2>
                
                <p><?php _e('This tool will remove the following Evidence Explained specific components:', 'heritage-press'); ?></p>
                
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><?php _e('Evidence Explained database tables', 'heritage-press'); ?></li>
                    <li><?php _e('Research questions system', 'heritage-press'); ?></li>
                    <li><?php _e('Information statements', 'heritage-press'); ?></li>
                    <li><?php _e('Evidence analysis', 'heritage-press'); ?></li>
                    <li><?php _e('Proof arguments', 'heritage-press'); ?></li>
                    <li><?php _e('Source quality assessment system', 'heritage-press'); ?></li>
                </ul>
                
                <p><strong><?php _e('The following data will be preserved:', 'heritage-press'); ?></strong></p>
                
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><?php _e('All individual records', 'heritage-press'); ?></li>
                    <li><?php _e('All family records', 'heritage-press'); ?></li>
                    <li><?php _e('All event records', 'heritage-press'); ?></li>
                    <li><?php _e('Basic sources and citations', 'heritage-press'); ?></li>
                    <li><?php _e('GEDCOM import capability', 'heritage-press'); ?></li>
                    <li><?php _e('RootsMagic import capability', 'heritage-press'); ?></li>
                </ul>
                
                <form method="post" action="">
                    <?php wp_nonce_field('heritage_press_remove_evidence', 'evidence_remover_nonce'); ?>
                    
                    <p>
                        <label>
                            <input type="checkbox" name="confirm_remove_evidence" value="yes" required>
                            <?php _e('I understand that this action is irreversible and will permanently delete all Evidence Explained system data.', 'heritage-press'); ?>
                        </label>
                    </p>
                    
                    <p>
                        <input type="submit" class="button button-primary" value="<?php _e('Remove Evidence Explained System', 'heritage-press'); ?>">
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
}
