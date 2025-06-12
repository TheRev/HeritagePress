<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="wrap hp-trees-admin">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups"></span>
        <?php _e('Delete Tree', 'heritagepress'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Messages -->
    <?php if (isset($_GET['error'])): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($this->get_error_text($_GET['error'])); ?></p>
        </div>
    <?php endif; ?>

    <div class="hp-tree-delete-confirmation">
        <div class="hp-delete-warning">
            <span class="dashicons dashicons-warning"></span>
            <h2><?php _e('Delete Tree Confirmation', 'heritagepress'); ?></h2>
            <p><?php _e('You are about to permanently delete the following tree and ALL its associated data:', 'heritagepress'); ?>
            </p>
        </div>

        <div class="hp-tree-details">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Tree ID', 'heritagepress'); ?></th>
                    <td><strong><?php echo esc_html($tree->gedcom); ?></strong></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Title', 'heritagepress'); ?></th>
                    <td><strong><?php echo esc_html($tree->title); ?></strong></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Description', 'heritagepress'); ?></th>
                    <td><?php echo esc_html($tree->description); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Created', 'heritagepress'); ?></th>
                    <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $tree->created_at)); ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="hp-tree-stats">
            <h3><?php _e('Data to be deleted:', 'heritagepress'); ?></h3>
            <div class="hp-stats-grid">
                <div class="hp-stat-item">
                    <span class="dashicons dashicons-groups"></span>
                    <span class="hp-stat-number"><?php echo number_format($tree->people_count); ?></span>
                    <span class="hp-stat-label"><?php _e('Individuals', 'heritagepress'); ?></span>
                </div>
                <div class="hp-stat-item">
                    <span class="dashicons dashicons-heart"></span>
                    <span class="hp-stat-number"><?php echo number_format($tree->families_count); ?></span>
                    <span class="hp-stat-label"><?php _e('Families', 'heritagepress'); ?></span>
                </div>
                <div class="hp-stat-item">
                    <span class="dashicons dashicons-media-document"></span>
                    <span class="hp-stat-number"><?php echo number_format($tree->sources_count); ?></span>
                    <span class="hp-stat-label"><?php _e('Sources', 'heritagepress'); ?></span>
                </div>
                <div class="hp-stat-item">
                    <span class="dashicons dashicons-format-image"></span>
                    <span class="hp-stat-number"><?php echo number_format($tree->media_count); ?></span>
                    <span class="hp-stat-label"><?php _e('Media Files', 'heritagepress'); ?></span>
                </div>
            </div>
        </div>

        <div class="hp-delete-actions">
            <div class="hp-warning-text">
                <p><strong><?php _e('WARNING: This action cannot be undone!', 'heritagepress'); ?></strong></p>
                <p><?php _e('All individuals, families, sources, media, notes, and other data associated with this tree will be permanently deleted.', 'heritagepress'); ?>
                </p>
            </div>

            <form method="post" action="<?php echo admin_url('admin.php?page=heritagepress-trees'); ?>"
                class="hp-delete-form">
                <?php wp_nonce_field('hp_trees_action'); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="tree_id" value="<?php echo esc_attr($tree->treeID); ?>">

                <div class="hp-delete-confirmation-checkbox">
                    <label>
                        <input type="checkbox" name="confirm_delete" value="1" required>
                        <?php _e('I understand that this action cannot be undone', 'heritagepress'); ?>
                    </label>
                </div>

                <div class="hp-form-actions">
                    <a href="<?php echo admin_url('admin.php?page=heritagepress-trees'); ?>"
                        class="button button-secondary">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Cancel', 'heritagepress'); ?>
                    </a>
                    <button type="submit" class="button button-primary hp-delete-button" disabled>
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Delete Tree Permanently', 'heritagepress'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {
        // Enable delete button only when checkbox is checked
        $('input[name="confirm_delete"]').change(function () {
            $('.hp-delete-button').prop('disabled', !this.checked);
        });

        // Double confirmation on submit
        $('.hp-delete-form').submit(function (e) {
            if (!confirm('<?php echo esc_js(__('Are you absolutely sure you want to delete this tree and all its data? This action cannot be undone.', 'heritagepress')); ?>')) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>