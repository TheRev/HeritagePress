<?php
/**
 * Evidence Explained System Cleanup Script
 *
 * This script lists all the files that can be safely deleted after 
 * removing the Evidence Explained system from Heritage Press.
 *
 * @package HeritagePress
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * List of files that can be safely deleted after removing the Evidence Explained system
 */
function heritage_press_get_removable_evidence_files() {
    return array(
        // Database files
        'includes/database/class-evidence-manager.php',
        
        // Model files
        'includes/models/class-evidence-analysis.php',
        'includes/models/class-information-statement.php',
        'includes/models/class-proof-argument.php',
        'includes/models/class-research-question.php',
        'includes/models/class-source-quality-assessment.php',
        
        // Repository files
        'includes/repositories/class-evidence-analysis-repository.php',
        'includes/repositories/class-information-statement-repository.php',
        'includes/repositories/class-proof-argument-repository.php',
        'includes/repositories/class-research-question-repository.php',
        
        // Admin files
        'includes/admin/class-evidence-admin.php',
        
        // Admin view files
        'admin/views/evidence-analysis-detail.php',
        'admin/views/evidence-analysis-form.php',
        'admin/views/evidence-analysis-list.php',
        'admin/views/information-statement-form.php',
        'admin/views/information-statements-list.php',
        'admin/views/proof-argument-detail.php',
        'admin/views/proof-argument-form.php',
        'admin/views/proof-arguments-list.php',
        'admin/views/research-questions-form.php',
        'admin/views/research-questions-list.php',
        
        // Service files
        'includes/services/class-evidence-analysis-service.php',
        'includes/services/class-evidence-citation-formatter.php',
    );
}

/**
 * Render the Evidence system files cleanup page
 */
function heritage_press_render_evidence_cleanup_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Evidence Explained System File Cleanup', 'heritage-press'); ?></h1>
        
        <div class="notice notice-info">
            <p><?php _e('After removing the Evidence Explained system functionality, you can safely delete these files to clean up your plugin directory.', 'heritage-press'); ?></p>
        </div>
        
        <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
            <h2><?php _e('Files Safe to Delete', 'heritage-press'); ?></h2>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('File Path', 'heritage-press'); ?></th>
                        <th><?php _e('Status', 'heritage-press'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $files = heritage_press_get_removable_evidence_files();
                    foreach ($files as $file) {
                        $file_path = HERITAGE_PRESS_PLUGIN_DIR . $file;
                        $exists = file_exists($file_path);
                        ?>
                        <tr>
                            <td><?php echo esc_html($file); ?></td>
                            <td>
                                <?php if ($exists): ?>
                                    <span style="color: #007cba;">&#10003; <?php _e('Exists', 'heritage-press'); ?></span>
                                <?php else: ?>
                                    <span style="color: #999;">&ndash; <?php _e('Not Found', 'heritage-press'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
              <div style="margin-top: 20px;">
                <p><?php _e('You can delete these files using FTP or your server file manager after ensuring that the Evidence Explained system has been successfully removed.', 'heritage-press'); ?></p>
                <p><?php _e('Alternatively, you can use the automated removal script included with the plugin:', 'heritage-press'); ?></p>
                <pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">powershell -ExecutionPolicy Bypass -File "<?php echo HERITAGE_PRESS_PLUGIN_DIR; ?>remove-evidence-files.ps1"</pre>
                <p><strong><?php _e('Note:', 'heritage-press'); ?></strong> <?php _e('Always backup your site before deleting files. The script will automatically create backups of removed files.', 'heritage-press'); ?></p>
            </div>
        </div>
    </div>
    <?php
}

// Add file cleanup submenu page
function heritage_press_add_evidence_cleanup_menu() {
    add_submenu_page(
        'heritage-press',
        __('Evidence System File Cleanup', 'heritage-press'),
        __('Evidence File Cleanup', 'heritage-press'),
        'manage_options',
        'heritage-evidence-cleanup',
        'heritage_press_render_evidence_cleanup_page'
    );
}
add_action('admin_menu', 'heritage_press_add_evidence_cleanup_menu');
