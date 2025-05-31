<?php
/**
 * Main plugin class
 *
 * @package HeritagePress
 */

namespace HeritagePress\Core;

class Plugin {
    /**
     * Instance of this class
     *
     * @var Plugin
     */
    private static $instance;

    /**
     * Get an instance of this class
     *
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }    /**
     * Initialize the plugin
     */
    public function run() {
        // Load plugin text domain
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));

        // Initialize services
        $this->init_services();

        // Initialize components
        $this->init_components();

        // Initialize admin interface
        if (is_admin()) {
            $this->init_admin();
        }

        // Initialize frontend
        $this->init_frontend();
    }

    /**
     * Load the plugin text domain for translation
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'heritage-press',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize database
        $this->init_database();

        // Initialize admin
        if (is_admin()) {
            // Initialize Evidence Admin
            // $evidence_admin = new Evidence_Admin();
            // $evidence_admin->init();
        }

        // Initialize public
        // new Frontend\Manager();
    }

    /**
     * Initialize database and ensure tables exist
     */
    private function init_database() {
        // Check if database needs to be initialized or upgraded
        $current_version = get_option('heritage_press_db_version', '0.0.0');
        $plugin_version = HERITAGE_PRESS_VERSION;
        
        if (version_compare($current_version, $plugin_version, '<')) {
            $database_manager = new \HeritagePress\Database\Database_Manager();
            $database_manager->create_tables();
            update_option('heritage_press_db_version', $plugin_version);
        }
    }

    /**
     * Initialize the plugin services
     */
    private function init_services() {
        $container = Container::getInstance();        // Register database services
        $container->singleton('database.manager', function() {
            return new \HeritagePress\Database\Database_Manager();
        });

        $container->singleton('database.gedcom', function() {
            return new \HeritagePress\Database\GedcomDatabaseHandler();
        });

        // Register GEDCOM services
        $container->singleton('gedcom.parser', function() {
            return new \HeritagePress\Gedcom\Gedcom7_Parser();
        });

        $container->singleton('gedcom.validator', function() {
            return new \HeritagePress\Gedcom\Gedcom7_Validator();
        });

        $container->singleton('gedcom.export', function() {
            return new \HeritagePress\Gedcom\GedcomExportHandler();
        });        // Register event handler
        $container->singleton('events', function() {
            return new \HeritagePress\Core\GedcomEvents();
        });
    }

    /**
     * Initialize admin interface
     */    private function init_admin() {
        // Register all repositories in the container
        $this->register_repositories();
        
        // Initialize admin menus and interfaces
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Register AJAX handlers
        $this->register_ajax_handlers();
    }

    /**
     * Initialize frontend
     */
    private function init_frontend() {
        // Frontend initialization
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }

    /**
     * Register all repositories in the service container
     */
    private function register_repositories() {
        $container = Container::getInstance();
        
        // Register audit log observer
        $container->singleton('audit.observer', function() {
            return new Audit_Log_Observer();
        });

        // Register repositories with audit observer dependency
        $container->singleton('repository.individual', function() use ($container) {
            return new \HeritagePress\Repositories\Individual_Repository(
                $container->get('audit.observer')
            );
        });

        $container->singleton('repository.family', function() use ($container) {
            return new \HeritagePress\Repositories\Family_Repository(
                $container->get('audit.observer')
            );
        });

        $container->singleton('repository.gedcom_tree', function() use ($container) {
            return new \HeritagePress\Repositories\Gedcom_Tree_Repository(
                $container->get('audit.observer')
            );
        });

        // Register other repositories as needed
    }

    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        add_menu_page(
            __('Heritage Press', 'heritage-press'),
            __('Heritage Press', 'heritage-press'),
            'manage_options',
            'heritage-press',
            array($this, 'render_main_admin_page'),
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'heritage-press',
            __('Individuals', 'heritage-press'),
            __('Individuals', 'heritage-press'),
            'manage_options',
            'heritage-press-individuals',
            array($this, 'render_individuals_page')
        );

        add_submenu_page(
            'heritage-press',
            __('Families', 'heritage-press'),
            __('Families', 'heritage-press'),
            'manage_options',
            'heritage-press-families',
            array($this, 'render_families_page')
        );

        add_submenu_page(
            'heritage-press',
            __('GEDCOM Import', 'heritage-press'),
            __('GEDCOM Import', 'heritage-press'),
            'manage_options',
            'heritage-press-import',
            array($this, 'render_import_page')
        );
    }

    /**
     * Render main admin page
     */
    public function render_main_admin_page() {
        include HERITAGE_PRESS_PLUGIN_DIR . 'admin/views/main-admin.php';
    }

    /**
     * Render individuals page
     */
    public function render_individuals_page() {
        include HERITAGE_PRESS_PLUGIN_DIR . 'admin/views/individuals.php';
    }

    /**
     * Render families page
     */
    public function render_families_page() {
        include HERITAGE_PRESS_PLUGIN_DIR . 'admin/views/families.php';
    }

    /**
     * Render import page
     */
    public function render_import_page() {
        include HERITAGE_PRESS_PLUGIN_DIR . 'admin/views/import.php';
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'heritage-press') === false) {
            return;
        }

        wp_enqueue_style(
            'heritage-press-admin',
            plugin_dir_url(HERITAGE_PRESS_PLUGIN_DIR . 'heritage-press.php') . 'admin/css/admin.css',
            array(),
            HERITAGE_PRESS_VERSION
        );

        wp_enqueue_script(
            'heritage-press-admin',
            plugin_dir_url(HERITAGE_PRESS_PLUGIN_DIR . 'heritage-press.php') . 'admin/js/admin.js',
            array('jquery'),
            HERITAGE_PRESS_VERSION,
            true
        );        wp_localize_script('heritage-press-admin', 'heritage_press_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('heritage_press_admin'),
            'admin_url' => admin_url(),
            'messages' => array(
                'no_file_selected' => __('Please select a GEDCOM file to import.', 'heritage-press'),
                'file_too_large' => __('File is too large. Maximum size is 32MB.', 'heritage-press'),
                'invalid_file_type' => __('Please select a valid GEDCOM file (.ged or .gedcom).', 'heritage-press'),
                'reading_file' => __('Reading GEDCOM file...', 'heritage-press'),
                'parsing_data' => __('Parsing genealogy data...', 'heritage-press'),
                'importing_individuals' => __('Importing individuals...', 'heritage-press'),
                'importing_families' => __('Importing families and relationships...', 'heritage-press'),
                'finalizing_import' => __('Finalizing import...', 'heritage-press'),
                'import_complete' => __('Import completed successfully!', 'heritage-press'),
                'import_success' => __('Your GEDCOM file has been imported successfully. You can now view your family tree data.', 'heritage-press'),
                'confirm_archive' => __('Are you sure you want to archive this tree? Archived trees can be restored later.', 'heritage-press'),
                'confirm_delete' => __('Are you sure you want to permanently delete this item? This action cannot be undone.', 'heritage-press'),
                'error_occurred' => __('An error occurred. Please try again.', 'heritage-press'),
                'search_individuals' => __('Search individuals...', 'heritage-press'),
                'search_families' => __('Search families...', 'heritage-press'),
                'loading' => __('Loading...', 'heritage-press'),
                'save_changes' => __('Save Changes', 'heritage-press'),
                'cancel' => __('Cancel', 'heritage-press'),
                'edit' => __('Edit', 'heritage-press'),
                'delete' => __('Delete', 'heritage-press'),
                'view' => __('View', 'heritage-press')
            )
        ));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style(
            'heritage-press-frontend',
            plugin_dir_url(HERITAGE_PRESS_PLUGIN_DIR . 'heritage-press.php') . 'public/css/frontend.css',
            array(),
            HERITAGE_PRESS_VERSION
        );        wp_enqueue_script(
            'heritage-press-frontend',
            plugin_dir_url(HERITAGE_PRESS_PLUGIN_DIR . 'heritage-press.php') . 'public/js/frontend.js',
            array('jquery'),
            HERITAGE_PRESS_VERSION,
            true
        );
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        // Individual search and management
        add_action('wp_ajax_heritage_press_search_individuals', array($this, 'ajax_search_individuals'));
        add_action('wp_ajax_heritage_press_get_individual', array($this, 'ajax_get_individual'));
        add_action('wp_ajax_heritage_press_save_individual', array($this, 'ajax_save_individual'));
        add_action('wp_ajax_heritage_press_delete_individual', array($this, 'ajax_delete_individual'));
        
        // Family management
        add_action('wp_ajax_heritage_press_search_families', array($this, 'ajax_search_families'));
        add_action('wp_ajax_heritage_press_get_family', array($this, 'ajax_get_family'));
        add_action('wp_ajax_heritage_press_save_family', array($this, 'ajax_save_family'));
        
        // GEDCOM import
        add_action('wp_ajax_heritage_press_upload_gedcom', array($this, 'ajax_upload_gedcom'));
        add_action('wp_ajax_heritage_press_import_progress', array($this, 'ajax_import_progress'));
        
        // Dashboard data
        add_action('wp_ajax_heritage_press_dashboard_stats', array($this, 'ajax_dashboard_stats'));
    }

    /**
     * AJAX: Search individuals
     */
    public function ajax_search_individuals() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_press_admin')) {
            wp_die('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $container = Container::getInstance();
        $individual_repo = $container->get('repository.individual');

        // Get search parameters
        $search_term = sanitize_text_field($_POST['search'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);        $criteria = [];
        if (!empty($search_term)) {
            $criteria['name'] = $search_term;
        }

        // Get results based on search criteria
        if (!empty($criteria)) {
            $individuals = $individual_repo->search($criteria, $per_page, ($page - 1) * $per_page);
            $total = $individual_repo->search_count($criteria);
            $results = [
                'items' => $individuals,
                'total' => $total,
                'pages' => ceil($total / $per_page),
                'current_page' => $page,
                'per_page' => $per_page
            ];
        } else {
            // Get paginated results for all individuals
            $results = $individual_repo->get_paginated($page, $per_page);
        }

        // Format response
        $response = [
            'success' => true,
            'data' => [
                'individuals' => array_map(function($individual) {
                    return [
                        'id' => $individual->get_id(),
                        'uuid' => $individual->get_uuid(),
                        'given_names' => $individual->get_given_names(),
                        'surname' => $individual->get_surname(),
                        'full_name' => trim($individual->get_given_names() . ' ' . $individual->get_surname()),
                        'sex' => $individual->get_sex(),
                        'birth_date' => $individual->get_birth_date(),
                        'death_date' => $individual->get_death_date(),                        'living_status' => $individual->get_living_status()
                    ];
                }, $results['items']),
                'pagination' => [
                    'total' => $results['total'],
                    'pages' => $results['pages'],
                    'current_page' => $results['current_page'],
                    'per_page' => $results['per_page']
                ]
            ]
        ];

        wp_send_json($response);
    }

    /**
     * AJAX: Get individual details
     */
    public function ajax_get_individual() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_press_admin')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $individual_id = intval($_POST['id'] ?? 0);
        if (!$individual_id) {
            wp_send_json_error('Invalid individual ID');
        }

        $container = Container::getInstance();
        $individual_repo = $container->get('repository.individual');
        $individual = $individual_repo->get_by_id($individual_id);

        if (!$individual) {
            wp_send_json_error('Individual not found');
        }

        $response = [
            'id' => $individual->get_id(),
            'uuid' => $individual->get_uuid(),
            'given_names' => $individual->get_given_names(),
            'surname' => $individual->get_surname(),
            'title' => $individual->get_title(),
            'sex' => $individual->get_sex(),
            'birth_date' => $individual->get_birth_date(),
            'birth_place' => $individual->get_birth_place(),
            'death_date' => $individual->get_death_date(),
            'death_place' => $individual->get_death_place(),
            'living_status' => $individual->get_living_status(),
            'notes' => $individual->get_notes(),
            'created_at' => $individual->get_created_at(),
            'updated_at' => $individual->get_updated_at()
        ];

        wp_send_json_success($response);
    }

    /**
     * AJAX: Save individual
     */
    public function ajax_save_individual() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_press_admin')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $container = Container::getInstance();
        $individual_repo = $container->get('repository.individual');

        $individual_id = intval($_POST['id'] ?? 0);
        $data = [
            'given_names' => sanitize_text_field($_POST['given_names'] ?? ''),
            'surname' => sanitize_text_field($_POST['surname'] ?? ''),
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'sex' => sanitize_text_field($_POST['sex'] ?? ''),
            'birth_date' => sanitize_text_field($_POST['birth_date'] ?? ''),
            'birth_place' => sanitize_text_field($_POST['birth_place'] ?? ''),
            'death_date' => sanitize_text_field($_POST['death_date'] ?? ''),
            'death_place' => sanitize_text_field($_POST['death_place'] ?? ''),
            'living_status' => sanitize_text_field($_POST['living_status'] ?? 'unknown'),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
        ];

        if ($individual_id) {
            // Update existing individual
            $success = $individual_repo->update($individual_id, $data);
            $message = $success ? 'Individual updated successfully' : 'Failed to update individual';
        } else {
            // Create new individual
            $data['uuid'] = wp_generate_uuid4();
            $data['file_id'] = 1; // Default file ID for manual entries
            $individual_id = $individual_repo->create($data);
            $success = $individual_id !== false;
            $message = $success ? 'Individual created successfully' : 'Failed to create individual';
        }

        if ($success) {
            wp_send_json_success(['id' => $individual_id, 'message' => $message]);
        } else {
            wp_send_json_error($message);
        }
    }

    /**
     * AJAX: Delete individual
     */
    public function ajax_delete_individual() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_press_admin')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $individual_id = intval($_POST['id'] ?? 0);
        if (!$individual_id) {
            wp_send_json_error('Invalid individual ID');
        }

        $container = Container::getInstance();
        $individual_repo = $container->get('repository.individual');
        $success = $individual_repo->delete($individual_id);

        if ($success) {
            wp_send_json_success('Individual deleted successfully');
        } else {
            wp_send_json_error('Failed to delete individual');
        }
    }

    /**
     * AJAX: Search families (placeholder)
     */
    public function ajax_search_families() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_press_admin')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        // Placeholder response for families
        wp_send_json_success([
            'families' => [],
            'pagination' => [
                'total' => 0,
                'pages' => 0,
                'current_page' => 1,
                'per_page' => 20
            ]
        ]);
    }

    /**
     * AJAX: Get family details (placeholder)
     */
    public function ajax_get_family() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_press_admin')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        wp_send_json_error('Family management not yet implemented');
    }

    /**
     * AJAX: Save family (placeholder)
     */
    public function ajax_save_family() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_press_admin')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        wp_send_json_error('Family management not yet implemented');
    }

    /**
     * AJAX: Upload GEDCOM file
     */
    public function ajax_upload_gedcom() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_press_admin')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        if (!isset($_FILES['gedcom_file'])) {
            wp_send_json_error('No file uploaded');
        }

        $file = $_FILES['gedcom_file'];
        
        // Validate file
        $allowed_extensions = ['ged', 'gedcom'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            wp_send_json_error('Invalid file type. Please upload a .ged or .gedcom file.');
        }

        // Check file size (32MB max)
        if ($file['size'] > 32 * 1024 * 1024) {
            wp_send_json_error('File too large. Maximum size is 32MB.');
        }

        // Handle file upload
        $upload_dir = wp_upload_dir();
        $heritage_dir = $upload_dir['basedir'] . '/heritage-press/';
        
        if (!file_exists($heritage_dir)) {
            wp_mkdir_p($heritage_dir);
        }

        $filename = 'gedcom_' . time() . '_' . sanitize_file_name($file['name']);
        $filepath = $heritage_dir . $filename;        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Store import session data
            $import_id = uniqid('import_');
            set_transient('heritage_press_import_' . $import_id, [
                'file_path' => $filepath,
                'original_name' => $file['name'],
                'status' => 'uploaded',
                'progress' => 0,
                'started_at' => current_time('mysql')
            ], HOUR_IN_SECONDS);

            // Start basic GEDCOM processing
            $this->process_gedcom_file($import_id, $filepath);

            wp_send_json_success([
                'import_id' => $import_id,
                'message' => 'File uploaded successfully. Import processing started.',
                'filename' => $file['name']
            ]);
        } else {
            wp_send_json_error('Failed to upload file');
        }
    }

    /**
     * AJAX: Get import progress
     */
    public function ajax_import_progress() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_press_admin')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $import_id = sanitize_text_field($_POST['import_id'] ?? '');
        if (!$import_id) {
            wp_send_json_error('Invalid import ID');
        }

        $import_data = get_transient('heritage_press_import_' . $import_id);
        if (!$import_data) {
            wp_send_json_error('Import session not found');
        }

        // For demo purposes, simulate progress
        // In real implementation, this would check actual import progress
        $elapsed = time() - strtotime($import_data['started_at']);
        $progress = min(100, ($elapsed / 30) * 100); // Complete in 30 seconds for demo

        $stages = [
            'Reading file...',
            'Parsing GEDCOM data...',
            'Importing individuals...',
            'Importing families...',
            'Finalizing import...'
        ];

        $current_stage = min(floor($progress / 20), count($stages) - 1);

        wp_send_json_success([
            'progress' => $progress,
            'stage' => $stages[$current_stage],
            'completed' => $progress >= 100
        ]);
    }

    /**
     * AJAX: Get dashboard statistics
     */
    public function ajax_dashboard_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_press_admin')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $container = Container::getInstance();
        $individual_repo = $container->get('repository.individual');

        // Get basic statistics
        $all_individuals = $individual_repo->get_all();
        $total_individuals = count($all_individuals);
        
        // Calculate other stats
        $living_count = 0;
        $deceased_count = 0;
        $male_count = 0;
        $female_count = 0;

        foreach ($all_individuals as $individual) {
            if ($individual->get_living_status() === 'living') {
                $living_count++;
            } elseif ($individual->get_living_status() === 'deceased') {
                $deceased_count++;
            }

            if ($individual->get_sex() === 'M') {
                $male_count++;
            } elseif ($individual->get_sex() === 'F') {
                $female_count++;
            }
        }

        wp_send_json_success([
            'total_individuals' => $total_individuals,
            'total_families' => 0, // Placeholder
            'living_individuals' => $living_count,
            'deceased_individuals' => $deceased_count,
            'male_count' => $male_count,
            'female_count' => $female_count,
            'recent_imports' => 0 // Placeholder
        ]);
    }

    /**
     * Process GEDCOM file - basic implementation
     *
     * @param string $import_id Import session ID
     * @param string $filepath Path to GEDCOM file
     */
    private function process_gedcom_file($import_id, $filepath) {
        try {
            // Update status to processing
            $import_data = get_transient('heritage_press_import_' . $import_id);
            $import_data['status'] = 'processing';
            $import_data['progress'] = 10;
            set_transient('heritage_press_import_' . $import_id, $import_data, HOUR_IN_SECONDS);

            // Basic GEDCOM file parsing
            $file_handle = fopen($filepath, 'r');
            if (!$file_handle) {
                throw new \Exception('Cannot open GEDCOM file');
            }

            $individual_count = 0;
            $family_count = 0;
            $line_count = 0;
            $current_record = null;
            $current_data = [];

            $container = Container::getInstance();
            $individual_repo = $container->get('individual_repository');

            while (($line = fgets($file_handle)) !== false) {
                $line_count++;
                $line = trim($line);
                
                if (empty($line)) continue;

                // Basic GEDCOM line parsing
                if (preg_match('/^(\d+)\s+(\@[^@]+\@)?\s*([A-Z0-9_]+)(?:\s+(.*))?$/', $line, $matches)) {
                    $level = (int)$matches[1];
                    $xref = isset($matches[2]) ? trim($matches[2], '@') : null;
                    $tag = $matches[3];
                    $value = isset($matches[4]) ? $matches[4] : '';

                    // Process individual records
                    if ($level === 0 && $tag === 'INDI' && $xref) {
                        // Save previous record if exists
                        if ($current_record === 'INDI' && !empty($current_data)) {
                            $this->save_individual_record($current_data, $individual_repo);
                            $individual_count++;
                        }
                        
                        // Start new individual record
                        $current_record = 'INDI';
                        $current_data = [
                            'uuid' => $xref,
                            'file_id' => 1, // Default file ID
                            'given_names' => '',
                            'surname' => '',
                            'sex' => '',
                            'birth_date' => '',
                            'birth_place' => '',
                            'death_date' => '',
                            'death_place' => '',
                            'living' => 0
                        ];
                    }
                    // Process family records
                    elseif ($level === 0 && $tag === 'FAM' && $xref) {
                        // Save previous individual record if exists
                        if ($current_record === 'INDI' && !empty($current_data)) {
                            $this->save_individual_record($current_data, $individual_repo);
                            $individual_count++;
                        }
                        
                        $current_record = 'FAM';
                        $family_count++;
                        $current_data = []; // Reset for family data
                    }
                    // Process name data
                    elseif ($level === 1 && $tag === 'NAME' && $current_record === 'INDI') {
                        $this->parse_name($value, $current_data);
                    }
                    // Process sex data
                    elseif ($level === 1 && $tag === 'SEX' && $current_record === 'INDI') {
                        $current_data['sex'] = $value;
                    }
                    // Process birth event
                    elseif ($level === 1 && $tag === 'BIRT' && $current_record === 'INDI') {
                        $current_data['_processing_birth'] = true;
                    }
                    // Process death event
                    elseif ($level === 1 && $tag === 'DEAT' && $current_record === 'INDI') {
                        $current_data['_processing_death'] = true;
                    }
                    // Process date within events
                    elseif ($level === 2 && $tag === 'DATE') {
                        if (isset($current_data['_processing_birth'])) {
                            $current_data['birth_date'] = $this->parse_date($value);
                        } elseif (isset($current_data['_processing_death'])) {
                            $current_data['death_date'] = $this->parse_date($value);
                        }
                    }
                    // Process place within events
                    elseif ($level === 2 && $tag === 'PLAC') {
                        if (isset($current_data['_processing_birth'])) {
                            $current_data['birth_place'] = $value;
                        } elseif (isset($current_data['_processing_death'])) {
                            $current_data['death_place'] = $value;
                        }
                    }
                }

                // Update progress every 1000 lines
                if ($line_count % 1000 === 0) {
                    $import_data['progress'] = min(90, 10 + (($line_count / 10000) * 80));
                    set_transient('heritage_press_import_' . $import_id, $import_data, HOUR_IN_SECONDS);
                }
            }

            // Save final individual record
            if ($current_record === 'INDI' && !empty($current_data)) {
                $this->save_individual_record($current_data, $individual_repo);
                $individual_count++;
            }

            fclose($file_handle);

            // Update final status
            $import_data['status'] = 'completed';
            $import_data['progress'] = 100;
            $import_data['individuals_imported'] = $individual_count;
            $import_data['families_imported'] = $family_count;
            $import_data['completed_at'] = current_time('mysql');
            set_transient('heritage_press_import_' . $import_id, $import_data, HOUR_IN_SECONDS);

        } catch (\Exception $e) {
            // Update error status
            $import_data = get_transient('heritage_press_import_' . $import_id);
            $import_data['status'] = 'error';
            $import_data['error'] = $e->getMessage();
            set_transient('heritage_press_import_' . $import_id, $import_data, HOUR_IN_SECONDS);
        }
    }

    /**
     * Parse GEDCOM name format
     *
     * @param string $name_value GEDCOM name value
     * @param array &$data Current individual data
     */
    private function parse_name($name_value, &$data) {
        // Basic GEDCOM name parsing: "Given Names /Surname/"
        if (preg_match('/^(.*?)\s*\/([^\/]*)\/?$/', $name_value, $matches)) {
            $data['given_names'] = trim($matches[1]);
            $data['surname'] = trim($matches[2]);
        } else {
            // No surname marker, treat as given names
            $data['given_names'] = trim($name_value);
        }
    }

    /**
     * Parse GEDCOM date format
     *
     * @param string $date_value GEDCOM date value
     * @return string Standardized date or empty string
     */
    private function parse_date($date_value) {
        // Basic date parsing - handle common formats
        $date_value = trim($date_value);
        
        // Remove qualifiers like "ABT", "EST", "BEF", "AFT"
        $date_value = preg_replace('/^(ABT|EST|BEF|AFT|CAL|INT)\s+/', '', $date_value);
        
        // Try to parse standard date formats
        if (preg_match('/^(\d{1,2})\s+(JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC)\s+(\d{4})$/', $date_value, $matches)) {
            $months = [
                'JAN' => '01', 'FEB' => '02', 'MAR' => '03', 'APR' => '04',
                'MAY' => '05', 'JUN' => '06', 'JUL' => '07', 'AUG' => '08',
                'SEP' => '09', 'OCT' => '10', 'NOV' => '11', 'DEC' => '12'
            ];
            return sprintf('%04d-%02d-%02d', $matches[3], $months[$matches[2]], $matches[1]);
        }
        
        // Try year only
        if (preg_match('/^(\d{4})$/', $date_value, $matches)) {
            return $matches[1] . '-01-01';
        }
        
        // Return original if can't parse
        return $date_value;
    }

    /**
     * Save individual record to database
     *
     * @param array $data Individual data
     * @param object $repository Individual repository
     */
    private function save_individual_record($data, $repository) {
        try {
            // Clean up temporary processing flags
            unset($data['_processing_birth'], $data['_processing_death']);
            
            // Validate required fields
            if (empty($data['uuid'])) {
                return false;
            }
            
            // Check if individual already exists
            $existing = $repository->get_by_uuid($data['uuid']);
            if ($existing) {
                return false; // Skip duplicates
            }
            
            // Create new individual
            $repository->create($data);
            return true;
        } catch (\Exception $e) {
            error_log('Heritage Press GEDCOM Import Error: ' . $e->getMessage());
            return false;
        }
    }
}
