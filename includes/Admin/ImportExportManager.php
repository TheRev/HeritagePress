<?php
/**
 * Import/Export Manager for HeritagePress
 *
 * Handles the tabbed interface for GEDCOM import and export
 *
 * @package HeritagePress
 * @subpackage Admin
 */

namespace HeritagePress\Admin;

use HeritagePress\Admin\ImportExport\ImportHandler;
use HeritagePress\Admin\ImportExport\ExportHandler;
use HeritagePress\Admin\ImportExport\DateHandler;
use HeritagePress\Admin\ImportExport\SettingsHandler;
use HeritagePress\Admin\ImportExport\LogsHandler;
use HeritagePress\Admin\DatabaseOperations;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ImportExportManager
 *
 * Manages the tabbed interface for import/export operations using modular handlers
 */
class ImportExportManager
{
    // Use DatabaseOperations trait to access tree data
    use DatabaseOperations;

    /**
     * @var ImportHandler
     */
    private $import_handler;

    /**
     * @var ExportHandler
     */
    private $export_handler;

    /**
     * @var DateHandler
     */
    private $date_handler;

    /**
     * @var SettingsHandler
     */
    private $settings_handler;

    /**
     * @var LogsHandler
     */
    private $logs_handler;

    /**
     * @var \wpdb WordPress database object
     */
    private $wpdb;

    /**
     * Constructor
     * 
     * @param object $plugin Optional plugin instance
     */
    public function __construct($plugin = null)
    {
        // Initialize modular handlers
        $this->import_handler = new ImportHandler();
        $this->export_handler = new ExportHandler();
        $this->date_handler = new DateHandler();
        $this->settings_handler = new SettingsHandler();
        $this->logs_handler = new LogsHandler();

        // Initialize WordPress database object
        global $wpdb;
        $this->wpdb = $wpdb;
    }    /**
         * Render the main import/export page
         */
    public function render_page()
    {
        // Get current tab from URL, default to 'import'
        $current_tab = isset($_GET['tab']) ? $this->sanitize_tab_name($_GET['tab']) : 'import';

        // Tab definitions
        $tabs = array(
            'import' => __('Import GEDCOM', 'heritagepress'),
            'export' => __('Export GEDCOM', 'heritagepress'),
            'settings' => __('Settings', 'heritagepress'),
            'logs' => __('Import Logs', 'heritagepress')
        );

        // Start output buffer
        ob_start();

        // Include header template with tabs
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/templates/shared/header.php';

        // Include the current tab content
        switch ($current_tab) {
            case 'import':
                $this->render_import_tab();
                break;

            case 'export':
                $this->render_export_tab();
                break;

            case 'settings':
                $this->render_settings_tab();
                break;

            case 'logs':
                $this->render_logs_tab();
                break;
            default:
                $this->render_import_tab();
                break;
        }        // Include footer
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/templates/shared/footer.php';

        // Output the page content
        echo ob_get_clean();
    }

    /**
     * Render the import tab
     */
    private function render_import_tab()
    {
        // Get the import step, default to 1
        $step = isset($_GET['step']) ? intval($_GET['step']) : 1;

        // DEBUG: Add step routing debugging
        error_log('ImportExportManager::render_import_tab() - Step routing debug:');
        error_log('  $_GET[step]: ' . (isset($_GET['step']) ? $_GET['step'] : 'NOT SET'));
        error_log('  intval($_GET[step]): ' . (isset($_GET['step']) ? intval($_GET['step']) : 'N/A'));
        error_log('  Final $step value: ' . $step);
        error_log('  $_GET contents: ' . print_r($_GET, true));

        // Get available trees for import selection
        $trees = $this->get_trees();

        // Get additional data that might be needed in various steps
        $file_key = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : '';
        $tree_id = isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : (isset($_GET['tree_id']) ? sanitize_text_field($_GET['tree_id']) : '');
        $new_tree_name = isset($_POST['new_tree_name']) ? sanitize_text_field($_POST['new_tree_name']) : (isset($_GET['new_tree_name']) ? sanitize_text_field($_GET['new_tree_name']) : '');
        $import_option = isset($_POST['import_option']) ? sanitize_text_field($_POST['import_option']) : (isset($_GET['import_option']) ? sanitize_text_field($_GET['import_option']) : 'replace');

        // If we have a tree_id, get the tree information
        $selected_tree = null;
        $selected_tree_name = '';
        if (!empty($tree_id) && $tree_id !== 'new') {
            foreach ($trees as $tree) {
                if ($tree->id == $tree_id) {
                    $selected_tree = $tree;
                    $selected_tree_name = $tree->title;
                    break;
                }
            }
        } elseif ($tree_id === 'new' && !empty($new_tree_name)) {
            $selected_tree_name = $new_tree_name;
        }

        // Use the import.php template which will include the correct step
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/templates/import/import.php';
    }

