<?php
/**
 * Individuals Admin View
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get pagination parameters
$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 20;
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get repository
$container = \HeritagePress\Core\Container::getInstance();
$individual_repo = $container->get('repository.individual');

// Get individuals with pagination
if (!empty($search)) {
    $criteria = ['name' => $search];
    $results = $individual_repo->search($criteria, $per_page, ($page - 1) * $per_page);
    $total_count = $individual_repo->search_count($criteria);
    $individuals = $results;
    $total_pages = ceil($total_count / $per_page);
} else {
    $results = $individual_repo->get_paginated($page, $per_page);
    $individuals = $results['items'];
    $total_count = $results['total'];
    $total_pages = $results['pages'];
}
?>

<div class="wrap heritage-press-admin">
    <h1 class="wp-heading-inline">
        <?php _e('Individuals', 'heritage-press'); ?>
    </h1>
    
    <a href="#" class="page-title-action" id="add-individual-btn">
        <?php _e('Add New Individual', 'heritage-press'); ?>
    </a>
    
    <hr class="wp-header-end">

    <!-- Search Form -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" class="search-form" id="individuals-search-form">
                <input type="hidden" name="page" value="heritage-press-individuals">
                
                <div class="search-box">
                    <input type="search" 
                           id="individual-search-input" 
                           name="search" 
                           value="<?php echo esc_attr($search); ?>" 
                           placeholder="<?php _e('Search individuals...', 'heritage-press'); ?>"
                           class="wp-list-table-search">
                    
                    <button type="submit" class="button">
                        <?php _e('Search', 'heritage-press'); ?>
                    </button>
                    
                    <?php if (!empty($search)): ?>
                        <a href="<?php echo admin_url('admin.php?page=heritage-press-individuals'); ?>" class="button">
                            <?php _e('Clear', 'heritage-press'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Search Results Info -->
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php 
                if (!empty($search)) {
                    printf(_n('%s individual found', '%s individuals found', $total_count, 'heritage-press'), number_format_i18n($total_count));
                } else {
                    printf(_n('%s individual', '%s individuals', $total_count, 'heritage-press'), number_format_i18n($total_count));
                }
                ?>
            </span>
            
            <?php if ($total_pages > 1): ?>
                <?php
                $pagination_args = array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $page,
                    'add_args' => $search ? array('search' => $search) : array()
                );
                echo paginate_links($pagination_args);
                ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Individuals Table -->
    <table class="wp-list-table widefat fixed striped individuals" id="individuals-table">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-name column-primary">
                    <?php _e('Name', 'heritage-press'); ?>
                </th>
                <th scope="col" class="manage-column column-sex">
                    <?php _e('Sex', 'heritage-press'); ?>
                </th>
                <th scope="col" class="manage-column column-birth">
                    <?php _e('Birth', 'heritage-press'); ?>
                </th>
                <th scope="col" class="manage-column column-death">
                    <?php _e('Death', 'heritage-press'); ?>
                </th>
                <th scope="col" class="manage-column column-actions">
                    <?php _e('Actions', 'heritage-press'); ?>
                </th>
            </tr>
        </thead>
        
        <tbody id="individuals-list">
            <?php if (empty($individuals)): ?>
                <tr class="no-items">
                    <td colspan="5" class="colspanchange">
                        <?php _e('No individuals found.', 'heritage-press'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($individuals as $individual): ?>
                    <tr data-individual-id="<?php echo esc_attr($individual->get_id()); ?>">
                        <td class="name column-name column-primary">
                            <strong>
                                <a href="#" class="view-individual" data-individual-id="<?php echo esc_attr($individual->get_id()); ?>">
                                    <?php 
                                    $full_name = trim($individual->get_given_names() . ' ' . $individual->get_surname());
                                    echo esc_html($full_name ?: __('(No name)', 'heritage-press')); 
                                    ?>
                                </a>
                            </strong>
                            
                            <div class="row-actions">
                                <span class="view">
                                    <a href="#" class="view-individual" data-individual-id="<?php echo esc_attr($individual->get_id()); ?>">
                                        <?php _e('View', 'heritage-press'); ?>
                                    </a> |
                                </span>
                                <span class="edit">
                                    <a href="#" class="edit-individual" data-individual-id="<?php echo esc_attr($individual->get_id()); ?>">
                                        <?php _e('Edit', 'heritage-press'); ?>
                                    </a> |
                                </span>
                                <span class="delete">
                                    <a href="#" class="delete-individual" 
                                       data-individual-id="<?php echo esc_attr($individual->get_id()); ?>"
                                       data-confirm="<?php _e('Are you sure you want to delete this individual?', 'heritage-press'); ?>">
                                        <?php _e('Delete', 'heritage-press'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        
                        <td class="sex column-sex">
                            <?php 
                            $sex = $individual->get_sex();
                            if ($sex === 'M') {
                                echo '<span class="sex-male">' . __('Male', 'heritage-press') . '</span>';
                            } elseif ($sex === 'F') {
                                echo '<span class="sex-female">' . __('Female', 'heritage-press') . '</span>';
                            } else {
                                echo '<span class="sex-unknown">' . __('Unknown', 'heritage-press') . '</span>';
                            }
                            ?>
                        </td>
                        
                        <td class="birth column-birth">
                            <?php 
                            $birth_date = $individual->get_birth_date();
                            $birth_place = $individual->get_birth_place();
                            
                            if ($birth_date || $birth_place) {
                                echo '<div class="event-info">';
                                if ($birth_date) {
                                    echo '<div class="event-date">' . esc_html($birth_date) . '</div>';
                                }
                                if ($birth_place) {
                                    echo '<div class="event-place">' . esc_html($birth_place) . '</div>';
                                }
                                echo '</div>';
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        
                        <td class="death column-death">
                            <?php 
                            $death_date = $individual->get_death_date();
                            $death_place = $individual->get_death_place();
                            
                            if ($death_date || $death_place) {
                                echo '<div class="event-info">';
                                if ($death_date) {
                                    echo '<div class="event-date">' . esc_html($death_date) . '</div>';
                                }
                                if ($death_place) {
                                    echo '<div class="event-place">' . esc_html($death_place) . '</div>';
                                }
                                echo '</div>';
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        
                        <td class="actions column-actions">
                            <div class="button-group">
                                <button type="button" class="button button-small view-individual" 
                                        data-individual-id="<?php echo esc_attr($individual->get_id()); ?>">
                                    <?php _e('View', 'heritage-press'); ?>
                                </button>
                                <button type="button" class="button button-small edit-individual" 
                                        data-individual-id="<?php echo esc_attr($individual->get_id()); ?>">
                                    <?php _e('Edit', 'heritage-press'); ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Bottom pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php echo paginate_links($pagination_args); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Individual Modal -->
<div id="individual-modal" class="heritage-modal" style="display: none;">
    <div class="heritage-modal-content">
        <div class="heritage-modal-header">
            <h2 id="individual-modal-title"><?php _e('Individual Details', 'heritage-press'); ?></h2>
            <button type="button" class="heritage-modal-close">&times;</button>
        </div>
        
        <div class="heritage-modal-body">
            <div id="individual-modal-content">
                <!-- Content will be loaded via AJAX -->
                <div class="loading-spinner">
                    <span class="spinner is-active"></span>
                    <span><?php _e('Loading...', 'heritage-press'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="heritage-modal-footer">
            <button type="button" class="button button-primary" id="save-individual" style="display: none;">
                <?php _e('Save Changes', 'heritage-press'); ?>
            </button>
            <button type="button" class="button heritage-modal-close">
                <?php _e('Close', 'heritage-press'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Loading overlay for AJAX operations -->
<div id="heritage-loading-overlay" style="display: none;">
    <div class="heritage-loading-content">
        <span class="spinner is-active"></span>
        <span><?php _e('Processing...', 'heritage-press'); ?></span>
    </div>
</div>