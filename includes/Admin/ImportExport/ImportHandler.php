<?php
/**
 * Import Handler for HeritagePress
 *
 * @package HeritagePress
 * @subpackage Admin
 */

namespace HeritagePress\Admin\ImportExport;

/**
 * Class ImportHandler
 *
 * Handles GEDCOM import operations
 */
class ImportHandler extends BaseManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Register import-specific AJAX handlers
        add_action('wp_ajax_hp_upload_gedcom', array($this, 'handle_gedcom_upload'));
        add_action('wp_ajax_hp_process_gedcom', array($this, 'handle_gedcom_process'));
        add_action('wp_ajax_hp_import_progress', array($this, 'get_import_progress'));
    }

    /**
     * Handle GEDCOM file upload
     */
    public function handle_gedcom_upload()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['hp_gedcom_import_nonce'], 'hp_gedcom_import')) {
            wp_send_json_error(array('message' => __('Security check failed', 'heritagepress')));
            return;
        }

        // Check if file was uploaded
        if (!isset($_FILES['gedcom_file']) || $_FILES['gedcom_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('No file uploaded or upload error', 'heritagepress')));
            return;
        }

        $file = $_FILES['gedcom_file'];

        // Validate file type
        $allowed_extensions = array('ged', 'gedcom');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_extensions)) {
            wp_send_json_error(array('message' => __('Invalid file type. Only .ged and .gedcom files are allowed.', 'heritagepress')));
            return;
        }

        // Validate file size (max 50MB)
        $max_size = 50 * 1024 * 1024; // 50MB in bytes
        if ($file['size'] > $max_size) {
            wp_send_json_error(array('message' => __('File too large. Maximum size is 50MB.', 'heritagepress')));
            return;
        }

        try {
            // Get upload directory
            $upload_info = $this->get_upload_dir();
            $gedcom_dir = $upload_info['path'];

            // Ensure directory exists
            if (!$this->ensure_upload_dir($gedcom_dir)) {
                wp_send_json_error(array('message' => __('Failed to create upload directory', 'heritagepress')));
                return;
            }

            // Generate unique filename
            $file_key = uniqid('gedcom_', true);
            $filename = $file_key . '.ged';
            $filepath = $gedcom_dir . '/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                wp_send_json_error(array('message' => __('Failed to save uploaded file', 'heritagepress')));
                return;
            }

            // Validate GEDCOM format
            $validation_result = $this->validate_gedcom_file($filepath);
            if (!$validation_result['valid']) {
                unlink($filepath); // Remove invalid file
                wp_send_json_error(array('message' => $validation_result['message']));
                return;
            }

            wp_send_json_success(array(
                'message' => __('File uploaded successfully', 'heritagepress'),
                'file_key' => $file_key,
                'filename' => $file['name'],
                'size' => $file['size'],
                'gedcom_info' => $validation_result['info']
            ));

        } catch (\Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Handle GEDCOM processing
     */
    public function handle_gedcom_process()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['hp_gedcom_import_nonce'], 'hp_gedcom_import')) {
            wp_send_json_error(array('message' => __('Security check failed', 'heritagepress')));
            return;
        }

        $file_key = sanitize_text_field($_POST['file_key'] ?? '');
        $tree_id = intval($_POST['tree_id'] ?? 0);
        $import_option = sanitize_text_field($_POST['import_option'] ?? 'new');

        if (empty($file_key)) {
            wp_send_json_error(array('message' => __('File key is required', 'heritagepress')));
            return;
        }

        try {
            $upload_info = $this->get_upload_dir();
            $gedcom_dir = $upload_info['path'];
            $gedcom_file = $gedcom_dir . '/' . $file_key . '.ged';

            if (!file_exists($gedcom_file)) {
                wp_send_json_error(array('message' => __('GEDCOM file not found', 'heritagepress')));
                return;
            }

            // Handle tree creation/selection
            if ($import_option === 'new') {
                $tree_name = sanitize_text_field($_POST['tree_name'] ?? '');
                if (empty($tree_name)) {
                    wp_send_json_error(array('message' => __('Tree name is required for new tree', 'heritagepress')));
                    return;
                }

                $tree_id = $this->create_new_tree($tree_name);
                if (!$tree_id) {
                    wp_send_json_error(array('message' => __('Failed to create new tree', 'heritagepress')));
                    return;
                }
            }

            // Create progress tracking
            $progress_data = $this->initialize_import_progress($file_key, $tree_id);

            // Process GEDCOM import using GedcomService
            $import_result = $this->gedcom_service->import($gedcom_file, $tree_id);

            if ($import_result) {
                // Update progress to completion
                $progress_data['completed'] = true;
                $progress_data['percent'] = 100;
                $progress_data['operation'] = __('Import completed successfully', 'heritagepress');
                $this->update_import_progress($file_key, $progress_data);

                wp_send_json_success(array(
                    'message' => __('GEDCOM import completed successfully', 'heritagepress'),
                    'tree_id' => $tree_id,
                    'file_key' => $file_key
                ));
            } else {
                throw new \Exception(__('GEDCOM import failed', 'heritagepress'));
            }

        } catch (\Exception $e) {
            // Update progress with error
            if (isset($progress_data)) {
                $progress_data['error'] = $e->getMessage();
                $progress_data['operation'] = __('Import failed', 'heritagepress');
                $this->update_import_progress($file_key, $progress_data);
            }

            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Get import progress
     */
    public function get_import_progress()
    {
        $file_key = sanitize_text_field($_GET['file_key'] ?? '');

        if (empty($file_key)) {
            wp_send_json_error(array('message' => __('File key is required', 'heritagepress')));
            return;
        }

        $upload_info = $this->get_upload_dir();
        $progress_file = $upload_info['path'] . '/' . $file_key . '_progress.json';

        if (!file_exists($progress_file)) {
            wp_send_json_error(array('message' => __('Progress file not found', 'heritagepress')));
            return;
        }

        $progress_data = json_decode(file_get_contents($progress_file), true);
        wp_send_json_success($progress_data);
    }

    /**
     * Validate GEDCOM file format
     * 
     * @param string $filepath Path to GEDCOM file
     * @return array Validation result
     */
    private function validate_gedcom_file($filepath)
    {
        // Basic GEDCOM validation
        $file_handle = fopen($filepath, 'r');
        if (!$file_handle) {
            return array('valid' => false, 'message' => __('Cannot read GEDCOM file', 'heritagepress'));
        }

        $first_line = fgets($file_handle);
        fclose($file_handle);

        if (strpos($first_line, '0 HEAD') !== 0) {
            return array('valid' => false, 'message' => __('Invalid GEDCOM format - missing header', 'heritagepress'));
        }

        return array(
            'valid' => true,
            'info' => array(
                'version' => '5.5.1', // TODO: Parse actual version
                'encoding' => 'UTF-8'  // TODO: Parse actual encoding
            )
        );
    }

    /**
     * Create a new tree
     * 
     * @param string $tree_name Tree name
     * @return int|false Tree ID or false on failure
     */
    private function create_new_tree($tree_name)
    {
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'hp_trees',
            array(
                'title' => $tree_name,
                'description' => __('Imported from GEDCOM', 'heritagepress'),
                'privacy_level' => 0,
                'owner_user_id' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%d', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Initialize import progress tracking
     * 
     * @param string $file_key File key
     * @param int $tree_id Tree ID
     * @return array Progress data
     */
    private function initialize_import_progress($file_key, $tree_id)
    {
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

        $this->update_import_progress($file_key, $progress_data);
        return $progress_data;
    }

    /**
     * Update import progress
     * 
     * @param string $file_key File key
     * @param array $progress_data Progress data
     */
    private function update_import_progress($file_key, $progress_data)
    {
        $upload_info = $this->get_upload_dir();
        $progress_file = $upload_info['path'] . '/' . $file_key . '_progress.json';
        file_put_contents($progress_file, json_encode($progress_data));
    }
}
