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
    }

    /**
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
            case 'export':
                $this->render_export_tab();
                break;

            case 'settings':
                $this->render_settings_tab();
                break;

            case 'logs':
                $this->render_logs_tab();
                break;

            case 'import':
            default:
                $this->render_import_tab();
                break;
        }

        // Include footer
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

        // Use the import.php template which will include the correct step
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/templates/import/import.php';
    }

    /**
     * Render the export tab
     */
    private function render_export_tab()
    {
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
        $tab_name = strtolower(preg_replace('/[^a-z0-9\-]/', '', $tab_name));

        // Make sure it's a valid tab
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
}
