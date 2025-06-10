<?php
namespace HeritagePress\Admin;

/**
 * Handles AJAX request processing for the HeritagePress plugin
 */
class AjaxHandler
{
    /** @var \wpdb */
    private $wpdb;

    /** @var DatabaseOperations */
    private $db_ops;

    public function __construct($wpdb, $db_ops)
    {
        $this->wpdb = $wpdb;
        $this->db_ops = $db_ops;

        // Register AJAX handlers
        add_action('wp_ajax_heritagepress_search_trees', [$this, 'handle_search_trees']);
        add_action('wp_ajax_heritagepress_delete_individual', [$this, 'handle_delete_individual']);
        add_action('wp_ajax_heritagepress_bulk_delete_individuals', [$this, 'handle_bulk_delete_individuals']);
        add_action('wp_ajax_heritagepress_search_individuals', [$this, 'handle_search_individuals']);
    }

    /**
     * Handle tree search AJAX request
     */
    public function handle_search_trees()
    {
        check_ajax_referer('heritagepress_ajax_nonce', 'nonce');

        parse_str($_POST['search_data'], $search_data);
        $search_query = sanitize_text_field($search_data['s'] ?? '');
        $privacy = sanitize_text_field($search_data['privacy'] ?? '');

        $trees = $this->db_ops->get_trees($search_query, $privacy);

        ob_start();
        $this->render_tree_cards($trees);
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'count' => count($trees)
        ]);
    }

    /**
     * Handle individual deletion AJAX request
     */
    public function handle_delete_individual()
    {
        check_ajax_referer('heritagepress_ajax_nonce', 'nonce');

        $individual_id = intval($_POST['id']);
        if (!$individual_id) {
            wp_send_json_error(__('Invalid individual ID.', 'heritagepress'));
        }

        $success = $this->db_ops->delete_individual($individual_id);

        if ($success) {
            wp_send_json_success(__('Individual deleted successfully.', 'heritagepress'));
        } else {
            wp_send_json_error(__('Failed to delete individual.', 'heritagepress'));
        }
    }

    /**
     * Handle bulk individual deletion AJAX request
     */
    public function handle_bulk_delete_individuals()
    {
        check_ajax_referer('heritagepress_ajax_nonce', 'nonce');

        $ids = array_map('intval', (array) $_POST['ids']);
        if (empty($ids)) {
            wp_send_json_error(__('No individuals selected.', 'heritagepress'));
        }

        $deleted = 0;
        foreach ($ids as $id) {
            if ($this->db_ops->delete_individual($id)) {
                $deleted++;
            }
        }

        if ($deleted > 0) {
            wp_send_json_success([
                'message' => sprintf(
                    _n('%d individual deleted.', '%d individuals deleted.', $deleted, 'heritagepress'),
                    $deleted
                )
            ]);
        } else {
            wp_send_json_error(__('Failed to delete individuals.', 'heritagepress'));
        }
    }

    /**
     * Handle individual search AJAX request
     */
    public function handle_search_individuals()
    {
        check_ajax_referer('heritagepress_ajax_nonce', 'nonce');

        $search = sanitize_text_field($_POST['search']);
        if (strlen($search) < 2) {
            wp_send_json_success([]);
        }

        $results = $this->db_ops->search_individuals($search);
        wp_send_json_success($results);
    }
}
