<?php
/**
 * Media Import Progress Handler
 *
 * Handles progress tracking for media imports from genealogy software.
 * Supports both Ajax updates and WP Background Processing.
 *
 * @package HeritagePress
 * @subpackage Importers
 */

namespace HeritagePress\Importers;

class Media_Import_Progress {    const OPTION_KEY = 'heritage_press_media_import_progress';
    const BATCH_SIZE = 10; // Number of media files to process per batch
    const RECOVERY_TIMEOUT = 3600; // 1 hour timeout for recovery

    private $total_files = 0;
    private $processed_files = 0;
    private $failed_files = [];
    private $current_batch = [];
    private $import_id;
    private $settings;

    public function __construct($import_id = null) {
        $this->import_id = $import_id ?? uniqid('media_import_');        $this->settings = [
            'batch_size' => get_option('heritage_press_import_batch_size', self::BATCH_SIZE),
            'save_interval' => get_option('heritage_press_import_save_interval', 30), // seconds
            'detailed_errors' => get_option('heritage_press_detailed_error_logging', true)
        ];
        $this->load_progress();
        $this->maybe_recover_import();
    }

    /**
     * Start a new import process
     */
    public function start_import($total_files) {
        $this->total_files = $total_files;
        $this->processed_files = 0;
        $this->failed_files = [];
        $this->current_batch = [];
        $this->save_progress();

        return $this->import_id;
    }

    /**
     * Add a file to the current batch
     */
    public function add_to_batch($file_info) {
        $this->current_batch[] = $file_info;
        
        if (count($this->current_batch) >= self::BATCH_SIZE) {
            $this->process_batch();
        }
    }

    /**
     * Process the current batch of files
     */
    public function process_batch() {
        if (empty($this->current_batch)) {
            return;
        }

        foreach ($this->current_batch as $file_info) {
            try {
                $this->import_file($file_info);
                $this->processed_files++;
            } catch (\Exception $e) {
                $this->failed_files[] = [
                    'file' => $file_info['path'],
                    'error' => $e->getMessage()
                ];
                $this->log_error($file_info, $e->getMessage());
            }
        }

        $this->current_batch = [];
        $this->save_progress();
        $this->update_progress_ui();
    }

    /**
     * Import a single file
     */
    private function import_file($file_info) {
        $media_handler = new \HeritagePress\GEDCOM\GedcomMediaHandler($file_info['base_path']);
        $attachment_id = $media_handler->importToWordPress(
            $file_info['path'],
            $file_info['title'] ?? '',
            $file_info['description'] ?? ''
        );

        if (!$attachment_id) {
            throw new \Exception('Failed to import file');
        }

        // Store FTM-specific metadata
        if (!empty($file_info['metadata'])) {
            update_post_meta($attachment_id, '_heritage_press_ftm_metadata', $file_info['metadata']);
        }

        // Store geocoding data if available
        if (!empty($file_info['geocoding'])) {
            update_post_meta($attachment_id, '_heritage_press_geocoding', $file_info['geocoding']);
        }

        return $attachment_id;
    }

    /**
     * Get current progress
     */
    public function get_progress() {
        return [
            'import_id' => $this->import_id,
            'total_files' => $this->total_files,
            'processed_files' => $this->processed_files,
            'failed_files' => $this->failed_files,
            'percent_complete' => $this->total_files > 0 
                ? round(($this->processed_files / $this->total_files) * 100, 2)
                : 0,
            'status' => $this->get_status()
        ];
    }

    /**
     * Get import status
     */
    private function get_status() {
        if ($this->total_files === 0) {
            return 'not_started';
        }
        if ($this->processed_files >= $this->total_files) {
            return 'completed';
        }
        if (!empty($this->failed_files)) {
            return 'has_errors';
        }
        return 'in_progress';
    }

    /**
     * Load progress from database
     */
    private function load_progress() {
        $progress = get_option(self::OPTION_KEY . '_' . $this->import_id);
        if ($progress) {
            $this->total_files = $progress['total_files'];
            $this->processed_files = $progress['processed_files'];
            $this->failed_files = $progress['failed_files'];
        }
    }

    /**
     * Save progress to database
     */
    private function save_progress() {
        update_option(self::OPTION_KEY . '_' . $this->import_id, [
            'total_files' => $this->total_files,
            'processed_files' => $this->processed_files,
            'failed_files' => $this->failed_files,
            'last_update' => current_time('mysql')
        ]);
    }

    /**
     * Update progress UI via Ajax
     */
    private function update_progress_ui() {
        wp_send_json($this->get_progress());
    }

    /**
     * Clean up completed import
     */
    public function cleanup() {
        delete_option(self::OPTION_KEY . '_' . $this->import_id);
    }

    /**
     * Attempt to recover a failed or interrupted import
     */
    private function maybe_recover_import() {
        if (!$this->import_id) {
            return;
        }

        $progress = get_option(self::OPTION_KEY . '_' . $this->import_id);
        if (!$progress) {
            return;
        }

        // Check if import is stale
        $last_update = strtotime($progress['last_update']);
        if (time() - $last_update > self::RECOVERY_TIMEOUT) {
            $this->cleanup();
            return;
        }

        // Recover progress
        $this->total_files = $progress['total_files'];
        $this->processed_files = $progress['processed_files'];
        $this->failed_files = $progress['failed_files'];
        
        // Log recovery
        error_log(sprintf(
            'Recovered media import %s: %d/%d files processed',
            $this->import_id,
            $this->processed_files,
            $this->total_files
        ));
    }

    /**
     * Log detailed error information
     */
    private function log_error($file_info, $error) {
        if (!$this->settings['detailed_errors']) {
            return;
        }        $error_log = get_option('heritage_press_import_error_log', []);
        $error_log[$this->import_id][] = [
            'timestamp' => current_time('mysql'),
            'file' => $file_info['path'],
            'error' => $error,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];

        update_option('heritage_press_import_error_log', $error_log);
    }

    /**
     * Get detailed error log
     */
    public function get_error_log() {
        return get_option('heritage_press_import_error_log', [])[$this->import_id] ?? [];
    }
}
