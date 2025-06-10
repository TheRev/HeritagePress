<?php
namespace HeritagePress\Admin;

use HeritagePress\Database\Manager as SchemaManager;
use HeritagePress\Database\WPHelper;

/**
 * Handles form submissions and data processing for admin pages
 */
class FormHandler
{
    /** @var SchemaManager */
    private $db_manager;

    /**
     * @param SchemaManager $db_manager Database schema manager instance
     */
    public function __construct(SchemaManager $db_manager)
    {
        $this->db_manager = $db_manager;

        // Register form submission handler
        WPHelper::addAction('admin_init', [$this, 'handle_submissions']);
    }

    /**
     * Handle all form submissions
     */
    public function handle_submissions()
    {
        if (!isset($_GET['page']) || !isset($_POST['action'])) {
            return;
        }

        $page = WPHelper::sanitizeTextField($_GET['page']);
        $action = WPHelper::sanitizeTextField($_POST['action']);

        switch ($page) {
            case 'heritagepress-individuals':
                $this->handle_individual_actions($action);
                break;
            case 'heritagepress-trees':
                $this->handle_tree_actions($action);
                break;
            case 'heritagepress-settings':
                if ($action === 'save') {
                    $this->handle_settings_save();
                }
                break;
        }
    }

    /**
     * Handle individual-related form submissions
     */
    private function handle_individual_actions($action)
    {
        switch ($action) {
            case 'create':
                $this->handle_create_individual();
                break;
            case 'update':
                $this->handle_update_individual();
                break;
            case 'merge':
                $this->handle_merge_individuals();
                break;
        }
    }

    /**
     * Handle tree-related form submissions
     */
    private function handle_tree_actions($action)
    {
        switch ($action) {
            case 'create':
                $this->handle_create_tree();
                break;
            case 'update':
                $this->handle_update_tree();
                break;
            case 'delete':
                $tree_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                if ($tree_id) {
                    $this->handle_delete_tree($tree_id);
                }
                break;
        }
    }

    /**
     * Handle individual creation submission
     */
    public function handle_create_individual()
    {
        WPHelper::checkAdminReferer('heritagepress-create-individual');

        $individual_data = [
            'tree_id' => intval($_POST['tree_id']),
            'sex' => WPHelper::sanitizeTextField($_POST['sex']),
            'living' => isset($_POST['is_living']),
            'created_by' => WPHelper::getCurrentUserId(),
            'updated_by' => WPHelper::getCurrentUserId()
        ];

        $individual_id = $this->db_manager->create_individual($individual_data);

        if (!$individual_id) {
            WPHelper::redirect(WPHelper::adminUrl('admin.php?page=heritagepress-individuals&error=create_failed'));
            exit;
        }

        $name_data = [
            'individual_id' => $individual_id,
            'given_names' => WPHelper::sanitizeTextField($_POST['given_names']),
            'surname' => WPHelper::sanitizeTextField($_POST['surname']),
            'is_primary' => true
        ];

        $this->db_manager->create_name($name_data);

        WPHelper::redirect(WPHelper::adminUrl('admin.php?page=heritagepress-individuals&id=' . $individual_id . '&tab=edit'));
        exit;
    }

    /** 
     * Handle individual update submission
     */
    public function handle_update_individual()
    {
        WPHelper::checkAdminReferer('heritagepress-update-individual');

        $individual_id = intval($_POST['individual_id']);
        if (!$individual_id) {
            WPHelper::redirect(WPHelper::adminUrl('admin.php?page=heritagepress-individuals&error=invalid_id'));
            exit;
        }

        $individual_data = [
            'sex' => WPHelper::sanitizeTextField($_POST['sex']),
            'living' => isset($_POST['is_living']),
            'updated_by' => WPHelper::getCurrentUserId()
        ];

        $success = $this->db_manager->update_individual($individual_id, $individual_data);

        if ($success && isset($_POST['surname'], $_POST['given_names'])) {
            $name_data = [
                'given_names' => WPHelper::sanitizeTextField($_POST['given_names']),
                'surname' => WPHelper::sanitizeTextField($_POST['surname'])
            ];

            $this->db_manager->update_name($individual_id, $name_data);
        }

        WPHelper::redirect(WPHelper::adminUrl('admin.php?page=heritagepress-individuals&id=' . $individual_id . '&message=' . ($success ? 'updated' : 'update_failed')));
        exit;
    }

