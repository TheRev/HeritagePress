<?php
/**
 * Settings Handler for HeritagePress
 *
 * @package HeritagePress
 * @subpackage Admin
 */

namespace HeritagePress\Admin\ImportExport;

/**
 * Class SettingsHandler
 *
 * Handles import/export settings operations
 */
class SettingsHandler extends BaseManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Register settings-specific AJAX handlers
        add_action('wp_ajax_hp_save_import_export_settings', array($this, 'save_import_export_settings'));
    }

    /**
     * Save Import/Export Settings via AJAX
     */
    public function save_import_export_settings()
    {
        // Check nonce
        check_ajax_referer('hp_importexport_settings', 'hp_settings_nonce');

        // Get settings from POST data
        $import_settings = isset($_POST['import_settings']) ? $this->sanitize_settings($_POST['import_settings']) : array();
        $export_settings = isset($_POST['export_settings']) ? $this->sanitize_settings($_POST['export_settings']) : array();
        $advanced_settings = isset($_POST['advanced_settings']) ? $this->sanitize_settings($_POST['advanced_settings']) : array();

        // Merge advanced settings into import settings for storage
        if (is_array($advanced_settings) && !empty($advanced_settings)) {
            $import_settings = array_merge($import_settings, $advanced_settings);
        }

        // Prepare boolean values for checkboxes (will be absent if unchecked)
        $import_checkbox_fields = array('import_media', 'privacy_living');
        foreach ($import_checkbox_fields as $field) {
            $import_settings[$field] = isset($import_settings[$field]) ? 1 : 0;
        }

        $export_checkbox_fields = array('privacy_living', 'privacy_notes', 'privacy_media');
        foreach ($export_checkbox_fields as $field) {
            $export_settings[$field] = isset($export_settings[$field]) ? 1 : 0;
        }

        // Save settings to WordPress options
        $import_saved = update_option('heritagepress_import_settings', $import_settings);
        $export_saved = update_option('heritagepress_import_settings', $export_settings);

        // Log the settings save
        $this->log_settings_change('settings_saved', 'Import/Export settings updated', array(
            'import_settings' => $import_settings,
            'export_settings' => $export_settings
        ));

        if ($import_saved || $export_saved) {
            wp_send_json_success(array(
                'message' => __('Settings saved successfully', 'heritagepress')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save settings', 'heritagepress')
            ));
        }
    }

    /**
     * Get import settings
     *
     * @return array Import settings
     */
    public function get_import_settings()
    {
        $defaults = array(
            'import_media' => 1,
            'privacy_living' => 1,
            'default_tree_action' => 'new',
            'gedcom_encoding' => 'UTF-8',
            'max_upload_size' => '50MB'
        );

        $saved_settings = get_option('heritagepress_import_settings', array());
        return array_merge($defaults, $saved_settings);
    }

    /**
     * Get export settings
     *
     * @return array Export settings
     */
    public function get_export_settings()
    {
        $defaults = array(
            'default_format' => 'gedcom',
            'gedcom_version' => '5.5.1',
            'privacy_living' => 0,
            'privacy_notes' => 0,
            'privacy_media' => 0,
            'include_media' => 1,
            'line_breaks' => 1
        );

        $saved_settings = get_option('heritagepress_export_settings', array());
        return array_merge($defaults, $saved_settings);
    }

    /**
     * Sanitize settings array
     *
     * @param array $settings Settings to sanitize
     * @return array Sanitized settings
     */
    private function sanitize_settings($settings)
    {
        if (!is_array($settings)) {
            return array();
        }

        $sanitized = array();
        foreach ($settings as $key => $value) {
            $sanitized_key = sanitize_key($key);

            if (is_array($value)) {
                $sanitized[$sanitized_key] = $this->sanitize_settings($value);
            } elseif (is_bool($value) || $value === '1' || $value === '0') {
                $sanitized[$sanitized_key] = (bool) $value;
            } elseif (is_numeric($value)) {
                $sanitized[$sanitized_key] = is_int($value) ? intval($value) : floatval($value);
            } else {
                $sanitized[$sanitized_key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * Reset settings to defaults
     *
     * @param string $type Settings type ('import', 'export', or 'all')
     * @return bool Success status
     */
    public function reset_settings($type = 'all')
    {
        $success = true;

        switch ($type) {
            case 'import':
                $success = delete_option('heritagepress_import_settings');
                break;
            case 'export':
                $success = delete_option('heritagepress_export_settings');
                break;
            case 'all':
            default:
                $import_reset = delete_option('heritagepress_import_settings');
                $export_reset = delete_option('heritagepress_export_settings');
                $success = $import_reset && $export_reset;
                break;
        }

        if ($success) {
            $this->log_settings_change('settings_reset', "Settings reset to defaults: {$type}");
        }

        return $success;
    }

    /**
     * Log settings changes
     *
     * @param string $action Action performed
     * @param string $message Log message
     * @param array $details Additional details
     */
    private function log_settings_change($action, $message, $details = array())
    {
        // Get current logs
        $logs = get_option('heritagepress_importexport_logs', array());

        // Add new log entry
        $logs[] = array(
            'timestamp' => current_time('timestamp'),
            'action' => $action,
            'message' => $message,
            'details' => $details,
            'user_id' => get_current_user_id()
        );

        // Trim logs to keep only the last 100 entries
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }

        // Update logs in database
        update_option('heritagepress_importexport_logs', $logs);
    }

    /**
     * Get available GEDCOM versions
     *
     * @return array Available versions
     */
    public function get_gedcom_versions()
    {
        return array(
            '5.5.1' => 'GEDCOM 5.5.1',
            '7.0' => 'GEDCOM 7.0'
        );
    }

    /**
     * Get available export formats
     *
     * @return array Available formats
     */
    public function get_export_formats()
    {
        return array(
            'gedcom' => __('Standard GEDCOM (.ged)', 'heritagepress'),
            'gedzip' => __('GEDZIP (GEDCOM with media)', 'heritagepress'),
            'json' => __('JSON (API format)', 'heritagepress')
        );
    }

    /**
     * Get default tree actions
     *
     * @return array Available actions
     */
    public function get_tree_actions()
    {
        return array(
            'new' => __('Create new tree', 'heritagepress'),
            'replace' => __('Replace existing tree', 'heritagepress'),
            'merge' => __('Merge with existing tree', 'heritagepress')
        );
    }

    /**
     * Validate settings before saving
     *
     * @param array $settings Settings to validate
     * @param string $type Settings type ('import' or 'export')
     * @return array Validation result
     */
    public function validate_settings($settings, $type)
    {
        $errors = array();
        $valid_settings = array();

        switch ($type) {
            case 'import':
                $valid_settings = $this->validate_import_settings($settings, $errors);
                break;
            case 'export':
                $valid_settings = $this->validate_export_settings($settings, $errors);
                break;
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'settings' => $valid_settings
        );
    }

    /**
     * Validate import settings
     *
     * @param array $settings Settings to validate
     * @param array &$errors Error array reference
     * @return array Validated settings
     */
    private function validate_import_settings($settings, &$errors)
    {
        $valid_settings = array();

        // Validate max upload size
        if (isset($settings['max_upload_size'])) {
            if (!preg_match('/^\d+[KMGT]?B?$/i', $settings['max_upload_size'])) {
                $errors[] = __('Invalid upload size format', 'heritagepress');
            } else {
                $valid_settings['max_upload_size'] = strtoupper($settings['max_upload_size']);
            }
        }

        // Validate encoding
        if (isset($settings['gedcom_encoding'])) {
            $valid_encodings = array('UTF-8', 'ISO-8859-1', 'Windows-1252');
            if (!in_array($settings['gedcom_encoding'], $valid_encodings)) {
                $errors[] = __('Invalid GEDCOM encoding', 'heritagepress');
            } else {
                $valid_settings['gedcom_encoding'] = $settings['gedcom_encoding'];
            }
        }

        return $valid_settings;
    }

    /**
     * Validate export settings
     *
     * @param array $settings Settings to validate
     * @param array &$errors Error array reference
     * @return array Validated settings
     */
    private function validate_export_settings($settings, &$errors)
    {
        $valid_settings = array();

        // Validate GEDCOM version
        if (isset($settings['gedcom_version'])) {
            $valid_versions = array_keys($this->get_gedcom_versions());
            if (!in_array($settings['gedcom_version'], $valid_versions)) {
                $errors[] = __('Invalid GEDCOM version', 'heritagepress');
            } else {
                $valid_settings['gedcom_version'] = $settings['gedcom_version'];
            }
        }

        // Validate export format
        if (isset($settings['default_format'])) {
            $valid_formats = array_keys($this->get_export_formats());
            if (!in_array($settings['default_format'], $valid_formats)) {
                $errors[] = __('Invalid export format', 'heritagepress');
            } else {
                $valid_settings['default_format'] = $settings['default_format'];
            }
        }

        return $valid_settings;
    }
}
