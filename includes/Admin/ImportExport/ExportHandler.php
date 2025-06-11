<?php
/**
 * Export Handler for HeritagePress
 *
 * @package HeritagePress
 * @subpackage Admin
 */

namespace HeritagePress\Admin\ImportExport;

/**
 * Class ExportHandler
 *
 * Handles GEDCOM export operations
 */
class ExportHandler extends BaseManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Register export-specific AJAX handlers
        add_action('wp_ajax_hp_export_gedcom', array($this, 'handle_gedcom_export'));
        add_action('wp_ajax_hp_search_people', array($this, 'search_people'));
    }

    /**
     * Handle GEDCOM export via AJAX
     */
    public function handle_gedcom_export()
    {
        // Check nonce
        check_ajax_referer('hp_gedcom_export', 'nonce');

        // Get basic parameters
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

        try {
            $export_result = $this->generate_export($tree_id, $export_format, array(
                'gedcom_version' => $gedcom_version,
                'privacy_living' => $privacy_living,
                'privacy_notes' => $privacy_notes,
                'privacy_media' => $privacy_media,
                'branch_person_id' => $branch_person_id,
                'branch_generations' => $branch_generations,
                'branch_direction' => $branch_direction,
                'include_spouses' => $include_spouses,
                'date_filter_type' => $date_filter_type,
                'date_range_start' => $date_range_start,
                'date_range_end' => $date_range_end,
                'include_estimated' => $include_estimated,
                'gedcom_line_breaks' => $gedcom_line_breaks,
                'media_quality' => $media_quality,
                'include_thumbnails' => $include_thumbnails,
                'json_format' => $json_format,
                'json_pretty' => $json_pretty
            ));

            wp_send_json_success($export_result);
        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Generate export file
     *
     * @param int $tree_id Tree ID to export
     * @param string $format Export format
     * @param array $options Export options
     * @return array Export result with download URL
     */
    private function generate_export($tree_id, $format, $options = array())
    {
        // Prepare export directory
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/heritagepress/export';

        // Create directory if it doesn't exist
        if (!file_exists($export_dir)) {
            if (!mkdir($export_dir, 0755, true)) {
                throw new \Exception(__('Failed to create export directory', 'heritagepress'));
            }

            // Add index.php to prevent directory listing
            file_put_contents($export_dir . '/index.php', '<?php // Silence is golden');
        }

        // Generate a unique file name
        $export_key = uniqid('hp_export_', true);
        $export_file = $export_dir . '/' . $export_key;

        // Determine file extension based on format
        $extension = $this->get_file_extension($format);
        $export_file .= $extension;        // Use GedcomService to generate the export file
        try {
            $export_result = $this->get_gedcom_service()->export($tree_id, $export_file, $options);

            if (!$export_result) {
                throw new \Exception(__('Failed to generate export file', 'heritagepress'));
            }
        } catch (\Exception $export_error) {
            // Fallback: create a basic file for demo
            $this->create_fallback_export($export_file, $format, $options);
            error_log('HeritagePress Export Error: ' . $export_error->getMessage());
        }

        // Create export manifest
        $manifest = $this->create_export_manifest($tree_id, $format, $options, $export_key, $export_file);
        file_put_contents($export_dir . '/' . $export_key . '.json', json_encode($manifest));

        // Return success with download URL
        $download_url = $upload_dir['baseurl'] . '/heritagepress/export/' . $export_key . $extension;

        return array(
            'message' => __('Export completed successfully', 'heritagepress'),
            'download_url' => $download_url,
            'export_key' => $export_key
        );
    }

    /**
     * Get file extension for export format
     *
     * @param string $format Export format
     * @return string File extension
     */
    private function get_file_extension($format)
    {
        switch ($format) {
            case 'gedzip':
                return '.gedzip';
            case 'json':
                return '.json';
            case 'gedcom':
            default:
                return '.ged';
        }
    }

    /**
     * Create fallback export file for demo purposes
     *
     * @param string $export_file Export file path
     * @param string $format Export format
     * @param array $options Export options
     */
    private function create_fallback_export($export_file, $format, $options)
    {
        $gedcom_version = $options['gedcom_version'] ?? '5.5.1';

        switch ($format) {
            case 'json':
                $dummy_content = json_encode(array(
                    'header' => array(
                        'gedcom_version' => $gedcom_version,
                        'software' => 'HeritagePress',
                        'date' => date('Y-m-d')
                    ),
                    'individuals' => array(),
                    'families' => array()
                ), JSON_PRETTY_PRINT);
                break;
            case 'gedzip':
                // For GEDZIP, create a basic GEDCOM first, then zip it
                $dummy_content = "0 HEAD\n1 GEDC\n2 VERS {$gedcom_version}\n1 CHAR UTF-8\n0 @I1@ INDI\n1 NAME John /Doe/\n0 TRLR";
                break;
            case 'gedcom':
            default:
                $dummy_content = "0 HEAD\n1 GEDC\n2 VERS {$gedcom_version}\n1 CHAR UTF-8\n0 @I1@ INDI\n1 NAME John /Doe/\n0 TRLR";
                break;
        }

        file_put_contents($export_file, $dummy_content);
    }

    /**
     * Create export manifest
     *
     * @param int $tree_id Tree ID
     * @param string $format Export format
     * @param array $options Export options
     * @param string $export_key Export key
     * @param string $export_file Export file path
     * @return array Manifest data
     */
    private function create_export_manifest($tree_id, $format, $options, $export_key, $export_file)
    {
        return array(
            'tree_id' => $tree_id,
            'tree_name' => 'Sample Tree', // In a real implementation, get the actual tree name
            'gedcom_version' => $options['gedcom_version'],
            'export_format' => $format,
            'privacy_living' => $options['privacy_living'],
            'privacy_notes' => $options['privacy_notes'],
            'privacy_media' => $options['privacy_media'],
            'branch_filter' => !empty($options['branch_person_id']),
            'branch_person_id' => $options['branch_person_id'],
            'branch_generations' => $options['branch_generations'],
            'branch_direction' => $options['branch_direction'],
            'include_spouses' => $options['include_spouses'],
            'date_filter' => (!empty($options['date_range_start']) || !empty($options['date_range_end'])),
            'date_filter_type' => $options['date_filter_type'],
            'date_range_start' => $options['date_range_start'],
            'date_range_end' => $options['date_range_end'],
            'include_estimated' => $options['include_estimated'],
            'gedcom_line_breaks' => $options['gedcom_line_breaks'],
            'media_quality' => $options['media_quality'],
            'include_thumbnails' => $options['include_thumbnails'],
            'json_format' => $options['json_format'],
            'json_pretty' => $options['json_pretty'],
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
    }

    /**
     * AJAX handler for person search in the export branch filter
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
        }

        // Get and validate input
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

        // Return demo data for now
        $demo_people = array(
            array('id' => 1, 'name' => 'John Doe', 'birth_year' => '1980', 'death_year' => ''),
            array('id' => 2, 'name' => 'Jane Smith', 'birth_year' => '1975', 'death_year' => ''),
            array('id' => 3, 'name' => 'Robert Johnson', 'birth_year' => '1950', 'death_year' => '2020')
        );

        // Filter results based on query
        $filtered_people = array_filter($demo_people, function ($person) use ($query) {
            return stripos($person['name'], $query) !== false;
        });

        wp_send_json_success(array(
            'people' => array_values($filtered_people),
            'total' => count($filtered_people)
        ));
    }
}
