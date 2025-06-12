<?php
namespace HeritagePress\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Trees Management Class for HeritagePress
 * Handles tree creation, editing, deletion, and display
 * Based on proven genealogy software patterns
 */
class TreesManager
{
    /**
     * @var object WordPress database object
     */
    private $wpdb;

    /**
     * @var string Table name for trees
     */
    private $trees_table;

    /**
     * @var string Table name for people
     */
    private $people_table;

    /**
     * @var string Table name for families
     */
    private $families_table;

    /**
     * @var string Table name for sources
     */
    private $sources_table;

    /**
     * @var string Table name for repositories
     */
    private $repositories_table;

    /**
     * @var string Table name for notes
     */
    private $notes_table;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Initialize table names
        $this->trees_table = $wpdb->prefix . 'hp_trees';
        $this->people_table = $wpdb->prefix . 'hp_people';
        $this->families_table = $wpdb->prefix . 'hp_families';
        $this->sources_table = $wpdb->prefix . 'hp_sources';
        $this->repositories_table = $wpdb->prefix . 'hp_repositories';
        $this->notes_table = $wpdb->prefix . 'hp_xnotes';

        // Handle AJAX requests
        add_action('wp_ajax_hp_trees_action', [$this, 'handle_ajax_request']);
        add_action('wp_ajax_nopriv_hp_trees_action', [$this, 'handle_ajax_request']);
    }

    /**
     * Render the main trees management page
     */
    public function render_page()
    {
        // Get current action
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $tree_id = isset($_GET['tree']) ? intval($_GET['tree']) : 0;

        // Enqueue scripts and styles
        $this->enqueue_assets();

        // Handle POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handle_post_request();
            return;
        }

        // Route to appropriate view
        switch ($action) {
            case 'edit':
                $this->render_edit_tree($tree_id);
                break;
            case 'add':
                $this->render_add_tree();
                break;
            case 'delete':
                $this->render_delete_tree($tree_id);
                break;
            default:
                $this->render_trees_list();
                break;
        }
    }

    /**
     * Enqueue CSS and JavaScript assets
     */
    private function enqueue_assets()
    {
        // Enqueue WordPress admin styles
        wp_enqueue_style('wp-admin');
        wp_enqueue_style('dashicons');

        // Enqueue custom Trees CSS
        wp_enqueue_style(
            'heritagepress-trees',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/admin-trees.css',
            ['wp-admin'],
            HERITAGEPRESS_VERSION
        );

        // Enqueue JavaScript
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'heritagepress-trees',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/admin-trees.js',
            ['jquery'],
            HERITAGEPRESS_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script('heritagepress-trees', 'hpTrees', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hp_trees_nonce'),
            'strings' => [
                'confirm_delete' => __('Are you sure you want to delete this tree? This action cannot be undone.', 'heritagepress'),
                'enter_tree_name' => __('Please enter a tree name.', 'heritagepress'),
                'enter_tree_id' => __('Please enter a tree ID.', 'heritagepress')
            ]
        ]);
    }

    /**
     * Handle POST requests (form submissions)
     */
    private function handle_post_request()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_trees_action')) {
            wp_die(__('Security check failed', 'heritagepress'));
        }

        $action = sanitize_text_field($_POST['action']);

        switch ($action) {
            case 'add_tree':
                $this->process_add_tree();
                break;
            case 'update_tree':
                $this->process_update_tree();
                break;
            case 'delete_tree':
                $this->process_delete_tree();
                break;
        }
    }

    /**
     * Render the trees listing page
     */
    private function render_trees_list()
    {
        // Get search parameters
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        // Build query
        $where_clause = '';
        $search_params = [];

        if ($search) {
            $where_clause = " WHERE (gedcom LIKE %s OR title LIKE %s OR description LIKE %s)";
            $search_like = '%' . $this->wpdb->esc_like($search) . '%';
            $search_params = [$search_like, $search_like, $search_like];
        }

        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$this->trees_table}" . $where_clause;
        $total_trees = $this->wpdb->get_var(
            $search_params ? $this->wpdb->prepare($count_query, $search_params) : $count_query
        );

        // Get trees
        $query = "SELECT * FROM {$this->trees_table}" . $where_clause . " ORDER BY title LIMIT %d OFFSET %d";
        $params = array_merge($search_params, [$per_page, $offset]);
        $trees = $this->wpdb->get_results($this->wpdb->prepare($query, $params));

        // Get statistics for each tree
        foreach ($trees as $tree) {
            $tree->people_count = $this->get_people_count($tree->gedcom);
            $tree->families_count = $this->get_families_count($tree->gedcom);
            $tree->sources_count = $this->get_sources_count($tree->gedcom);
        }

        // Calculate pagination
        $total_pages = ceil($total_trees / $per_page);

        // Render the view
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/trees-list.php';
    }

    /**
     * Render the add tree form
     */
    private function render_add_tree()
    {
        // Render the view
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/trees-add.php';
    }

    /**
     * Render the edit tree form
     */
    private function render_edit_tree($tree_id)
    {
        $tree = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->trees_table} WHERE treeID = %d", $tree_id)
        );

        if (!$tree) {
            wp_die(__('Tree not found', 'heritagepress'));
        }

        // Get statistics
        $tree->people_count = $this->get_people_count($tree->gedcom);
        $tree->families_count = $this->get_families_count($tree->gedcom);
        $tree->sources_count = $this->get_sources_count($tree->gedcom);
        $tree->repositories_count = $this->get_repositories_count($tree->gedcom);
        $tree->notes_count = $this->get_notes_count($tree->gedcom);

        // Render the view
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/trees-edit.php';
    }

    /**
     * Process add tree form submission
     */
    private function process_add_tree()
    {
        $data = [
            'gedcom' => sanitize_text_field($_POST['gedcom']),
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'privacy_level' => intval($_POST['privacy_level']),
            'owner_user_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        // Validate required fields
        if (empty($data['gedcom']) || empty($data['title'])) {
            wp_redirect(add_query_arg(['action' => 'add', 'error' => 'required_fields'], admin_url('admin.php?page=heritagepress-trees')));
            exit;
        }

        // Check for duplicate gedcom ID
        $existing = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->trees_table} WHERE gedcom = %s", $data['gedcom'])
        );

        if ($existing > 0) {
            wp_redirect(add_query_arg(['action' => 'add', 'error' => 'duplicate_id'], admin_url('admin.php?page=heritagepress-trees')));
            exit;
        }

        // Insert tree
        $result = $this->wpdb->insert($this->trees_table, $data);

        if ($result) {
            wp_redirect(add_query_arg(['message' => 'tree_added'], admin_url('admin.php?page=heritagepress-trees')));
        } else {
            wp_redirect(add_query_arg(['action' => 'add', 'error' => 'database_error'], admin_url('admin.php?page=heritagepress-trees')));
        }
        exit;
    }

    /**
     * Process update tree form submission
     */
    private function process_update_tree()
    {
        $tree_id = intval($_POST['tree_id']);

        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'privacy_level' => intval($_POST['privacy_level']),
            'updated_at' => current_time('mysql')
        ];

        // Update tree
        $result = $this->wpdb->update(
            $this->trees_table,
            $data,
            ['treeID' => $tree_id],
            ['%s', '%s', '%d', '%s'],
            ['%d']
        );

        if ($result !== false) {
            wp_redirect(add_query_arg(['message' => 'tree_updated'], admin_url('admin.php?page=heritagepress-trees')));
        } else {
            wp_redirect(add_query_arg(['action' => 'edit', 'tree' => $tree_id, 'error' => 'database_error'], admin_url('admin.php?page=heritagepress-trees')));
        }
        exit;
    }

    /**
     * Process delete tree form submission
     */
    private function process_delete_tree()
    {
        $tree_id = intval($_POST['tree_id']);

        // Get tree data
        $tree = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->trees_table} WHERE treeID = %d", $tree_id)
        );

        if (!$tree) {
            wp_redirect(add_query_arg(['error' => 'tree_not_found'], admin_url('admin.php?page=heritagepress-trees')));
            exit;
        }

        // Delete related data first (to maintain referential integrity)
        $this->wpdb->delete($this->people_table, ['gedcom' => $tree->gedcom]);
        $this->wpdb->delete($this->families_table, ['gedcom' => $tree->gedcom]);
        $this->wpdb->delete($this->sources_table, ['gedcom' => $tree->gedcom]);
        $this->wpdb->delete($this->repositories_table, ['gedcom' => $tree->gedcom]);
        $this->wpdb->delete($this->notes_table, ['gedcom' => $tree->gedcom]);

        // Delete tree
        $result = $this->wpdb->delete($this->trees_table, ['treeID' => $tree_id]);

        if ($result) {
            wp_redirect(add_query_arg(['message' => 'tree_deleted'], admin_url('admin.php?page=heritagepress-trees')));
        } else {
            wp_redirect(add_query_arg(['error' => 'delete_failed'], admin_url('admin.php?page=heritagepress-trees')));
        }
        exit;
    }

    /**
     * Handle AJAX requests
     */
    public function handle_ajax_request()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hp_trees_nonce')) {
            wp_die('Security check failed');
        }

        $action = sanitize_text_field($_POST['trees_action']);

        switch ($action) {
            case 'get_tree_stats':
                $this->ajax_get_tree_stats();
                break;
            case 'validate_gedcom_id':
                $this->ajax_validate_gedcom_id();
                break;
        }

        wp_die();
    }

    /**
     * AJAX: Get tree statistics
     */
    private function ajax_get_tree_stats()
    {
        $gedcom = sanitize_text_field($_POST['gedcom']);

        $stats = [
            'people' => $this->get_people_count($gedcom),
            'families' => $this->get_families_count($gedcom),
            'sources' => $this->get_sources_count($gedcom),
            'repositories' => $this->get_repositories_count($gedcom),
            'notes' => $this->get_notes_count($gedcom)
        ];

        wp_send_json_success($stats);
    }

    /**
     * AJAX: Validate GEDCOM ID
     */
    private function ajax_validate_gedcom_id()
    {
        $gedcom = sanitize_text_field($_POST['gedcom']);
        $exclude_id = isset($_POST['exclude_id']) ? intval($_POST['exclude_id']) : 0;

        $where_clause = $exclude_id ?
            $this->wpdb->prepare("WHERE gedcom = %s AND treeID != %d", $gedcom, $exclude_id) :
            $this->wpdb->prepare("WHERE gedcom = %s", $gedcom);

        $exists = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->trees_table} " . $where_clause);

        wp_send_json_success(['available' => ($exists == 0)]);
    }

    /**
     * Get people count for a tree
     */
    private function get_people_count($gedcom)
    {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->people_table} WHERE gedcom = %s", $gedcom)
        );
    }

    /**
     * Get families count for a tree
     */
    private function get_families_count($gedcom)
    {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->families_table} WHERE gedcom = %s", $gedcom)
        );
    }

    /**
     * Get sources count for a tree
     */
    private function get_sources_count($gedcom)
    {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->sources_table} WHERE gedcom = %s", $gedcom)
        );
    }

    /**
     * Get repositories count for a tree
     */
    private function get_repositories_count($gedcom)
    {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->repositories_table} WHERE gedcom = %s", $gedcom)
        );
    }

    /**
     * Get notes count for a tree
     */
    private function get_notes_count($gedcom)
    {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->notes_table} WHERE gedcom = %s", $gedcom)
        );
    }
}
