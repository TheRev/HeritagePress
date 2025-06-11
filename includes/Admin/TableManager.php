<?php
/**
 * Table Management for HeritagePress
 *
 * Handles table management operations for HeritagePress database tables
 *
 * @package HeritagePress
 * @subpackage Admin
 */

namespace HeritagePress\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Tabl            } else {
                wp_send_json_success(array(
                    'message' => sprintf(__('Successfully deleted %d HeritagePress tables', 'heritagepress'), $deleted_count)
                ));
            }
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => __('Failed to delete tables: ', 'heritagepress') . $e->getMessage()));
        }
 *
 * Manages HeritagePress database tables including viewing, clearing, and deleting operations
 */
class TableManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Register AJAX handlers for table management
        add_action('wp_ajax_hp_get_table_structure', array($this, 'handle_get_table_structure'));
        add_action('wp_ajax_hp_clear_table', array($this, 'handle_clear_table'));
        add_action('wp_ajax_hp_delete_table', array($this, 'handle_delete_table'));
        add_action('wp_ajax_hp_clear_all_tables', array($this, 'handle_clear_all_tables'));
        add_action('wp_ajax_hp_delete_all_tables', array($this, 'handle_delete_all_tables'));
        add_action('wp_ajax_hp_rebuild_tables', array($this, 'handle_rebuild_tables'));
        add_action('wp_ajax_hp_optimize_tables', array($this, 'handle_optimize_tables'));
    }

    /**
     * Render the main table management page
     */
    public function render_page()
    {
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/templates/tables/tables.php';
    }

    /**
     * Handle get table structure AJAX request
     */
    public function handle_get_table_structure()
    {
        // Check nonce
        check_ajax_referer('hp_table_management', 'nonce');

        $table = sanitize_text_field($_POST['table']);

        if (empty($table)) {
            wp_send_json_error(array('message' => __('Table name is required', 'heritagepress')));
        }
        global $wpdb;

        try {
            // Get table structure
            $columns = $wpdb->get_results("DESCRIBE `{$table}`");            // Get row count - use get_results for mock environment compatibility
            $count = 0;
            try {
                $count_results = $wpdb->get_results("SELECT COUNT(*) as count FROM `{$table}`");
                if (!empty($count_results) && isset($count_results[0]->count)) {
                    $count = intval($count_results[0]->count);
                }
            } catch (\Throwable $e) {
                // In mock/development environment, fallback to 0
                $count = 0;
            }

            wp_send_json_success(array(
                'columns' => $columns,
                'count' => intval($count)
            ));
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => __('Failed to get table structure: ', 'heritagepress') . $e->getMessage()));
        }
    }

    /**
     * Handle clear table AJAX request
     */
    public function handle_clear_table()
    {
        // Check nonce
        check_ajax_referer('hp_table_management', 'nonce');

        $table = sanitize_text_field($_POST['table']);

        if (empty($table)) {
            wp_send_json_error(array('message' => __('Table name is required', 'heritagepress')));
        }

        global $wpdb;

        try {
            // Clear table data
            $result = $wpdb->query("TRUNCATE TABLE `{$table}`");

            if ($result === false) {
                wp_send_json_error(array('message' => __('Failed to clear table: ', 'heritagepress') . $wpdb->last_error));
            } else {
                wp_send_json_success(array('message' => sprintf(__('Successfully cleared table %s', 'heritagepress'), $table)));
            }
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => __('Failed to clear table: ', 'heritagepress') . $e->getMessage()));
        }
    }

    /**
     * Handle delete table AJAX request
     */
    public function handle_delete_table()
    {
        // Check nonce
        check_ajax_referer('hp_table_management', 'nonce');

        $table = sanitize_text_field($_POST['table']);

        if (empty($table)) {
            wp_send_json_error(array('message' => __('Table name is required', 'heritagepress')));
        }

        global $wpdb;

        try {
            // Delete table
            $result = $wpdb->query("DROP TABLE IF EXISTS `{$table}`");

            if ($result === false) {
                wp_send_json_error(array('message' => __('Failed to delete table: ', 'heritagepress') . $wpdb->last_error));
            } else {
                wp_send_json_success(array('message' => sprintf(__('Successfully deleted table %s', 'heritagepress'), $table)));
            }
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => __('Failed to delete table: ', 'heritagepress') . $e->getMessage()));
        }
    }

    /**
     * Handle rebuild tables AJAX request
     */
    public function handle_rebuild_tables()
    {
        // Check nonce
        check_ajax_referer('hp_table_management', 'nonce');

        global $wpdb;

        try {
            // Get all HeritagePress tables
            $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'", ARRAY_N);
            $hp_tables = array_map(function ($table) {
                return $table[0];
            }, $tables);

            $rebuilt_count = 0;
            $errors = [];

            foreach ($hp_tables as $table) {
                // Rebuild table (repair and analyze)
                $result = $wpdb->query("REPAIR TABLE `{$table}`");
                if ($result === false) {
                    $errors[] = $table . ': ' . $wpdb->last_error;
                } else {
                    $rebuilt_count++;
                }
            }

            if (!empty($errors)) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        __('Rebuilt %d tables, but %d had errors: %s', 'heritagepress'),
                        $rebuilt_count,
                        count($errors),
                        implode(', ', $errors)
                    )
                ));
            } else {
                wp_send_json_success(array(
                    'message' => sprintf(__('Successfully rebuilt %d HeritagePress tables', 'heritagepress'), $rebuilt_count)
                ));
            }
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => __('Failed to rebuild tables: ', 'heritagepress') . $e->getMessage()));
        }
    }

    /**
     * Handle optimize tables AJAX request
     */
    public function handle_optimize_tables()
    {
        // Check nonce
        check_ajax_referer('hp_table_management', 'nonce');

        global $wpdb;

        try {
            // Get all HeritagePress tables
            $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'", ARRAY_N);
            $hp_tables = array_map(function ($table) {
                return $table[0];
            }, $tables);

            $optimized_count = 0;
            $errors = [];

            foreach ($hp_tables as $table) {
                // Optimize table
                $result = $wpdb->query("OPTIMIZE TABLE `{$table}`");
                if ($result === false) {
                    $errors[] = $table . ': ' . $wpdb->last_error;
                } else {
                    $optimized_count++;
                }
            }

            if (!empty($errors)) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        __('Optimized %d tables, but %d had errors: %s', 'heritagepress'),
                        $optimized_count,
                        count($errors),
                        implode(', ', $errors)
                    )
                ));
            } else {
                wp_send_json_success(array(
                    'message' => sprintf(__('Successfully optimized %d HeritagePress tables', 'heritagepress'), $optimized_count)
                ));
            }
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => __('Failed to optimize tables: ', 'heritagepress') . $e->getMessage()));
        }
    }

    /**
     * Handle clear all tables AJAX request
     */
    public function handle_clear_all_tables()
    {
        // Check nonce
        check_ajax_referer('hp_table_management', 'nonce');

        global $wpdb;

        try {
            // Get all HeritagePress tables
            $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'", ARRAY_N);
            $hp_tables = array_map(function ($table) {
                return $table[0];
            }, $tables);

            $cleared_count = 0;
            $errors = [];

            foreach ($hp_tables as $table) {
                // Clear table data
                $result = $wpdb->query("TRUNCATE TABLE `{$table}`");
                if ($result === false) {
                    $errors[] = $table . ': ' . $wpdb->last_error;
                } else {
                    $cleared_count++;
                }
            }

            if (!empty($errors)) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        __('Cleared %d tables, but %d had errors: %s', 'heritagepress'),
                        $cleared_count,
                        count($errors),
                        implode(', ', $errors)
                    )
                ));
            } else {
                wp_send_json_success(array(
                    'message' => sprintf(__('Successfully cleared all data from %d HeritagePress tables', 'heritagepress'), $cleared_count)
                ));
            }
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => __('Failed to clear tables: ', 'heritagepress') . $e->getMessage()));
        }
    }

    /**
     * Handle delete all tables AJAX request
     */
    public function handle_delete_all_tables()
    {
        // Check nonce
        check_ajax_referer('hp_table_management', 'nonce');

        global $wpdb;

        try {
            // Get all HeritagePress tables
            $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'", ARRAY_N);
            $hp_tables = array_map(function ($table) {
                return $table[0];
            }, $tables);

            $deleted_count = 0;
            $errors = [];

            foreach ($hp_tables as $table) {
                // Delete table
                $result = $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
                if ($result === false) {
                    $errors[] = $table . ': ' . $wpdb->last_error;
                } else {
                    $deleted_count++;
                }
            }

            if (!empty($errors)) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        __('Deleted %d tables, but %d had errors: %s', 'heritagepress'),
                        $deleted_count,
                        count($errors),
                        implode(', ', $errors)
                    )
                ));
            } else {
                wp_send_json_success(array(
                    'message' => sprintf(__('Successfully deleted %d HeritagePress tables', 'heritagepress'), $deleted_count)
                ));
            }
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => __('Failed to delete tables: ', 'heritagepress') . $e->getMessage()));
        }
    }
}
