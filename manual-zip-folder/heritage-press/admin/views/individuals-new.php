<?php
/**
 * Individuals Management Page
 *
 * @package HeritagePress
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1>
        <?php _e('Individuals Management', 'heritage-press'); ?>
        <button type="button" class="page-title-action add-new-individual">
            <?php _e('Add New Individual', 'heritage-press'); ?>
        </button>
    </h1>

    <!-- Search Form -->
    <div class="search-container">
        <form class="search-form" data-search-type="individuals">
            <div class="search-input-wrapper">
                <input type="search" name="search" placeholder="<?php esc_attr_e('Search individuals by name...', 'heritage-press'); ?>" class="regular-text">
                <button type="submit" class="button">
                    <?php _e('Search', 'heritage-press'); ?>
                </button>
                <button type="button" class="button clear-search" style="display: none;">
                    <?php _e('Clear', 'heritage-press'); ?>
                </button>
                <span class="spinner"></span>
            </div>
        </form>
    </div>

    <!-- Search Results / Individuals List -->
    <div class="individuals-container">
        <div class="individuals-list">
            <!-- Dynamic content will be loaded here via AJAX -->
            <div class="loading-placeholder">
                <p><?php _e('Loading individuals...', 'heritage-press'); ?></p>
                <span class="spinner is-active"></span>
            </div>
        </div>
        
        <!-- Pagination will be inserted here if needed -->
        <div class="pagination-container"></div>
    </div>

    <!-- Quick Actions -->
    <div class="heritage-quick-actions">
        <h3><?php _e('Quick Actions', 'heritage-press'); ?></h3>
        <div class="action-buttons">
            <button type="button" class="button add-new-individual">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Add New Individual', 'heritage-press'); ?>
            </button>
            <button type="button" class="button heritage-bulk-import">
                <span class="dashicons dashicons-upload"></span>
                <?php _e('Import from GEDCOM', 'heritage-press'); ?>
            </button>
            <button type="button" class="button heritage-export-data">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export Data', 'heritage-press'); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Load initial individuals list
    if (typeof performSearch === 'function') {
        performSearch($('.search-form'), 'individuals');
    }
    
    // Handle bulk import button
    $('.heritage-bulk-import').on('click', function() {
        window.location.href = '<?php echo admin_url('admin.php?page=heritage-press-import'); ?>';
    });
    
    // Handle export button (placeholder)
    $('.heritage-export-data').on('click', function() {
        alert('<?php esc_js_e('Export functionality coming soon!', 'heritage-press'); ?>');
    });
});
</script>

<style>
.search-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.search-input-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.search-input-wrapper input[type="search"] {
    flex: 1;
    min-width: 300px;
}

.individuals-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
    min-height: 400px;
}

.loading-placeholder {
    text-align: center;
    padding: 40px 20px;
    color: #646970;
}

.loading-placeholder .spinner {
    float: none;
    margin: 10px auto 0;
    display: block;
}

.heritage-quick-actions {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
}

.heritage-quick-actions h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #1d2327;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.action-buttons .button {
    display: flex;
    align-items: center;
    gap: 8px;
}

.action-buttons .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

@media (max-width: 768px) {
    .search-input-wrapper {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-input-wrapper input[type="search"] {
        min-width: auto;
        width: 100%;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>
