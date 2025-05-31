<?php
/**
 * Evidence Explained System Removal Tool
 *
 * This script removes all components of the Evidence Explained system
 * as part of transitioning to a standard genealogy plugin.
 *
 * Usage: Run this script from the WordPress admin or command line
 * 
 * @package HeritagePress
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include the Evidence Table Remover
require_once HERITAGE_PRESS_PLUGIN_DIR . 'includes/database/sql/remove-evidence-tables.php';

/**
 * Main class to handle Evidence Explained system removal
 */
class Heritage_Press_Evidence_Remover {

    /**
     * Run the removal process
     */
    public function run() {
        $this->show_header();
        
        // Step 1: Drop database tables
        $this->drop_database_tables();
        
        // Step 2: Fix database relations
        $this->fix_database_relations();
        
        // Step 3: Update plugin options
        $this->update_plugin_options();
        
        $this->show_footer();
    }
    
    /**
     * Show header information
     */
    private function show_header() {
        echo '<div class="wrap">';
        echo '<h1>Heritage Press - Evidence Explained System Removal</h1>';
        echo '<p>This tool removes the Evidence Explained system components from the Heritage Press plugin.</p>';
        echo '<div class="notice notice-warning"><p><strong>Warning:</strong> This process cannot be undone. All Evidence Explained related data will be permanently deleted.</p></div>';
        echo '<div class="card" style="padding: 15px; margin-bottom: 20px;">';
    }
      /**
     * Show footer information
     */
    private function show_footer() {
        echo '</div>'; // Close card
        
        echo '<div class="success-message" style="margin-top: 20px; padding: 15px; background-color: #f0f8f0; border-left: 4px solid #46b450;">';
        echo '<h3>Cleanup Complete!</h3>';
        echo '<p>The Evidence Explained system has been successfully removed from Heritage Press.</p>';
        echo '<p><strong>Next Steps:</strong></p>';
        echo '<ul>';
        echo '<li>Use the File Cleanup tool to identify files that can be safely deleted</li>';
        echo '<li>Update any custom templates that may have referenced Evidence Explained components</li>';
        echo '</ul>';
        echo '</div>';
        
        echo '<div style="margin-top: 20px;">';
        echo '<a href="' . admin_url('admin.php?page=heritage-evidence-cleanup') . '" class="button button-primary">Go to File Cleanup Tool</a> ';
        echo '<a href="' . admin_url('admin.php?page=heritage-press') . '" class="button">Return to Heritage Press</a>';
        echo '</div>';
        
        echo '</div>'; // Close wrap
    }
    
    /**
     * Drop Evidence Explained specific database tables
     */    private function drop_database_tables() {
        echo '<h3>Step 1: Migrating and removing database tables</h3>';
        
        try {
            $table_remover = new \HeritagePress\Database\Evidence_Table_Remover();
            
            // First migrate any valuable data
            echo '<p>Migrating valuable data from Evidence Explained tables...</p>';
            $migrated = $table_remover->migrate_valuable_data();
            echo '<p>✅ Migrated data: ' . $migrated['information_statements'] . ' information statements and ' 
                 . $migrated['evidence_analysis'] . ' evidence analyses.</p>';
            
            // Then drop the tables
            echo '<p>Removing Evidence Explained tables...</p>';
            $table_remover->drop_evidence_tables();
            echo '<p>✅ Evidence Explained tables removed successfully.</p>';
        } catch (\Exception $e) {
            echo '<p>❌ Error: ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    /**
     * Fix any broken database relations
     */
    private function fix_database_relations() {
        echo '<h3>Step 2: Fixing database relations</h3>';
        
        try {
            $table_remover = new \HeritagePress\Database\Evidence_Table_Remover();
            $table_remover->fix_foreign_keys();
            echo '<p>✅ Database relations fixed successfully.</p>';
        } catch (\Exception $e) {
            echo '<p>❌ Error fixing database relations: ' . esc_html($e->getMessage()) . '</p>';
        }
    }
      /**
     * Update plugin options to reflect removal of Evidence Explained
     */
    private function update_plugin_options() {
        echo '<h3>Step 3: Updating plugin options</h3>';
        
        try {
            // Update options to indicate Evidence Explained system is removed
            update_option('heritage_press_evidence_explained_enabled', false);
            update_option('heritage_press_evidence_system_removed', true);
            update_option('heritage_press_evidence_removed_date', current_time('mysql'));
            
            // Add log entry for this significant change
            $user = wp_get_current_user();
            $log_message = sprintf(
                'Evidence Explained system removed by user %s (ID: %d) on %s',
                $user->user_login,
                $user->ID,
                current_time('mysql')
            );
            update_option('heritage_press_evidence_removal_log', $log_message);
            
            echo '<p>✅ Plugin options updated successfully.</p>';
        } catch (\Exception $e) {
            echo '<p>❌ Error updating plugin options: ' . esc_html($e->getMessage()) . '</p>';
        }
    }
}

// Initialize and run the remover
$evidence_remover = new Heritage_Press_Evidence_Remover();
$evidence_remover->run();
