<?php
/**
 * Admin class for Heritage Press
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Heritage Press Admin Class
 */
class Heritage_Press_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_heritage_press_search_individuals', array($this, 'ajax_search_individuals'));
        add_action('wp_ajax_heritage_press_import_gedcom', array($this, 'ajax_import_gedcom'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            'Heritage Press',
            'Heritage Press',
            'manage_options',
            'heritage-press',
            array($this, 'dashboard_page'),
            'dashicons-groups',
            30
        );
        
        // Sub-menu pages
        add_submenu_page(
            'heritage-press',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'heritage-press',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'heritage-press',
            'Individuals',
            'Individuals',
            'manage_options',
            'heritage-press-individuals',
            array($this, 'individuals_page')
        );
        
        add_submenu_page(
            'heritage-press',
            'Families',
            'Families',
            'manage_options',
            'heritage-press-families',
            array($this, 'families_page')
        );
        
        add_submenu_page(
            'heritage-press',
            'GEDCOM Import',
            'GEDCOM Import',
            'manage_options',
            'heritage-press-import',
            array($this, 'import_page')
        );
        
        add_submenu_page(
            'heritage-press',
            'Settings',
            'Settings',
            'manage_options',
            'heritage-press-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on Heritage Press admin pages
        if (strpos($hook, 'heritage-press') === false) {
            return;
        }
        
        wp_enqueue_style(
            'heritage-press-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/admin.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'heritage-press-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Localize script for AJAX
        wp_localize_script(
            'heritage-press-admin',
            'heritage_press_ajax',
            array(
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('heritage_press_nonce')
            )
        );
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        // Get statistics
        global $wpdb;
        
        $individuals_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}heritage_press_individuals");
        $families_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}heritage_press_families");
        $relationships_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}heritage_press_relationships");
        
        // Recent individuals
        $recent_individuals = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}heritage_press_individuals 
             ORDER BY created_at DESC LIMIT 5"
        );
        
        include plugin_dir_path(dirname(__FILE__)) . 'admin/views/dashboard.php';
    }
    
    /**
     * Individuals management page
     */
    public function individuals_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $individual_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        switch ($action) {
            case 'edit':
            case 'add':
                $this->individual_form_page($individual_id);
                break;
            case 'delete':
                $this->delete_individual($individual_id);
                break;
            default:
                $this->individuals_list_page();
                break;
        }
    }
    
    /**
     * Individuals list page
     */
    private function individuals_list_page() {
        global $wpdb;
        
        // Handle search
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $where_clause = '';
        
        if (!empty($search)) {
            $where_clause = $wpdb->prepare(
                "WHERE first_name LIKE %s OR last_name LIKE %s",
                '%' . $search . '%',
                '%' . $search . '%'
            );
        }
        
        // Pagination
        $per_page = 20;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($page - 1) * $per_page;
        
        // Get individuals
        $individuals = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}heritage_press_individuals 
             $where_clause
             ORDER BY last_name, first_name 
             LIMIT $per_page OFFSET $offset"
        );
        
        // Get total count for pagination
        $total_items = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}heritage_press_individuals $where_clause"
        );
        
        $total_pages = ceil($total_items / $per_page);
        
        include plugin_dir_path(dirname(__FILE__)) . 'admin/views/individuals-list.php';
    }
    
    /**
     * Individual form page
     */
    private function individual_form_page($individual_id) {
        $individual = null;
        $is_edit = false;
        
        if ($individual_id > 0) {
            $individual = Heritage_Press_Individual::get_by_id($individual_id);
            $is_edit = true;
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && wp_verify_nonce($_POST['heritage_press_nonce'], 'save_individual')) {
            $this->save_individual($individual_id);
            return;
        }
        
        include plugin_dir_path(dirname(__FILE__)) . 'admin/views/individual-form.php';
    }
    
    /**
     * Save individual
     */
    private function save_individual($individual_id) {
        $individual = $individual_id > 0 ? Heritage_Press_Individual::get_by_id($individual_id) : new Heritage_Press_Individual();
        
        if (!$individual) {
            wp_die('Individual not found.');
        }
        
        // Sanitize and save data
        $individual->first_name = sanitize_text_field($_POST['first_name']);
        $individual->last_name = sanitize_text_field($_POST['last_name']);
        $individual->gender = sanitize_text_field($_POST['gender']);
        $individual->birth_date = sanitize_text_field($_POST['birth_date']);
        $individual->birth_place = sanitize_text_field($_POST['birth_place']);
        $individual->death_date = sanitize_text_field($_POST['death_date']);
        $individual->death_place = sanitize_text_field($_POST['death_place']);
        $individual->notes = sanitize_textarea_field($_POST['notes']);
        
        if ($individual->save()) {
            wp_redirect(admin_url('admin.php?page=heritage-press-individuals&message=saved'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=heritage-press-individuals&message=error'));
            exit;
        }
    }
    
    /**
     * Delete individual
     */
    private function delete_individual($individual_id) {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_individual_' . $individual_id)) {
            wp_die('Security check failed.');
        }
        
        $individual = Heritage_Press_Individual::get_by_id($individual_id);
        if ($individual && $individual->delete()) {
            wp_redirect(admin_url('admin.php?page=heritage-press-individuals&message=deleted'));
        } else {
            wp_redirect(admin_url('admin.php?page=heritage-press-individuals&message=error'));
        }
        exit;
    }
    
    /**
     * Families page
     */
    public function families_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $family_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        switch ($action) {
            case 'edit':
            case 'add':
                $this->family_form_page($family_id);
                break;
            case 'delete':
                $this->delete_family($family_id);
                break;
            default:
                $this->families_list_page();
                break;
        }
    }
    
    /**
     * Families list page
     */
    private function families_list_page() {
        global $wpdb;
        
        // Pagination
        $per_page = 20;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($page - 1) * $per_page;
        
        // Get families with husband and wife names
        $families = $wpdb->get_results(
            "SELECT f.*, 
                    h.first_name as husband_first_name, h.last_name as husband_last_name,
                    w.first_name as wife_first_name, w.last_name as wife_last_name
             FROM {$wpdb->prefix}heritage_press_families f
             LEFT JOIN {$wpdb->prefix}heritage_press_individuals h ON f.husband_id = h.id
             LEFT JOIN {$wpdb->prefix}heritage_press_individuals w ON f.wife_id = w.id
             ORDER BY f.created_at DESC
             LIMIT $per_page OFFSET $offset"
        );
        
        // Get total count for pagination
        $total_items = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}heritage_press_families"
        );
        
        $total_pages = ceil($total_items / $per_page);
        
        include plugin_dir_path(dirname(__FILE__)) . 'admin/views/families-list.php';
    }
    
    /**
     * Family form page
     */
    private function family_form_page($family_id) {
        $family = null;
        $is_edit = false;
        
        if ($family_id > 0) {
            $family = Heritage_Press_Family::get_by_id($family_id);
            $is_edit = true;
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && wp_verify_nonce($_POST['heritage_press_nonce'], 'save_family')) {
            $this->save_family($family_id);
            return;
        }
        
        include plugin_dir_path(dirname(__FILE__)) . 'admin/views/family-form.php';
    }
    
    /**
     * Save family
     */
    private function save_family($family_id) {
        $family = $family_id > 0 ? Heritage_Press_Family::get_by_id($family_id) : new Heritage_Press_Family();
        
        if (!$family) {
            wp_die('Family not found.');
        }
        
        // Sanitize and save data
        $family->husband_id = intval($_POST['husband_id']) ?: null;
        $family->wife_id = intval($_POST['wife_id']) ?: null;
        $family->marriage_date = sanitize_text_field($_POST['marriage_date']);
        $family->marriage_place = sanitize_text_field($_POST['marriage_place']);
        $family->notes = sanitize_textarea_field($_POST['notes']);
        
        if ($family->save()) {
            wp_redirect(admin_url('admin.php?page=heritage-press-families&message=saved'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=heritage-press-families&message=error'));
            exit;
        }
    }
    
    /**
     * Delete family
     */
    private function delete_family($family_id) {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_family_' . $family_id)) {
            wp_die('Security check failed.');
        }
        
        $family = Heritage_Press_Family::get_by_id($family_id);
        if ($family && $family->delete()) {
            wp_redirect(admin_url('admin.php?page=heritage-press-families&message=deleted'));
        } else {
            wp_redirect(admin_url('admin.php?page=heritage-press-families&message=error'));
        }
        exit;
    }
    
    /**
     * Import page
     */
    public function import_page() {
        // Handle file upload
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && wp_verify_nonce($_POST['heritage_press_nonce'], 'import_gedcom')) {
            $this->handle_gedcom_upload();
            return;
        }
        
        include plugin_dir_path(dirname(__FILE__)) . 'admin/views/import.php';
    }
    
    /**
     * Handle GEDCOM upload
     */
    private function handle_gedcom_upload() {
        if (!isset($_FILES['gedcom_file'])) {
            wp_redirect(admin_url('admin.php?page=heritage-press-import&message=no_file'));
            exit;
        }
        
        $file = $_FILES['gedcom_file'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(admin_url('admin.php?page=heritage-press-import&message=upload_error'));
            exit;
        }
        
        // Move file to temporary location
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['path'] . '/' . uniqid('gedcom_') . '.ged';
        
        if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
            wp_redirect(admin_url('admin.php?page=heritage-press-import&message=move_error'));
            exit;
        }
        
        // Parse and import
        $parser = new Heritage_Press_GEDCOM_Parser();
        $parse_result = $parser->parse_file($temp_file);
        
        if ($parse_result) {
            $import_result = $parser->import_to_database();
            
            // Clean up temp file
            unlink($temp_file);
            
            // Redirect with results
            $message = sprintf(
                'imported&individuals=%d&families=%d&errors=%d',
                $import_result['individuals_imported'],
                $import_result['families_imported'],
                count($import_result['errors'])
            );
            
            wp_redirect(admin_url('admin.php?page=heritage-press-import&message=' . $message));
        } else {
            unlink($temp_file);
            wp_redirect(admin_url('admin.php?page=heritage-press-import&message=parse_error'));
        }
        
        exit;
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && wp_verify_nonce($_POST['heritage_press_nonce'], 'save_settings')) {
            $this->save_settings();
            return;
        }
        
        include plugin_dir_path(dirname(__FILE__)) . 'admin/views/settings.php';
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        // Save various settings
        update_option('heritage_press_date_format', sanitize_text_field($_POST['date_format']));
        update_option('heritage_press_default_privacy', sanitize_text_field($_POST['default_privacy']));
        update_option('heritage_press_enable_frontend', isset($_POST['enable_frontend']) ? 1 : 0);
        
        wp_redirect(admin_url('admin.php?page=heritage-press-settings&message=saved'));
        exit;
    }
    
    /**
     * AJAX search individuals
     */
    public function ajax_search_individuals() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_press_nonce')) {
            wp_die('Security check failed');
        }
        
        $search = sanitize_text_field($_POST['search']);
        $individuals = Heritage_Press_Individual::search($search, 10);
        
        $results = array();
        foreach ($individuals as $individual) {
            $results[] = array(
                'id' => $individual->id,
                'name' => $individual->get_display_name(),
                'first_name' => $individual->first_name,
                'last_name' => $individual->last_name
            );
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * AJAX import GEDCOM
     */
    public function ajax_import_gedcom() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_press_nonce')) {
            wp_die('Security check failed');
        }
        
        // This would handle large GEDCOM imports in chunks
        // For now, just return success
        wp_send_json_success(array('message' => 'Import started'));
    }
}
