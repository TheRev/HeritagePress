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

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include WordPress file functions if not available
if (!function_exists('wp_upload_dir')) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
}

// Include WordPress post functions if not available
if (!function_exists('sanitize_file_name')) {
    require_once(ABSPATH . 'wp-includes/formatting.php');
}

// No additional includes needed - we'll use our own sanitization methods

/**
 * Class ImportExportManager
 *
 * Manages the tabbed interface for import/export operations
 */
class ImportExportManager
{    /**
     * Constructor
     * 
     * @param object $plugin Optional plugin instance
     */
    public function __construct($plugin = null)
    {
        // Register AJAX handlers
        add_action('wp_ajax_hp_upload_gedcom', array($this, 'handle_gedcom_upload'));
        add_action('wp_ajax_hp_process_gedcom', array($this, 'handle_gedcom_process'));
        add_action('wp_ajax_hp_export_gedcom', array($this, 'handle_gedcom_export'));
        add_action('wp_ajax_hp_import_progress', array($this, 'get_import_progress'));
        add_action('wp_ajax_hp_save_import_export_settings', array($this, 'save_import_export_settings'));
        add_action('wp_ajax_hp_search_people', array($this, 'search_people'));
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
        ob_start();        // Include header template with tabs
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
        $step = isset($_GET['step']) ? intval($_GET['step']) : 1;        // Use the import.php template which will include the correct step
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
    }    /**
         * Handle GEDCOM file upload via AJAX
         */
    public function handle_gedcom_upload()
    {
        // Check nonce
        check_ajax_referer('hp_gedcom_upload', 'nonce');

        // Check if file was uploaded
        if (!isset($_FILES['gedcom_file']) || empty($_FILES['gedcom_file']['tmp_name'])) {
            wp_send_json_error(array(
                'message' => __('No file was uploaded', 'heritagepress')
            ));
            return;
        }

        $file = $_FILES['gedcom_file'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_message = $this->get_upload_error_message($file['error']);
            wp_send_json_error(array(
                'message' => $error_message
            ));
            return;
        }

        // Check file type
        $file_info = pathinfo($file['name']);
        $extension = strtolower($file_info['extension']);

        if (!in_array($extension, array('ged', 'gedcom'))) {
            wp_send_json_error(array(
                'message' => __('Invalid file type. Only GEDCOM files (.ged, .gedcom) are allowed.', 'heritagepress')
            ));
            return;
        }

        // Get WordPress upload directory
        $upload_dir = wp_upload_dir();
        $gedcom_dir = $upload_dir['basedir'] . '/heritagepress/gedcom';

        // Create upload directory if it doesn't exist
        if (!file_exists($gedcom_dir)) {
            // Create directory recursively
            if (!mkdir($gedcom_dir, 0755, true)) {
                wp_send_json_error(array(
                    'message' => __('Failed to create upload directory. Please check server permissions.', 'heritagepress')
                ));
                return;
            }

            // Create an index.php file to prevent directory listing
            file_put_contents($gedcom_dir . '/index.php', '<?php // Silence is golden');
        }

        // Generate a unique file key
        $file_key = uniqid('hp_gedcom_', true);
        $target_file = $gedcom_dir . '/' . $file_key . '.ged';

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $target_file)) {
            wp_send_json_error(array(
                'message' => __('Failed to save the uploaded file. Please check server permissions.', 'heritagepress')
            ));
            return;
        }

        // Create a manifest file with original name and upload details
        $manifest = array(
            'original_name' => preg_replace('/[^a-zA-Z0-9_.-]/', '_', $file['name']), // Simple filename sanitization
            'file_size' => $file['size'],
            'file_key' => $file_key,
            'date_uploaded' => date('Y-m-d H:i:s'),
            'user_id' => get_current_user_id(),
        );

        file_put_contents($gedcom_dir . '/' . $file_key . '.json', json_encode($manifest));

        // Return success with file key
        wp_send_json_success(array(
            'message' => __('File uploaded successfully', 'heritagepress'),
            'file_key' => $file_key
        ));
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
     * Get error message for file upload errors
     *
     * @param int $error_code PHP upload error code
     * @return string Error message
     */
    private function get_upload_error_message($error_code)
    {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return __('The uploaded file exceeds the upload_max_filesize directive in php.ini', 'heritagepress');
            case UPLOAD_ERR_FORM_SIZE:
                return __('The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form', 'heritagepress');
            case UPLOAD_ERR_PARTIAL:
                return __('The uploaded file was only partially uploaded', 'heritagepress');
            case UPLOAD_ERR_NO_FILE:
                return __('No file was uploaded', 'heritagepress');
            case UPLOAD_ERR_NO_TMP_DIR:
                return __('Missing a temporary folder', 'heritagepress');
            case UPLOAD_ERR_CANT_WRITE:
                return __('Failed to write file to disk', 'heritagepress');
            case UPLOAD_ERR_EXTENSION:
                return __('A PHP extension stopped the file upload', 'heritagepress');
            default:
                return __('Unknown upload error', 'heritagepress');
        }
    }
    /**
     * Handle GEDCOM processing via AJAX
     */
    public function handle_gedcom_process()
    {
        // Check nonce
        check_ajax_referer('hp_gedcom_process', 'nonce');

        // Get required parameters
        $file_key = isset($_POST['file_key']) ? sanitize_text_field($_POST['file_key']) : '';
        $tree_id = isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : '';
        $new_tree_name = isset($_POST['new_tree_name']) ? sanitize_text_field($_POST['new_tree_name']) : '';
        $import_option = isset($_POST['import_option']) ? sanitize_text_field($_POST['import_option']) : 'replace';

        // Validate required parameters
        if (empty($file_key)) {
            wp_send_json_error(array(
                'message' => __('No GEDCOM file specified', 'heritagepress')
            ));
            return;
        }

        if ($tree_id === 'new' && empty($new_tree_name)) {
            wp_send_json_error(array(
                'message' => __('New tree name is required', 'heritagepress')
            ));
            return;
        }

        // Get the GEDCOM file path
        $upload_dir = wp_upload_dir();
        $gedcom_dir = $upload_dir['basedir'] . '/heritagepress/gedcom';
        $gedcom_file = $gedcom_dir . '/' . $file_key . '.ged';

        // Check if the file exists
        if (!file_exists($gedcom_file)) {
            wp_send_json_error(array(
                'message' => __('GEDCOM file not found', 'heritagepress')
            ));
            return;
        }

        try {
            // If creating a new tree, create it and get its ID
            if ($tree_id === 'new') {
                // Create a new tree in the database
                // This is a placeholder - actual implementation would create a tree record
                // $tree_manager = new \HeritagePress\Trees\TreeManager();
                // $tree_id = $tree_manager->create_tree($new_tree_name);
                $tree_id = time(); // Temporary placeholder

                // If tree creation failed
                if (empty($tree_id)) {
                    wp_send_json_error(array(
                        'message' => __('Failed to create new tree', 'heritagepress')
                    ));
                    return;
                }
            }

            // Create progress tracking file
            $progress_file = $gedcom_dir . '/' . $file_key . '_progress.json';
            $progress_data = array(
                'started' => true,
                'completed' => false,
                'percent' => 0,
                'operation' => __('Initializing import...', 'heritagepress'),
                'detail' => '',
                'stats' => array(
                    'processed' => 0,
                    'individuals' => 0,
                    'families' => 0,
                    'sources' => 0,
                    'media' => 0,
                    'notes' => 0,
                    'repositories' => 0
                ),
                'tree_id' => $tree_id,
                'error' => null
            );

            file_put_contents($progress_file, json_encode($progress_data));

            // In a real implementation, we would:
            // 1. Start a background process to handle the import
            // 2. Use GedcomService to parse and import the GEDCOM
            // 3. Update the progress file as we go

            // For this demonstration, we'll simulate starting the process successfully
            // The actual processing would happen in a background task

            wp_send_json_success(array(
                'message' => __('GEDCOM import started', 'heritagepress'),
                'tree_id' => $tree_id,
                'file_key' => $file_key
            ));

            // In a real implementation with background processing:
            // do_action('heritagepress_process_gedcom_async', $gedcom_file, $tree_id, $import_option);

        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    /**
     * Handle GEDCOM export via AJAX
     */
    public function handle_gedcom_export()
    {
        // Check nonce
        check_ajax_referer('hp_gedcom_export', 'nonce');// Get basic parameters
        $tree_id = isset($_POST['tree_id']) ? intval($_POST['tree_id']) : 0;
        $gedcom_version = isset($_POST['gedcom_version']) ? sanitize_text_field($_POST['gedcom_version']) : '5.5.1';
        $export_format = isset($_POST['export_format']) ? sanitize_text_field($_POST['export_format']) : 'gedcom';

        // Privacy options
        $privacy_living = isset($_POST['privacy_living']) ? (bool) $_POST['privacy_living'] : false;
        $privacy_notes = isset($_POST['privacy_notes']) ? (bool) $_POST['privacy_notes'] : false;
        $privacy_media = isset($_POST['privacy_media']) ? (bool) $_POST['privacy_media'] : false;

        // Branch selection options
        $branch_person_id = isset($_POST['branch_person_id']) ? sanitize_text_field($_POST['branch_person_id']) : '';
        $branch_generations = isset($_POST['branch_generations']) ? sanitize_text_field($_POST['branch_generations']) : 'all';
        $branch_direction = isset($_POST['branch_direction']) ? sanitize_text_field($_POST['branch_direction']) : 'both';
        $include_spouses = isset($_POST['include_spouses']) ? (bool) $_POST['include_spouses'] : true;

        // Date range filtering options
        $date_filter_type = isset($_POST['date_filter_type']) ? sanitize_text_field($_POST['date_filter_type']) : 'any';
        $date_range_start = isset($_POST['date_range_start']) ? sanitize_text_field($_POST['date_range_start']) : '';
        $date_range_end = isset($_POST['date_range_end']) ? sanitize_text_field($_POST['date_range_end']) : '';
        $include_estimated = isset($_POST['include_estimated']) ? (bool) $_POST['include_estimated'] : true;

        // Format-specific options
        $gedcom_line_breaks = isset($_POST['gedcom_line_breaks']) ? (bool) $_POST['gedcom_line_breaks'] : true;
        $media_quality = isset($_POST['media_quality']) ? sanitize_text_field($_POST['media_quality']) : 'high';
        $include_thumbnails = isset($_POST['include_thumbnails']) ? (bool) $_POST['include_thumbnails'] : false;
        $json_format = isset($_POST['json_format']) ? sanitize_text_field($_POST['json_format']) : 'standard';
        $json_pretty = isset($_POST['json_pretty']) ? (bool) $_POST['json_pretty'] : true;

        // Validate parameters
        if (empty($tree_id)) {
            wp_send_json_error(array(
                'message' => __('No tree selected', 'heritagepress')
            ));
            return;
        }

        // Prepare export directory
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/heritagepress/export';

        // Create directory if it doesn't exist
        if (!file_exists($export_dir)) {
            if (!mkdir($export_dir, 0755, true)) {
                wp_send_json_error(array(
                    'message' => __('Failed to create export directory', 'heritagepress')
                ));
                return;
            }

            // Add index.php to prevent directory listing
            file_put_contents($export_dir . '/index.php', '<?php // Silence is golden');
        }

        try {
            // Generate a unique file name
            $export_key = uniqid('hp_export_', true);
            $export_file = $export_dir . '/' . $export_key;

            // Determine file extension based on format
            $extension = '';
            switch ($export_format) {
                case 'gedzip':
                    $extension = '.gedzip';
                    break;
                case 'json':
                    $extension = '.json';
                    break;
                case 'gedcom':
                default:
                    $extension = '.ged';
                    break;
            }

            $export_file .= $extension;

            // In a real implementation, we would:
            // 1. Use GedcomService to generate the GEDCOM file
            // 2. Save it to the export directory
            // 3. Log the export

            // For this demonstration, we'll create a dummy file
            $dummy_content = "0 HEAD\n1 GEDC\n2 VERS {$gedcom_version}\n1 CHAR UTF-8\n0 @I1@ INDI\n1 NAME John /Doe/\n0 TRLR";
            file_put_contents($export_file, $dummy_content);            // Create export manifest
            $manifest = array(
                'tree_id' => $tree_id,
                'tree_name' => 'Sample Tree', // In a real implementation, get the actual tree name
                'gedcom_version' => $gedcom_version,
                'export_format' => $export_format,

                // Privacy settings
                'privacy_living' => $privacy_living,
                'privacy_notes' => $privacy_notes,
                'privacy_media' => $privacy_media,

                // Branch selection options
                'branch_filter' => !empty($branch_person_id),
                'branch_person_id' => $branch_person_id,
                'branch_generations' => $branch_generations,
                'branch_direction' => $branch_direction,
                'include_spouses' => $include_spouses,

                // Date range filtering
                'date_filter' => (!empty($date_range_start) || !empty($date_range_end)),
                'date_filter_type' => $date_filter_type,
                'date_range_start' => $date_range_start,
                'date_range_end' => $date_range_end,
                'include_estimated' => $include_estimated,

                // Format-specific options
                'gedcom_line_breaks' => $gedcom_line_breaks,
                'media_quality' => $media_quality,
                'include_thumbnails' => $include_thumbnails,
                'json_format' => $json_format,
                'json_pretty' => $json_pretty,

                // Export metadata
                'date_exported' => date('Y-m-d H:i:s'),
                'user_id' => get_current_user_id(),
                'file_size' => filesize($export_file),
                'export_key' => $export_key,
                'records' => array(
                    'individuals' => 1,  // Dummy data
                    'families' => 0,
                    'sources' => 0,
                    'media' => 0,
                    'notes' => 0,
                    'repositories' => 0
                )
            );

            file_put_contents($export_dir . '/' . $export_key . '.json', json_encode($manifest));

            // Return success with download URL
            $download_url = $upload_dir['baseurl'] . '/heritagepress/export/' . $export_key . $extension;

            wp_send_json_success(array(
                'message' => __('GEDCOM exported successfully', 'heritagepress'),
                'download_url' => $download_url,
                'export_key' => $export_key
            ));

        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    /**
     * Get import progress via AJAX
     */
    public function get_import_progress()
    {
        // Check nonce
        check_ajax_referer('hp_import_progress', 'nonce');

        // Get file key
        $file_key = isset($_POST['file_key']) ? sanitize_text_field($_POST['file_key']) : '';

        if (empty($file_key)) {
            wp_send_json_error(array(
                'message' => __('Invalid import file key', 'heritagepress')
            ));
            return;
        }

        // Get the progress file path
        $upload_dir = wp_upload_dir();
        $gedcom_dir = $upload_dir['basedir'] . '/heritagepress/gedcom';
        $progress_file = $gedcom_dir . '/' . $file_key . '_progress.json';

        // Check if the progress file exists
        if (!file_exists($progress_file)) {
            wp_send_json_error(array(
                'message' => __('Import progress not found', 'heritagepress')
            ));
            return;
        }

        // Read the progress data
        $progress_data = json_decode(file_get_contents($progress_file), true);

        if (!is_array($progress_data)) {
            wp_send_json_error(array(
                'message' => __('Invalid progress data', 'heritagepress')
            ));
            return;
        }

        // In a real implementation, this would read real progress from the file
        // For this demo, we'll simulate progress by incrementing values

        // Instead of real processing, we'll update the progress file for demo purposes
        // In a production environment, this would be handled by the background process
        if (!$progress_data['completed'] && $progress_data['started']) {
            $progress_data['percent'] += mt_rand(5, 15); // Randomly increment progress

            if ($progress_data['percent'] < 30) {
                $progress_data['operation'] = __('Processing individuals...', 'heritagepress');
                $progress_data['detail'] = __('Importing individual records', 'heritagepress');
                $progress_data['stats']['individuals'] += mt_rand(10, 20);
            } elseif ($progress_data['percent'] < 60) {
                $progress_data['operation'] = __('Processing families...', 'heritagepress');
                $progress_data['detail'] = __('Creating family relationships', 'heritagepress');
                $progress_data['stats']['families'] += mt_rand(5, 10);
            } elseif ($progress_data['percent'] < 85) {
                $progress_data['operation'] = __('Processing sources...', 'heritagepress');
                $progress_data['detail'] = __('Importing source records', 'heritagepress');
                $progress_data['stats']['sources'] += mt_rand(3, 8);
            } else {
                $progress_data['operation'] = __('Processing media...', 'heritagepress');
                $progress_data['detail'] = __('Importing media files', 'heritagepress');
                $progress_data['stats']['media'] += mt_rand(1, 5);
            }

            $progress_data['stats']['processed'] = $progress_data['stats']['individuals'] +
                $progress_data['stats']['families'] +
                $progress_data['stats']['sources'] +
                $progress_data['stats']['media'] +
                $progress_data['stats']['notes'] +
                $progress_data['stats']['repositories'];

            // Mark as completed if we reach 100%
            if ($progress_data['percent'] >= 100) {
                $progress_data['percent'] = 100;
                $progress_data['completed'] = true;
                $progress_data['operation'] = __('Import complete', 'heritagepress');
                $progress_data['detail'] = __('All records have been imported successfully', 'heritagepress');
            }

            // Save the updated progress
            file_put_contents($progress_file, json_encode($progress_data));
        }

        // Return the progress data
        wp_send_json_success(array(
            'percent' => $progress_data['percent'],
            'operation' => $progress_data['operation'],
            'detail' => $progress_data['detail'],
            'stats' => $progress_data['stats'],
            'completed' => $progress_data['completed'],
            'tree_id' => $progress_data['tree_id']
        ));
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

        // Save settings to database
        update_option('heritagepress_import_settings', $import_settings);
        update_option('heritagepress_export_settings', $export_settings);

        // Log settings update
        $this->log_action('settings_update', 'Import/Export settings updated by user ID ' . get_current_user_id());

        // Return success
        wp_send_json_success(array(
            'message' => __('Settings saved successfully', 'heritagepress')
        ));
    }

    /**
     * Sanitize settings array
     *
     * @param array $settings Array of settings to sanitize
     * @return array Sanitized settings
     */
    private function sanitize_settings($settings)
    {
        if (!is_array($settings)) {
            return array();
        }

        $sanitized = array();

        foreach ($settings as $key => $value) {
            // Sanitize based on setting type
            if (in_array($key, array('default_mode', 'default_version', 'default_format'))) {
                // For dropdown options, ensure they are valid values
                $sanitized[$key] = $this->sanitize_enum_setting($key, $value);
            } elseif ($key === 'batch_size') {
                // For numeric settings
                $sanitized[$key] = intval($value);
                // Enforce min/max values
                $sanitized[$key] = max(10, min(1000, $sanitized[$key]));
            } elseif ($key === 'temp_dir') {
                // For directory paths
                $sanitized[$key] = sanitize_text_field($value);
            } else {
                // For boolean settings (checkboxes)
                $sanitized[$key] = !empty($value) ? 1 : 0;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize settings that should match specific values
     *
     * @param string $setting Setting key
     * @param string $value Setting value
     * @return string Sanitized setting value
     */
    private function sanitize_enum_setting($setting, $value)
    {
        $allowed_values = array(
            'default_mode' => array('replace', 'add', 'merge'),
            'default_version' => array('7.0', '5.5.1'),
            'default_format' => array('gedcom', 'gedzip', 'json')
        );

        if (isset($allowed_values[$setting]) && !in_array($value, $allowed_values[$setting])) {
            // Return first allowed value as default
            return $allowed_values[$setting][0];
        }

        return $value;
    }    /**
         * Log an action to the import/export log
         *
         * @param string $action Action type
         * @param string $message Log message
         * @param array $details Optional additional details
         * @return boolean Whether logging was successful
         */
    private function log_action($action, $message, $details = array())
    {
        // Get existing logs
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
        return update_option('heritagepress_importexport_logs', $logs);
    }

    /**
     * AJAX handler for person search in the export branch filter
     * 
     * Searches for people in a specific tree based on a search query
     */
    public function search_people()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hp_gedcom_export')) {
            wp_send_json_error(array('message' => __('Security check failed', 'heritagepress')));
        }

        // Check required params
        if (!isset($_POST['query']) || !isset($_POST['tree_id'])) {
            wp_send_json_error(array('message' => __('Missing required parameters', 'heritagepress')));
        }        // Get and validate input
        $query = sanitize_text_field($_POST['query']);
        $tree_id = intval($_POST['tree_id']);

        if (empty($tree_id)) {
            wp_send_json_error(array('message' => __('Invalid tree ID', 'heritagepress')));
        }

        // Minimum search length
        if (strlen($query) < 2) {
            wp_send_json_error(array('message' => __('Search query too short', 'heritagepress')));
        }

        // Get people from database
        global $wpdb;

        // This is a placeholder - in a real implementation, 
        // this would search the actual database tables for people
        // based on the tree_id and query

        // Example query for demonstration:
        // $people = $wpdb->get_results($wpdb->prepare(
        //     "SELECT p.id, p.name, p.birth_year, p.death_year
        //     FROM {$wpdb->prefix}hp_people AS p
        //     WHERE p.tree_id = %d 
        //     AND (p.name LIKE %s OR p.alternatenames LIKE %s)
        //     ORDER BY p.name ASC
        //     LIMIT 10",
        //     $tree_id,
        //     '%' . $wpdb->esc_like($query) . '%',
        //     '%' . $wpdb->esc_like($query) . '%'
        // ));

        // For demonstration, we'll return mock data
        $people = array();

        // Generate some realistic names based on the query
        $surnames = array('Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Wilson');
        $firstNames = array('John', 'Mary', 'James', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Elizabeth');

        // If query looks like a last name
        $querySurname = ucfirst(strtolower($query));
        if (strlen($query) > 3) {
            foreach ($firstNames as $firstName) {
                // Add matching people
                if (strpos($querySurname, substr($firstName, 0, 2)) === 0) {
                    // First name match
                    $birthYear = rand(1800, 1950);
                    $deathYear = (rand(0, 10) > 8) ? '' : $birthYear + rand(50, 90); // Some still living

                    $people[] = array(
                        'id' => 'P' . rand(100, 999),
                        'name' => $firstName . ' ' . $surnames[array_rand($surnames)],
                        'birth_year' => $birthYear,
                        'death_year' => $deathYear
                    );
                }
            }

            // Add surname matches
            foreach ($surnames as $surname) {
                if (strpos($surname, $querySurname) === 0) {
                    $birthYear = rand(1800, 1950);
                    $deathYear = (rand(0, 10) > 8) ? '' : $birthYear + rand(50, 90);

                    $people[] = array(
                        'id' => 'P' . rand(100, 999),
                        'name' => $firstNames[array_rand($firstNames)] . ' ' . $surname,
                        'birth_year' => $birthYear,
                        'death_year' => $deathYear
                    );
                }
            }
        }

        // Ensure we have at least some results
        if (empty($people)) {
            // Add random matches as fallback
            for ($i = 0; $i < 3; $i++) {
                $birthYear = rand(1800, 1950);
                $deathYear = (rand(0, 10) > 8) ? '' : $birthYear + rand(50, 90);

                $people[] = array(
                    'id' => 'P' . rand(100, 999),
                    'name' => $firstNames[array_rand($firstNames)] . ' ' . $querySurname,
                    'birth_year' => $birthYear,
                    'death_year' => $deathYear
                );
            }
        }

        // Limit results
        $people = array_slice($people, 0, 5);

        // Return search results
        wp_send_json_success(array(
            'people' => $people,
            'tree_id' => $tree_id,
            'query' => $query
        ));
    }
}
