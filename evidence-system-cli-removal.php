<?php
/**
 * Heritage Press Evidence System CLI Removal Tool
 *
 * This script provides a CLI interface for removing the Evidence Explained system
 * from the Heritage Press plugin.
 *
 * Usage:
 * wp eval-file evidence-system-cli-removal.php
 *
 * @package HeritagePress\CLI
 */

// Exit if accessed directly (not through WP-CLI)
if (!defined('WP_CLI') || !WP_CLI) {
    echo "This script can only be run from WP-CLI.\n";
    echo "Usage: wp eval-file evidence-system-cli-removal.php\n";
    exit(1);
}

// Load required files
require_once dirname(__FILE__) . '/includes/database/sql/remove-evidence-tables.php';

// Display banner
WP_CLI::log("\n=========================================");
WP_CLI::log("Heritage Press - Evidence System Remover");
WP_CLI::log("=========================================\n");

// Confirm action
WP_CLI::warning("This will permanently remove the Evidence Explained system from Heritage Press.");
WP_CLI::warning("All Evidence Explained related data will be deleted.\n");

$confirm = WP_CLI\Utils\get_flag_value($assoc_args, 'yes', false);

if (!$confirm) {
    WP_CLI::confirm("Are you sure you want to proceed?");
}

// Begin removal process
WP_CLI::log("Starting removal process...\n");

try {    // Step 1: Migrate and drop tables
    WP_CLI::log("Step 1a: Migrating valuable data from Evidence Explained tables...");
    $table_remover = new HeritagePress\Database\Evidence_Table_Remover();
    $migrated = $table_remover->migrate_valuable_data();
    WP_CLI::success("Migrated " . $migrated['information_statements'] . " information statements and " 
                   . $migrated['evidence_analysis'] . " evidence analyses.");
    
    WP_CLI::log("Step 1b: Dropping Evidence Explained tables...");
    $table_remover->drop_evidence_tables();
    WP_CLI::success("Evidence Explained tables removed successfully.");
    
    // Step 2: Fix database relations
    WP_CLI::log("\nStep 2: Fixing database relations...");
    $table_remover->fix_foreign_keys();
    WP_CLI::success("Database relations fixed successfully.");
    
    // Step 3: Update plugin options
    WP_CLI::log("\nStep 3: Updating plugin options...");
    update_option('heritage_press_evidence_explained_enabled', false);
    update_option('heritage_press_evidence_system_removed', true);
    update_option('heritage_press_evidence_removed_date', current_time('mysql'));
    
    // Add log entry
    $log_message = sprintf(
        'Evidence Explained system removed via CLI on %s',
        current_time('mysql')
    );
    update_option('heritage_press_evidence_removal_log', $log_message);
    WP_CLI::success("Plugin options updated successfully.");
    
    // Final message
    WP_CLI::success("\nEvidence Explained system successfully removed!");
    WP_CLI::log("\nRemaining tasks:");
    WP_CLI::log("1. Remove Evidence Explained files using the PowerShell script:");
    WP_CLI::log("   powershell -ExecutionPolicy Bypass -File \"" . HERITAGE_PRESS_PLUGIN_DIR . "remove-evidence-files.ps1\"");
    WP_CLI::log("2. Update any custom templates that may have referenced Evidence Explained components");
    
} catch (Exception $e) {
    WP_CLI::error("Error during removal process: " . $e->getMessage());
}
