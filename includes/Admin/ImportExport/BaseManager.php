<?php
/**
 * Base Import/Export Manager
 *
 * @package HeritagePress
 * @subpackage Admin
 */

namespace HeritagePress\Admin\ImportExport;

use HeritagePress\Services\GedcomService;
use HeritagePress\Models\DateConverter;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class BaseManager
 *
 * Core functionality for import/export operations
 */
class BaseManager
{
    /**
     * @var GedcomService
     */
    protected $gedcom_service;

    /**
     * @var DateConverter
     */
    protected $date_converter;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize services
        $this->gedcom_service = new GedcomService();
        $this->date_converter = new DateConverter();
    }

    /**
     * Get DateConverter instance
     * 
     * @return DateConverter
     */
    public function get_date_converter()
    {
        return $this->date_converter;
    }

    /**
     * Get GedcomService instance
     * 
     * @return object GedcomService instance
     */
    public function get_gedcom_service()
    {
        return $this->gedcom_service;
    }

    /**
     * Sanitize tab name for security
     * 
     * @param string $tab_name Tab name to sanitize
     * @return string Sanitized tab name
     */
    protected function sanitize_tab_name($tab_name)
    {
        // Only allow specific tab names
        $allowed_tabs = array('import', 'export', 'settings', 'logs');
        return in_array($tab_name, $allowed_tabs) ? $tab_name : 'import';
    }

    /**
     * Get upload directory for GEDCOM files
     * 
     * @return array Upload directory info
     */
    protected function get_upload_dir()
    {
        $upload_dir = wp_upload_dir();
        $gedcom_dir = $upload_dir['basedir'] . '/heritagepress/gedcom';
        $gedcom_url = $upload_dir['baseurl'] . '/heritagepress/gedcom';

        return array(
            'path' => $gedcom_dir,
            'url' => $gedcom_url,
            'base_dir' => $upload_dir['basedir'],
            'base_url' => $upload_dir['baseurl']
        );
    }

    /**
     * Ensure upload directory exists
     * 
     * @param string $dir_path Directory path
     * @return bool True if directory exists or was created
     */
    protected function ensure_upload_dir($dir_path)
    {
        if (!file_exists($dir_path)) {
            if (!wp_mkdir_p($dir_path)) {
                return false;
            }
        }
        return true;
    }
}
