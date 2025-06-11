<?php
/**
 * Admin Interface Manager
 *
 * Handles all WordPress backend administration functionality
 * for the HeritagePress genealogy plugin.
 *
 * @package HeritagePress
 * @subpackage Admin
 * @since 1.0.0
 */

namespace HeritagePress\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use HeritagePress\Database\Manager as SchemaManager;
use HeritagePress\Database\WPHelper;
use HeritagePress\Admin\MenuManager;
use HeritagePress\Admin\AssetManager;
use HeritagePress\Admin\FormHandler;
use HeritagePress\Admin\PageRenderer;
use HeritagePress\Admin\AjaxHandler;
use HeritagePress\Admin\DatabaseOperations;
use HeritagePress\Admin\TableManager;
use HeritagePress\Admin\ImportExport\ImportHandler;
use HeritagePress\Admin\ImportExport\ExportHandler;
use HeritagePress\Admin\ImportExport\DateHandler;
use HeritagePress\Admin\ImportExport\SettingsHandler;
use HeritagePress\Admin\ImportExport\LogsHandler;

if (!defined('HERITAGEPRESS_VERSION')) {
    define('HERITAGEPRESS_VERSION', '1.0.0');
}

/**
 * Main admin class for Heritage Press plugin
 */
class Admin
{
    /** @var SchemaManager */
    private $db_manager;

    /** @var MenuManager */
    private $menu_manager;

    /** @var AssetManager */
    private $asset_manager;

    /** @var FormHandler */
    private $form_handler;

    /** @var Admin Singleton instance */
    private static $instance = null;

    /** @var boolean Flag to track if an instance was already created */
    private static $instance_created = false;

    /** @var PageRenderer */
    private $page_renderer;    /** @var AjaxHandler */
    private $ajax_handler;

    /** @var ImportHandler */
    private $import_handler;

    /** @var ExportHandler */
    private $export_handler;

    /** @var DateHandler */
    private $date_handler;

    /** @var SettingsHandler */
    private $settings_handler;    /** @var LogsHandler */
    private $logs_handler;

    /** @var TableManager */
    private $table_manager;/**
      * Initialize the admin interface
      *
      * @param string $plugin_path Main plugin directory path
      * @param string $version Plugin version
      */    /**
           * Get the singleton instance
           *
           * @param string $plugin_path Main plugin directory path
           * @param string $version Plugin version
           * @return Admin The singleton instance
           */
    public static function get_instance($plugin_path, $version = '1.0.0')
    {
        // Generate a unique runtime ID for this request if not already set
        if (!defined('HERITAGEPRESS_ADMIN_INSTANCE_KEY')) {
            define('HERITAGEPRESS_ADMIN_INSTANCE_KEY', 'hp_admin_instance_' . HERITAGEPRESS_REQUEST_ID);
        }

        // If we already have an instance for this request
        if (defined('HERITAGEPRESS_ADMIN_INSTANCE_CREATED') && self::$instance !== null) {
            return self::$instance;
        }

        // Check if we've already created an instance in this request
        if (defined('HERITAGEPRESS_ADMIN_INSTANCE_CREATED')) {
            self::$instance = new self($plugin_path, $version);
            return self::$instance;
        }

        // First time creating the instance in this request
        define('HERITAGEPRESS_ADMIN_INSTANCE_CREATED', true);
        self::$instance = new self($plugin_path, $version);
        return self::$instance;
    }

    /**
     * Constructor is private to enforce singleton pattern
     * 
     * @param string $plugin_path Main plugin directory path
     * @param string $version Plugin version
     */
    private function __construct($plugin_path, $version = '1.0.0')
    {

        // Calculate plugin URL
        if (!defined('HERITAGEPRESS_PLUGIN_URL')) {
            define('HERITAGEPRESS_PLUGIN_URL', WPHelper::getPluginUrl($plugin_path . 'heritagepress.php'));
        }
        $plugin_url = HERITAGEPRESS_PLUGIN_URL;

        // Initialize database manager and operations
        $this->db_manager = new SchemaManager($plugin_path, $version);
        $db_ops = new class ($this->db_manager) {
            use DatabaseOperations;
            private $wpdb;
            public function __construct($db_manager)
            {
                $this->wpdb = $db_manager->get_wpdb();
            }
        };

        // Initialize form handler with database access
        $this->form_handler = new FormHandler($this->db_manager);

        // Initialize other managers
        $this->menu_manager = new MenuManager();
        $this->asset_manager = new AssetManager($plugin_url);

        // Initialize page renderer with dependencies
        $this->page_renderer = new PageRenderer(
            $plugin_path,
            $db_ops,
            $this->form_handler
        );        // Initialize AJAX handler with dependencies
        $this->ajax_handler = new AjaxHandler($this->db_manager, $db_ops);        // Initialize ImportExport handlers to register AJAX actions
        $this->import_handler = new ImportHandler();
        $this->export_handler = new ExportHandler();
        $this->date_handler = new DateHandler();
        $this->settings_handler = new SettingsHandler();
        $this->logs_handler = new LogsHandler();

        // Initialize TableManager to register AJAX actions
        $this->table_manager = new TableManager();

        // Initialize components
        $this->init();
    }    /**
         * Initialize admin components
         */
    private function init()
    {

        // Add menu items
        WPHelper::addAction('admin_menu', [$this->menu_manager, 'register_menus']);

        // Register assets
        WPHelper::addAction('admin_enqueue_scripts', [$this->asset_manager, 'enqueue_assets']);

        // Initialize AJAX handlers
        WPHelper::addAction('wp_ajax_heritagepress_delete_individuals', [$this->ajax_handler, 'handle_delete_individuals']);
        WPHelper::addAction('wp_ajax_heritagepress_search_individuals', [$this->ajax_handler, 'handle_search_individuals']);

        // Register AJAX handlers for GEDCOM statistics
        add_action('wp_ajax_hp_get_gedcom_stats', array($this, 'get_gedcom_stats'));
    }

    /**
     * AJAX handler for getting GEDCOM statistics
     */
    public function get_gedcom_stats()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hp_gedcom_upload')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $file_key = sanitize_text_field($_POST['file_key'] ?? '');

        if (empty($file_key)) {
            wp_send_json_error('File key required');
            return;
        }
        // Get file path - use same path structure as ImportHandler
        $upload_info = wp_upload_dir();
        $heritagepress_dir = $upload_info['basedir'] . '/heritagepress/gedcom/';
        $gedcom_file = $heritagepress_dir . $file_key . '.ged';

        if (!file_exists($gedcom_file)) {
            wp_send_json_error('GEDCOM file not found');
            return;
        }

        // Analyze file
        $stats = $this->analyze_gedcom_file_simple($gedcom_file);

        wp_send_json_success($stats);
    }

    /**
     * Simple GEDCOM file analysis
     */
    private function analyze_gedcom_file_simple($filepath)
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
            'total_lines' => 0
        );
        $file_handle = fopen($filepath, 'rb'); // Open in binary mode to handle BOM
        if (!$file_handle) {
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
     * Remove BOM (Byte Order Mark) from the beginning of content
     * 
     * @param string $content File content
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
