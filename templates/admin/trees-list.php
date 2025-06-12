<?php
/**
 * Trees List Template for HeritagePress
 * Displays a listing of all genealogy trees with management options
 * Based on TNG admin_trees.php structure with WordPress styling
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Extract variables passed from TreesManager
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
$error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
?>

<div class="wrap hp-trees-wrap">
    <!-- Page Header -->
    <div class="hp-trees-header">
        <h1 class="hp-trees-title">
            <span class="dashicons dashicons-networking"></span>
            <?php _e('Trees Management', 'heritagepress'); ?>
        </h1>
        <div class="hp-trees-actions">
            <a href="<?php echo admin_url('admin.php?page=heritagepress-trees&action=add'); ?>"
                class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Add New Tree', 'heritagepress'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=heritagepress-importexport'); ?>" class="button">
                <span class="dashicons dashicons-upload"></span>
                <?php _e('Import GEDCOM', 'heritagepress'); ?>
            </a>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo $this->get_message_text($message); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo $this->get_error_text($error); ?></p>
        </div>
    <?php endif; ?>

    <!-- Search and Filters -->
    <div class="hp-trees-search-section">
        <form method="get" id="hp-trees-search-form" class="hp-search-form">
            <input type="hidden" name="page" value="heritagepress-trees">
            <input type="hidden" name="action" value="list">

            <div class="search-box">
                <label class="screen-reader-text"
                    for="tree-search-input"><?php _e('Search Trees', 'heritagepress'); ?></label>
                <input type="search" id="tree-search-input" name="search" value="<?php echo esc_attr($search); ?>"
                    placeholder="<?php _e('Search trees...', 'heritagepress'); ?>">
                <input type="submit" id="search-submit" class="button"
                    value="<?php _e('Search Trees', 'heritagepress'); ?>">
            </div>
        </form>
    </div>

    <!-- Trees Table -->
    <div class="hp-trees-table-wrapper">
        <?php if (empty($trees)): ?>
            <!-- Empty State -->
            <div class="hp-trees-empty-state">
                <div class="hp-empty-state-icon">
                    <span class="dashicons dashicons-networking"></span>
                </div>
                <h3><?php _e('No Trees Found', 'heritagepress'); ?></h3>
                <?php if ($search): ?>
                    <p><?php printf(__('No trees match your search for "%s".', 'heritagepress'), esc_html($search)); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=heritagepress-trees'); ?>" class="button">
                        <?php _e('Clear Search', 'heritagepress'); ?>
                    </a>
                <?php else: ?>
                    <p><?php _e('You haven\'t created any genealogy trees yet.', 'heritagepress'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=heritagepress-trees&action=add'); ?>"
                        class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Create Your First Tree', 'heritagepress'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Trees Table -->
            <table class="wp-list-table widefat fixed striped hp-trees-table">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-title column-primary">
                            <?php _e('Tree Name', 'heritagepress'); ?>
                        </th>
                        <th scope="col" class="manage-column column-gedcom">
                            <?php _e('Tree ID', 'heritagepress'); ?>
                        </th>
                        <th scope="col" class="manage-column column-stats">
                            <?php _e('Statistics', 'heritagepress'); ?>
                        </th>
                        <th scope="col" class="manage-column column-privacy">
                            <?php _e('Privacy', 'heritagepress'); ?>
                        </th>
                        <th scope="col" class="manage-column column-owner">
                            <?php _e('Owner', 'heritagepress'); ?>
                        </th>
                        <th scope="col" class="manage-column column-date">
                            <?php _e('Last Updated', 'heritagepress'); ?>
                        </th>
                        <th scope="col" class="manage-column column-actions">
                            <?php _e('Actions', 'heritagepress'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trees as $tree): ?>
                        <tr>
                            <!-- Tree Name (Primary Column) -->
                            <td class="title column-title has-row-actions column-primary"
                                data-colname="<?php _e('Tree Name', 'heritagepress'); ?>">
                                <strong>
                                    <a href="<?php echo admin_url('admin.php?page=heritagepress-trees&action=edit&tree=' . $tree->treeID); ?>"
                                        class="row-title">
                                        <?php echo esc_html($tree->title); ?>
                                    </a>
                                </strong>
                                <?php if ($tree->description): ?>
                                    <p class="description"><?php echo esc_html(wp_trim_words($tree->description, 15)); ?></p>
                                <?php endif; ?>

                                <!-- Row Actions -->
                                <div class="row-actions">
                                    <span class="edit">
                                        <a
                                            href="<?php echo admin_url('admin.php?page=heritagepress-trees&action=edit&tree=' . $tree->treeID); ?>">
                                            <?php _e('Edit', 'heritagepress'); ?>
                                        </a> |
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo home_url('/genealogy/tree/' . $tree->gedcom); ?>" target="_blank">
                                            <?php _e('View', 'heritagepress'); ?>
                                        </a> |
                                    </span>
                                    <span class="export">
                                        <a
                                            href="<?php echo admin_url('admin.php?page=heritagepress-importexport&action=export&tree=' . $tree->treeID); ?>">
                                            <?php _e('Export', 'heritagepress'); ?>
                                        </a> |
                                    </span>
                                    <span class="delete">
                                        <a href="#" class="hp-delete-tree submitdelete"
                                            data-tree-id="<?php echo $tree->treeID; ?>"
                                            data-tree-name="<?php echo esc_attr($tree->title); ?>">
                                            <?php _e('Delete', 'heritagepress'); ?>
                                        </a>
                                    </span>
                                </div>

                                <button type="button" class="toggle-row">
                                    <span class="screen-reader-text"><?php _e('Show more details', 'heritagepress'); ?></span>
                                </button>
                            </td>

                            <!-- Tree ID -->
                            <td class="gedcom column-gedcom" data-colname="<?php _e('Tree ID', 'heritagepress'); ?>">
                                <code class="hp-tree-id"><?php echo esc_html($tree->gedcom); ?></code>
                            </td>

                            <!-- Statistics -->
                            <td class="stats column-stats" data-colname="<?php _e('Statistics', 'heritagepress'); ?>">
                                <div class="hp-tree-stats">
                                    <div class="stat-item">
                                        <span class="dashicons dashicons-groups"></span>
                                        <span class="stat-number"><?php echo number_format($tree->people_count); ?></span>
                                        <span class="stat-label"><?php _e('People', 'heritagepress'); ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="dashicons dashicons-heart"></span>
                                        <span class="stat-number"><?php echo number_format($tree->families_count); ?></span>
                                        <span class="stat-label"><?php _e('Families', 'heritagepress'); ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="dashicons dashicons-media-document"></span>
                                        <span class="stat-number"><?php echo number_format($tree->sources_count); ?></span>
                                        <span class="stat-label"><?php _e('Sources', 'heritagepress'); ?></span>
                                    </div>
                                    <?php if (isset($tree->media_count)): ?>
                                        <div class="stat-item">
                                            <span class="dashicons dashicons-format-image"></span>
                                            <span class="stat-number"><?php echo number_format($tree->media_count); ?></span>
                                            <span class="stat-label"><?php _e('Media', 'heritagepress'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Privacy Level -->
                            <td class="privacy column-privacy" data-colname="<?php _e('Privacy', 'heritagepress'); ?>">
                                <?php
                                $privacy_levels = [
                                    0 => __('Public', 'heritagepress'),
                                    1 => __('Private', 'heritagepress'),
                                    2 => __('Living Only', 'heritagepress'),
                                    3 => __('Members Only', 'heritagepress')
                                ];
                                $privacy_class = $tree->privacy_level == 0 ? 'public' : 'private';
                                ?>
                                <span class="hp-privacy-badge privacy-<?php echo $privacy_class; ?>">
                                    <?php echo $privacy_levels[$tree->privacy_level] ?? __('Unknown', 'heritagepress'); ?>
                                </span>
                            </td>

                            <!-- Owner -->
                            <td class="owner column-owner" data-colname="<?php _e('Owner', 'heritagepress'); ?>">
                                <?php if ($tree->owner_user_id): ?>
                                    <?php $owner = get_userdata($tree->owner_user_id); ?>
                                    <?php if ($owner): ?>
                                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $tree->owner_user_id); ?>">
                                            <?php echo esc_html($owner->display_name); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="hp-unknown-owner"><?php _e('Unknown User', 'heritagepress'); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="hp-no-owner"><?php _e('No Owner', 'heritagepress'); ?></span>
                                <?php endif; ?>
                            </td>

                            <!-- Last Updated -->
                            <td class="date column-date" data-colname="<?php _e('Last Updated', 'heritagepress'); ?>">
                                <?php
                                $updated_time = strtotime($tree->updated_at);
                                if ($updated_time):
                                    ?>
                                    <abbr title="<?php echo esc_attr(date_i18n('F j, Y g:i a', $updated_time)); ?>">
                                        <?php echo human_time_diff($updated_time, current_time('timestamp')) . ' ' . __('ago', 'heritagepress'); ?>
                                    </abbr>
                                <?php else: ?>
                                    <span class="hp-no-date"><?php _e('Unknown', 'heritagepress'); ?></span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td class="actions column-actions" data-colname="<?php _e('Actions', 'heritagepress'); ?>">
                                <div class="hp-action-buttons">
                                    <a href="<?php echo admin_url('admin.php?page=heritagepress-trees&action=edit&tree=' . $tree->treeID); ?>"
                                        class="button button-small" title="<?php _e('Edit Tree', 'heritagepress'); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <a href="<?php echo home_url('/genealogy/tree/' . $tree->gedcom); ?>"
                                        class="button button-small" title="<?php _e('View Tree', 'heritagepress'); ?>"
                                        target="_blank">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=heritagepress-importexport&action=export&tree=' . $tree->treeID); ?>"
                                        class="button button-small" title="<?php _e('Export Tree', 'heritagepress'); ?>">
                                        <span class="dashicons dashicons-download"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="hp-trees-pagination">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo; Previous'),
                        'next_text' => __('Next &raquo;'),
                        'total' => $total_pages,
                        'current' => $page,
                        'type' => 'list'
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Summary Information -->
    <div class="hp-trees-summary">
        <p class="hp-summary-text">
            <?php
            if (!empty($trees)) {
                printf(
                    _n(
                        'Displaying %1$s tree (of %2$s total)',
                        'Displaying %1$s trees (of %2$s total)',
                        count($trees),
                        'heritagepress'
                    ),
                    '<strong>' . number_format(count($trees)) . '</strong>',
                    '<strong>' . number_format($total_trees) . '</strong>'
                );
            }
            ?>
        </p>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="hp-delete-tree-modal" class="hp-modal" style="display: none;">
    <div class="hp-modal-content">
        <div class="hp-modal-header">
            <h3><?php _e('Delete Tree', 'heritagepress'); ?></h3>
            <button type="button" class="hp-modal-close">&times;</button>
        </div>
        <div class="hp-modal-body">
            <p><?php _e('Are you sure you want to delete this tree?', 'heritagepress'); ?></p>
            <p><strong id="hp-delete-tree-name"></strong></p>
            <p class="hp-warning">
                <span class="dashicons dashicons-warning"></span>
                <?php _e('This action cannot be undone. All associated data (people, families, sources, media) will be permanently deleted.', 'heritagepress'); ?>
            </p>
        </div>
        <div class="hp-modal-footer">
            <form method="post" id="hp-delete-tree-form">
                <?php wp_nonce_field('hp_trees_action'); ?>
                <input type="hidden" name="action" value="delete_tree">
                <input type="hidden" name="tree_id" id="hp-delete-tree-id">
                <button type="button" class="button hp-modal-cancel"><?php _e('Cancel', 'heritagepress'); ?></button>
                <button type="submit"
                    class="button button-primary hp-delete-confirm"><?php _e('Delete Tree', 'heritagepress'); ?></button>
            </form>
        </div>
    </div>
</div>