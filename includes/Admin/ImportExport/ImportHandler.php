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
    }    /**
         * Handle GEDCOM file upload
         */
    public function handle_gedcom_upload()
    {
        // Verify nonce - match the form nonce field name and action
        if (!wp_verify_nonce($_POST['hp_gedcom_nonce'], 'hp_gedcom_upload')) {
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
            }            // Validate GEDCOM format
            $validation_result = $this->validate_gedcom_file($filepath);

            // Debug logging
            error_log('GEDCOM validation for file: ' . $filepath);
            error_log('Validation result: ' . print_r($validation_result, true));

            if (!$validation_result['valid']) {
                error_log('GEDCOM validation failed, removing file: ' . $filepath);
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
        // Verify nonce - use the same nonce as upload for consistency
        if (!isset($_POST['hp_gedcom_nonce']) || !wp_verify_nonce($_POST['hp_gedcom_nonce'], 'hp_gedcom_upload')) {
            wp_send_json_error(array('message' => __('Security check failed', 'heritagepress')));
            return;
        }
        $file_key = sanitize_text_field($_POST['file_key'] ?? '');
        $tree_id = intval($_POST['tree_id'] ?? 0);
        $import_option = sanitize_text_field($_POST['import_option'] ?? 'new');

        // File key is now optional - proceed with import process regardless
        error_log('GEDCOM process handler: file_key=' . $file_key . ', tree_id=' . $tree_id . ', import_option=' . $import_option);

        try {
            $upload_info = $this->get_upload_dir();
            $gedcom_dir = $upload_info['path'];

            // If we have a file key, try to find the specific file
            $gedcom_file = null;
            if (!empty($file_key)) {
                $gedcom_file = $gedcom_dir . '/' . $file_key . '.ged';
                if (!file_exists($gedcom_file)) {
                    error_log('GEDCOM file not found at: ' . $gedcom_file);
                    // Don't fail here, continue with general import process
                    $gedcom_file = null;
                }
            }

            // If no specific file, try to find any GEDCOM file in upload directory
            if ($gedcom_file === null) {
                $gedcom_files = glob($gedcom_dir . '/*.ged');
                if (!empty($gedcom_files)) {
                    $gedcom_file = $gedcom_files[0]; // Use first found GEDCOM file
                    error_log('Using first available GEDCOM file: ' . $gedcom_file);
                } else {
                    wp_send_json_error(array('message' => __('No GEDCOM file found for import', 'heritagepress')));
                    return;
                }
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
            }            // Create progress tracking
            $progress_data = $this->initialize_import_progress($file_key, $tree_id);            // Process GEDCOM import using GedcomService
            error_log('GEDCOM Import: Starting import process for file: ' . $gedcom_file);
            $import_result = $this->get_gedcom_service()->import($gedcom_file, $tree_id);
            error_log('GEDCOM Import: Received result: ' . json_encode($import_result));

            if ($import_result && $import_result['success']) {
                error_log('GEDCOM Import: Success condition met - setting completion status');
                // Update progress to completion
                $progress_data['completed'] = true;
                $progress_data['percent'] = 100;
                $progress_data['operation'] = __('Import completed successfully', 'heritagepress');
                $this->update_import_progress($file_key, $progress_data);
                error_log('GEDCOM Import: Progress updated to completed, sending success response');
                wp_send_json_success(array(
                    'message' => __('GEDCOM import completed successfully', 'heritagepress'),
                    'tree_id' => $tree_id,
                    'file_key' => $file_key,
                    'stats' => $import_result['stats'],
                    'completed' => true,
                    'percent' => 100
                ));
            } else {
                error_log('GEDCOM Import: Failure condition - import_result success is false or missing');
                $error_message = $import_result['message'] ?? __('GEDCOM import failed', 'heritagepress');
                throw new \Exception($error_message);
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

        // If no file key provided, try to find any progress file
        if (empty($file_key)) {
            $upload_info = $this->get_upload_dir();
            $progress_files = glob($upload_info['path'] . '/*_progress.json');
            if (!empty($progress_files)) {
                // Use the most recent progress file
                $progress_file = $progress_files[0];
                foreach ($progress_files as $file) {
                    if (filemtime($file) > filemtime($progress_file)) {
                        $progress_file = $file;
                    }
                }
            } else {
                wp_send_json_error(array('message' => __('No import progress found', 'heritagepress')));
                return;
            }
        } else {
            $upload_info = $this->get_upload_dir();
            $progress_file = $upload_info['path'] . '/' . $file_key . '_progress.json';
            if (!file_exists($progress_file)) {
                wp_send_json_error(array('message' => __('Progress file not found', 'heritagepress')));
                return;
            }
        }

        $progress_data = json_decode(file_get_contents($progress_file), true);
        wp_send_json_success($progress_data);
    }    /**
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
            error_log('GEDCOM validation: Cannot open file ' . $filepath);
            return array('valid' => false, 'message' => __('Cannot read GEDCOM file', 'heritagepress'));
        }

        // Read first few lines to find HEAD record
        $found_header = false;
        $line_count = 0;
        $debug_lines = array();
        while (($line = fgets($file_handle)) !== false && $line_count < 10) {
            $original_line = $line;
            $line = trim($line);
            $line_count++;

            // Remove BOM characters if present (UTF-8, UTF-16, UTF-32)
            if ($line_count === 1) {
                // UTF-8 BOM (EF BB BF)
                $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
                // UTF-16 BE BOM (FE FF)
                $line = preg_replace('/^\xFE\xFF/', '', $line);
                // UTF-16 LE BOM (FF FE)
                $line = preg_replace('/^\xFF\xFE/', '', $line);
                // UTF-32 BE BOM (00 00 FE FF)
                $line = preg_replace('/^\x00\x00\xFE\xFF/', '', $line);
                // UTF-32 LE BOM (FF FE 00 00)
                $line = preg_replace('/^\xFF\xFE\x00\x00/', '', $line);
                // Also remove the common UTF-8 BOM character that might appear as a visible character
                $line = ltrim($line, "\xEF\xBB\xBF\xFE\xFF");
            }

            // Debug: collect first few lines
            if ($line_count <= 3) {
                $debug_lines[] = 'Line ' . $line_count . ': "' . $original_line . '" (after BOM removal: "' . $line . '")';
            }

            // Skip empty lines and look for GEDCOM header
            if (empty($line)) {
                continue;
            }

            // Check for GEDCOM header (various valid formats)
            if (preg_match('/^0\s*HEAD\s*$/i', $line)) {
                $found_header = true;
                error_log('GEDCOM validation: Found header on line ' . $line_count . ': ' . $line);
                break;
            }
        }

        fclose($file_handle);

        // Debug logging
        error_log('GEDCOM validation debug for ' . $filepath . ':');
        error_log('Lines checked: ' . implode(' | ', $debug_lines));
        error_log('Header found: ' . ($found_header ? 'YES' : 'NO'));

        if (!$found_header) {
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

    /**
     * Analyze GEDCOM file and return statistics
     * 
     * @param string $filepath Path to GEDCOM file
     * @return array Analysis results
     */
    public function analyze_gedcom_file($filepath)
    {
        $analysis = array(
            'gedcom_version' => '5.5.1',
            'encoding' => 'UTF-8',
            'source_system' => 'Unknown',
            'individuals' => 0,
            'families' => 0,
            'sources' => 0,
            'media' => 0,
            'notes' => 0,
            'repositories' => 0,
            'events' => 0,
            'file_size' => filesize($filepath),
            'total_lines' => 0,
            'total_records' => 0,
            'date_range' => array('earliest' => null, 'latest' => null),
            'places' => array(),
            'surnames' => array(),
            'warnings' => array(),
            'errors' => array(),
            'has_errors' => false,
            'has_warnings' => false,
            'error_count' => 0,
            'warning_count' => 0
        );

        $file_handle = fopen($filepath, 'rb'); // Open in binary mode to handle BOM
        if (!$file_handle) {
            $analysis['errors'][] = array(
                'type' => 'error',
                'message' => 'Cannot open GEDCOM file',
                'record_id' => '',
                'record_name' => '',
                'line' => 0
            );
            $analysis['has_errors'] = true;
            $analysis['error_count'] = 1;
            return $analysis;
        }

        $in_header = false;
        $in_source_section = false;
        $source_code = '';
        $source_name = '';
        $source_version = '';
        $first_line = true; // Track first line for BOM removal

        while (($line = fgets($file_handle)) !== false) {
            $analysis['total_lines']++;

            // Remove BOM from first line if present
            if ($first_line) {
                $line = $this->remove_bom($line);
                $first_line = false;
            }

            $line = trim($line);

            if (empty($line))
                continue;

            // Parse GEDCOM line
            if (preg_match('/^(\d+)\s+(.+)$/', $line, $matches)) {
                $level = intval($matches[1]);
                $content = $matches[2];

                // Level 0 records
                if ($level === 0) {
                    if ($content === 'HEAD') {
                        $in_header = true;
                        continue;
                    } elseif ($content === 'TRLR') {
                        break;
                    }

                    $in_header = false;

                    // Count record types
                    if (preg_match('/^@\w+@\s+(\w+)/', $content, $record_matches)) {
                        $record_type = $record_matches[1];
                        switch ($record_type) {
                            case 'INDI':
                                $analysis['individuals']++;
                                break;
                            case 'FAM':
                                $analysis['families']++;
                                break;
                            case 'SOUR':
                                $analysis['sources']++;
                                break;
                            case 'OBJE':
                                $analysis['media']++;
                                break;
                            case 'NOTE':
                                $analysis['notes']++;
                                break;
                            case 'REPO':
                                $analysis['repositories']++;
                                break;
                        }
                    }
                } else {
                    // Header information
                    if ($in_header) {
                        if ($level === 1) {
                            // Reset source section tracking
                            $in_source_section = false;

                            if (preg_match('/^SOUR\s*(.*)$/', $content, $sour_matches)) {
                                $in_source_section = true;
                                $source_code = trim($sour_matches[1]);
                            } elseif (preg_match('/^CHAR\s+(.+)$/', $content, $char_matches)) {
                                $analysis['encoding'] = trim($char_matches[1]);
                            }
                        } elseif ($level === 2) {
                            if ($in_source_section) {
                                if (preg_match('/^NAME\s+(.+)$/', $content, $name_matches)) {
                                    $source_name = trim($name_matches[1]);
                                } elseif (preg_match('/^VERS\s+(.+)$/', $content, $version_matches)) {
                                    $source_version = trim($version_matches[1]);
                                }
                            } elseif (preg_match('/^VERS\s+(.+)$/', $content, $version_matches)) {
                                // This might be GEDCOM version under GEDC
                                $analysis['gedcom_version'] = trim($version_matches[1]);
                            }
                        }
                    }

                    // Count events
                    if (in_array($content, array('BIRT', 'DEAT', 'MARR', 'DIV', 'CHR', 'BURI'))) {
                        $analysis['events']++;
                    }
                }
            }
        }
        fclose($file_handle);

        // Build the source system string from parsed components
        if (!empty($source_name) && !empty($source_version)) {
            $analysis['source_system'] = $source_name . ' (Version: ' . $source_version . ')';
        } elseif (!empty($source_name)) {
            $analysis['source_system'] = $source_name;
        } elseif (!empty($source_code)) {
            $analysis['source_system'] = $source_code;
        }

        $analysis['total_records'] = $analysis['individuals'] + $analysis['families'] + $analysis['sources'] + $analysis['media'] +
            $analysis['notes'] + $analysis['repositories'];

        return $analysis;
    }

    /**
     * Remove BOM from content
     * 
     * @param string $content Content to remove BOM from
     * @return string Content without BOM
     */
    private function remove_bom($content)
    {
        // UTF-8 BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            return substr($content, 3);
        }

        // UTF-16 BE BOM
        if (substr($content, 0, 2) === "\xFE\xFF") {
            return substr($content, 2);
        }

        // UTF-16 LE BOM
        if (substr($content, 0, 2) === "\xFF\xFE") {
            return substr($content, 2);
        }

        // UTF-32 BE BOM
        if (substr($content, 0, 4) === "\x00\x00\xFE\xFF") {
            return substr($content, 4);
        }

        // UTF-32 LE BOM
        if (substr($content, 0, 4) === "\xFF\xFE\x00\x00") {
            return substr($content, 4);
        }
        return $content;
    }
}