    /**
     * Handle settings page form submission
     */
    public function handle_settings_save()
    {
        WPHelper::checkAdminReferer('heritagepress-settings');

        // Update settings
        WPHelper::updateOption('heritagepress_default_date_format', WPHelper::sanitizeTextField($_POST['default_date_format']));
        WPHelper::updateOption('heritagepress_default_privacy', isset($_POST['default_public']));
        WPHelper::updateOption('heritagepress_enable_gedcom', isset($_POST['enable_gedcom']));
        WPHelper::updateOption('heritagepress_enable_dna', isset($_POST['enable_dna']));

        WPHelper::redirect(WPHelper::adminUrl('admin.php?page=heritagepress-settings&message=saved'));
        exit;
    }

    /**
     * Handle individual merge submission 
     */
    public function handle_merge_individuals()
    {
        WPHelper::checkAdminReferer('heritagepress-merge-individuals');

        $source_id = intval($_POST['source_id']);
        $target_id = intval($_POST['target_id']);

        if (!$source_id || !$target_id) {
            WPHelper::redirect(WPHelper::adminUrl('admin.php?page=heritagepress-individuals&tab=merge&error=invalid_ids'));
            exit;
        }

        $success = $this->db_manager->merge_individuals($source_id, $target_id);

        WPHelper::redirect(WPHelper::adminUrl('admin.php?page=heritagepress-individuals&id=' . $target_id . '&message=' . ($success ? 'merged' : 'merge_failed')));
        exit;
    }

    /**
     * Handle tree update submission
     */
    public function handle_update_tree()
    {
        WPHelper::checkAdminReferer('heritagepress-update-tree');

        $tree_id = intval($_POST['tree_id']);
        if (!$tree_id) {
            WPHelper::redirect(WPHelper::adminUrl('admin.php?page=heritagepress-trees&error=invalid_id'));
            exit;
        }

        // Update tree data
        $tree_data = [
            'name' => WPHelper::sanitizeTextField($_POST['tree_name']),
            'description' => WPHelper::sanitizeTextareaField($_POST['tree_description'] ?? ''),
            'is_public' => isset($_POST['tree_public']),
            'date_format' => WPHelper::sanitizeTextField($_POST['tree_date_format'] ?? 'YMD'),
            'updated_by' => WPHelper::getCurrentUserId()
        ];

        $success = $this->db_manager->update_tree($tree_id, $tree_data);

        WPHelper::redirect(WPHelper::adminUrl('admin.php?page=heritagepress-trees&id=' . $tree_id . '&message=' . ($success ? 'updated' : 'update_failed')));
        exit;
    }

    /**
     * Handle tree creation submission
     */
    public function handle_create_tree()
    {
        WPHelper::checkAdminReferer('heritagepress-create-tree');

        $tree_data = [
            'name' => WPHelper::sanitizeTextField($_POST['tree_name']),
            'description' => WPHelper::sanitizeTextareaField($_POST['tree_description'] ?? ''),
            'is_public' => isset($_POST['tree_public']),
            'date_format' => WPHelper::sanitizeTextField($_POST['tree_date_format'] ?? 'YMD'),
            'created_by' => WPHelper::getCurrentUserId(),
            'updated_by' => WPHelper::getCurrentUserId()
        ];

        $tree_id = $this->db_manager->create_tree($tree_data);

        if ($tree_id) {
            WPHelper::redirect(WPHelper::adminUrl('admin.php?page=heritagepress-trees&message=created'));
            exit;
        }

        WPHelper::redirect(WPHelper::adminUrl('admin.php?page=heritagepress-trees&error=create_failed'));
        exit;
    }

    /** 
     * Handle tree deletion
     *
     * @param int $tree_id Tree ID to delete
     */
    public function handle_delete_tree($tree_id)
    {
        if (!$tree_id) {
            WPHelper::redirect(WPHelper::adminUrl('admin.php?page=heritagepress-trees&error=invalid_id'));
            exit;
        }

        WPHelper::checkAdminReferer('heritagepress-delete-tree');

        $success = $this->db_manager->delete_tree($tree_id);

        WPHelper::redirect(WPHelper::adminUrl('admin.php?page=heritagepress-trees&message=' . ($success ? 'deleted' : 'delete_failed')));
        exit;
    }
}