    /**
     * Render the export tab
     */
    private function render_export_tab()
    {
        // Get available trees for export selection
        $trees = $this->get_trees();

        include HERITAGEPRESS_PLUGIN_DIR . 'includes/templates/export/export.php';
    }

    /**
     * Render the settings tab
     */
    private function render_settings_tab()
    {
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/templates/shared/settings.php';
    }

    /**
     * Render the logs tab
     */
    private function render_logs_tab()
    {
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/templates/shared/logs.php';
    }

    /**
     * Sanitize tab name to prevent malicious input
     *
     * @param string $tab_name The tab name to sanitize
     * @return string Sanitized tab name
     */
    private function sanitize_tab_name($tab_name)
    {
        // Only allow lowercase alphanumeric characters and hyphens
        $tab_name = strtolower(preg_replace('/[^a-z0-9\-]/', '', $tab_name));        // Make sure it's a valid tab
        $valid_tabs = ['import', 'export', 'settings', 'logs'];
        if (!in_array($tab_name, $valid_tabs)) {
            return 'import'; // Default to import tab if invalid
        }

        return $tab_name;
    }

    /**
     * Get import handler instance
     * 
     * @return ImportHandler
     */
    public function get_import_handler()
    {
        return $this->import_handler;
    }

    /**
     * Get export handler instance
     * 
     * @return ExportHandler
     */
    public function get_export_handler()
    {
        return $this->export_handler;
    }

    /**
     * Get date handler instance
     * 
     * @return DateHandler
     */
    public function get_date_handler()
    {
        return $this->date_handler;
    }

    /**
     * Get settings handler instance
     * 
     * @return SettingsHandler
     */
    public function get_settings_handler()
    {
        return $this->settings_handler;
    }

    /**
     * Get logs handler instance
     * 
     * @return LogsHandler
     */
    public function get_logs_handler()
    {
        return $this->logs_handler;
    }

    /**
     * Get DateConverter instance for backward compatibility
     * 
     * @return DateConverter
     */
    public function get_date_converter()
    {
        return $this->date_handler->get_date_converter();
    }

    /**
     * Get GedcomService instance for backward compatibility
     * 
     * @return object GedcomService instance
     */
    public function get_gedcom_service()
    {
        return $this->import_handler->get_gedcom_service();
    }

    /**
     * Add a log entry using the logs handler
     *
     * @param string $type Log type
     * @param string $action Action performed
     * @param string $message Log message
     * @param array $details Additional details
     * @return bool Success status
     */
    public function add_log($type, $action, $message, $details = array())
    {
        return $this->logs_handler->add_log($type, $action, $message, $details);
    }

    /**
     * Validate date string using date handler
     *
     * @param string $date_string Date string to validate
     * @return array Validation result
     */
    public function validate_date($date_string)
    {
        return $this->date_handler->validate_date_string($date_string);
    }

    /**
     * Get the list of family trees for import/export
     * 
     * @return array List of family trees
     */
    public function get_family_trees()
    {
        // Query the database for family trees
        $query = "SELECT * FROM {$this->wpdb->prefix}family_trees";
        $results = $this->wpdb->get_results($query);

        return $results;
    }
}