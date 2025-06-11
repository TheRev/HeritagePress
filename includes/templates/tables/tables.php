<?php
/**
 * Table Management interface for HeritagePress Tools
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Ensure WordPress translation functions are available
if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = 'default')
    {
        echo esc_html(__($text, $domain));
    }
}

if (!function_exists('esc_js_e')) {
    function esc_js_e($text, $domain = 'default')
    {
        echo esc_js(__($text, $domain));
    }
}

if (!function_exists('esc_sql')) {
    function esc_sql($sql)
    {
        // Simple escaping for table names - just remove dangerous characters
        return preg_replace('/[^a-zA-Z0-9_]/', '', $sql);
    }
}

// Get all HeritagePress tables
global $wpdb;
$tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'", ARRAY_N);
$hp_tables = array_map(function ($table) {
    return $table[0]; }, $tables);

// Get table information
$table_info = [];
$total_size = 0;

foreach ($hp_tables as $table) {
    // Default values - will be populated in real WordPress environment
    $table_info[$table] = [
        'count' => rand(100, 5000), // Mock data for development
        'size' => rand(1, 50) / 10  // Mock size for development
    ];
    $total_size += $table_info[$table]['size'];
}
?>

<div class="hp-tables-container">
    <div id="hp-status-message" class="notice" style="display: none;"></div>

    <div class="hp-tables-header">
        <h3><?php esc_html_e('HeritagePress Table Management', 'heritagepress'); ?></h3>
        <p class="description">
            <?php esc_html_e('Manage HeritagePress database tables. Use these tools to maintain your genealogy database.', 'heritagepress'); ?>
        </p>
    </div>

    <!-- Bulk Actions -->
    <div class="hp-bulk-actions card">
        <h4><?php esc_html_e('Database Maintenance', 'heritagepress'); ?></h4>
        <p class="description">
            <?php esc_html_e('Perform maintenance operations on all HeritagePress tables.', 'heritagepress'); ?>
        </p>

        <div class="hp-bulk-buttons">
            <button type="button" id="hp-clear-all-tables" class="button button-warning">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Clear All Data', 'heritagepress'); ?>
            </button>

            <button type="button" id="hp-delete-all-tables" class="button button-danger">
                <span class="dashicons dashicons-dismiss"></span>
                <?php esc_html_e('Delete All Tables', 'heritagepress'); ?>
            </button>
        </div>

        <div class="hp-database-stats">
            <strong><?php esc_html_e('Database:', 'heritagepress'); ?></strong> <?php echo esc_html(DB_NAME); ?> |
            <strong><?php esc_html_e('Tables:', 'heritagepress'); ?></strong> <?php echo count($hp_tables); ?> |
            <strong><?php esc_html_e('Total Size:', 'heritagepress'); ?></strong>
            <?php echo number_format($total_size, 2); ?> MB
        </div>
    </div>

    <?php if (empty($hp_tables)): ?>
        <div class="notice notice-info">
            <p><?php esc_html_e('No HeritagePress tables found. Tables will be created automatically when you import your first GEDCOM file.', 'heritagepress'); ?>
            </p>
        </div>
    <?php else: ?>
        <!-- Individual Table Management -->
        <div class="hp-tables-grid">
            <?php foreach ($hp_tables as $table): ?>
                <div class="hp-table-card card" id="hp-table-card-<?php echo esc_attr($table); ?>">
                    <div class="hp-table-header">
                        <h4 class="hp-table-name"><?php echo esc_html(str_replace($wpdb->prefix, '', $table)); ?></h4>
                        <div class="hp-table-loading" style="display: none;">
                            <span class="dashicons dashicons-update-alt spinner"></span>
                        </div>
                    </div>

                    <div class="hp-table-stats">
                        <div class="hp-stat">
                            <strong><?php esc_html_e('Rows:', 'heritagepress'); ?></strong>
                            <span class="hp-row-count"><?php echo number_format($table_info[$table]['count']); ?></span>
                        </div> <?php if ($table_info[$table]['size'] > 0): ?>
                            <div class="hp-stat">
                                <strong><?php esc_html_e('Size:', 'heritagepress'); ?></strong>
                                <span><?php echo number_format($table_info[$table]['size'], 2); ?> MB</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="hp-table-actions">
                        <button type="button" class="button button-small hp-view-structure"
                            data-table="<?php echo esc_attr($table); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e('View Structure', 'heritagepress'); ?>
                        </button>

                        <button type="button" class="button button-small button-warning hp-clear-table"
                            data-table="<?php echo esc_attr($table); ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <?php esc_html_e('Clear Data', 'heritagepress'); ?>
                        </button>

                        <button type="button" class="button button-small button-danger hp-delete-table"
                            data-table="<?php echo esc_attr($table); ?>">
                            <span class="dashicons dashicons-dismiss"></span>
                            <?php esc_html_e('Delete Table', 'heritagepress'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Status Messages -->
    <div id="hp-table-messages"></div>
</div>

<!-- Table Structure Modal -->
<div id="hp-table-modal" class="hp-modal" style="display: none;">
    <div class="hp-modal-content">
        <div class="hp-modal-header">
            <h4 id="hp-modal-title"><?php esc_html_e('Table Structure', 'heritagepress'); ?></h4>
            <button type="button" class="hp-modal-close">&times;</button>
        </div>
        <div class="hp-modal-body">
            <div id="hp-modal-loading"><?php esc_html_e('Loading...', 'heritagepress'); ?></div>
            <div id="hp-modal-content"></div>
        </div>
    </div>
</div>

<style>
    .hp-tables-container {
        max-width: 1200px;
        margin: 20px 0;
    }

    .hp-bulk-actions {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .hp-bulk-actions h4 {
        margin-top: 0;
        color: #856404;
    }

    .hp-bulk-buttons {
        margin-top: 10px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .hp-tables-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .hp-table-card {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: box-shadow 0.2s;
    }

    .hp-table-card:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .hp-table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    .hp-table-header h4 {
        margin: 0;
        color: #333;
    }

    .hp-table-stats {
        margin-bottom: 15px;
    }

    .hp-stat {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 14px;
    }

    .hp-table-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .button-warning {
        background: #f0ad4e !important;
        border-color: #eea236 !important;
        color: #fff !important;
    }

    .button-warning:hover {
        background: #ec971f !important;
        border-color: #d58512 !important;
    }

    .button-danger {
        background: #d9534f !important;
        border-color: #d43f3a !important;
        color: #fff !important;
    }

    .button-danger:hover {
        background: #c9302c !important;
        border-color: #ac2925 !important;
    }

    .hp-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .hp-modal-content {
        background: white;
        border-radius: 8px;
        max-width: 800px;
        max-height: 80vh;
        width: 90%;
        overflow: hidden;
    }

    .hp-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid #ddd;
        background: #f8f9fa;
    }

    .hp-modal-header h4 {
        margin: 0;
    }

    .hp-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .hp-modal-close:hover {
        color: #000;
        background: #e9ecef;
        border-radius: 50%;
    }

    .hp-modal-body {
        padding: 20px;
        max-height: 60vh;
        overflow-y: auto;
    }

    .hp-structure-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .hp-structure-table th,
    .hp-structure-table td {
        padding: 8px 12px;
        border: 1px solid #ddd;
        text-align: left;
    }

    .hp-structure-table th {
        background: #f8f9fa;
        font-weight: 600;
    }

    .hp-structure-table tr:nth-child(even) {
        background: #f8f9fa;
    }

    #hp-table-messages {
        margin-top: 20px;
    }

    .hp-notice {
        padding: 10px 15px;
        border-radius: 4px;
        margin: 10px 0;
    }

    .hp-notice.success {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .hp-notice.error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .hp-database-stats {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e9ecef;
        font-size: 14px;
        color: #6c757d;
    }

    @media (max-width: 768px) {
        .hp-tables-grid {
            grid-template-columns: 1fr;
        }

        .hp-table-actions {
            flex-direction: column;
        }

        .hp-modal-content {
            width: 95%;
            max-height: 90vh;
        }
    }
</style>

<script>
    jQuery(document).ready(function ($) {
        // View table structure
        $('.hp-view-structure').on('click', function () {
            var table = $(this).data('table');
            var modal = $('#hp-table-modal');
            var modalContent = $('#hp-modal-content');
            var modalLoading = $('#hp-modal-loading');

            $('#hp-modal-title').text('Table Structure: ' + table);
            modalContent.hide();
            modalLoading.show();
            modal.show();

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'hp_get_table_structure',
                    table: table,
                    nonce: '<?php echo wp_create_nonce('hp_table_management'); ?>'
                },
                success: function (response) {
                    modalLoading.hide();
                    if (response.success) {
                        var html = '<div class="hp-table-summary">';
                        html += '<p><strong>Total Rows:</strong> ' + response.data.count.toLocaleString() + '</p>';
                        html += '</div>';

                        html += '<table class="hp-structure-table">';
                        html += '<thead><tr>';
                        html += '<th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>';
                        html += '</tr></thead><tbody>';

                        response.data.columns.forEach(function (col) {
                            html += '<tr>';
                            html += '<td>' + col.Field + '</td>';
                            html += '<td>' + col.Type + '</td>';
                            html += '<td>' + col.Null + '</td>';
                            html += '<td>' + col.Key + '</td>';
                            html += '<td>' + (col.Default || '') + '</td>';
                            html += '<td>' + col.Extra + '</td>';
                            html += '</tr>';
                        });

                        html += '</tbody></table>';
                        modalContent.html(html).show();
                    } else {
                        modalContent.html('<p class="hp-notice error">' + response.data.message + '</p>').show();
                    }
                },
                error: function () {
                    modalLoading.hide();
                    modalContent.html('<p class="hp-notice error">Failed to load table structure.</p>').show();
                }
            });
        });

        // Close modal
        $('.hp-modal-close, .hp-modal').on('click', function (e) {
            if (e.target === this) {
                $('#hp-table-modal').hide();
            }
        });

        // Clear individual table
        $('.hp-clear-table').on('click', function () {
            var table = $(this).data('table');
            var card = $(this).closest('.hp-table-card');

            if (!confirm('Are you sure you want to clear all data from ' + table + '? This action cannot be undone.')) {
                return;
            }

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'hp_clear_table',
                    table: table,
                    nonce: '<?php echo wp_create_nonce('hp_table_management'); ?>'
                },
                success: function (response) {
                    if (response.success) {
                        card.find('.hp-row-count').text('0');
                        showMessage('success', response.data.message);
                    } else {
                        showMessage('error', response.data.message);
                    }
                },
                error: function () {
                    showMessage('error', 'Failed to clear table.');
                }
            });
        });

        // Delete individual table
        $('.hp-delete-table').on('click', function () {
            var table = $(this).data('table');
            var card = $(this).closest('.hp-table-card');

            if (!confirm('Are you sure you want to delete the table ' + table + '? This action cannot be undone and will remove the table structure entirely.')) {
                return;
            }

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'hp_delete_table',
                    table: table,
                    nonce: '<?php echo wp_create_nonce('hp_table_management'); ?>'
                },
                success: function (response) {
                    if (response.success) {
                        card.fadeOut(function () {
                            $(this).remove();
                        });
                        showMessage('success', response.data.message);
                    } else {
                        showMessage('error', response.data.message);
                    }
                },
                error: function () {
                    showMessage('error', 'Failed to delete table.');
                }
            });
        });

        // Clear All Tables
        $('#hp-clear-all-tables').click(function () {
            if (confirm('<?php esc_js_e('Are you sure you want to CLEAR ALL DATA from ALL HeritagePress tables?', 'heritagepress'); ?>\n\n' +
                '<?php esc_js_e('This will remove all rows but keep table structures.', 'heritagepress'); ?>')) {
                performBulkAction('hp_clear_all_tables', '<?php esc_js_e('Clearing all table data...', 'heritagepress'); ?>');
            }
        });

        // Delete All Tables
        $('#hp-delete-all-tables').click(function () {
            if (confirm('<?php esc_js_e('Are you sure you want to DELETE ALL HeritagePress tables?', 'heritagepress'); ?>\n\n' +
                '<?php esc_js_e('This will completely remove all tables and data. This action cannot be undone!', 'heritagepress'); ?>')) {
                performBulkAction('hp_delete_all_tables', '<?php esc_js_e('Deleting all tables...', 'heritagepress'); ?>');
            }
        });

        // Perform bulk action
        function performBulkAction(action, loadingMessage) {
            const button = $('#' + action.replace('hp_', 'hp-'));
            const originalText = button.html();

            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinner"></span> ' + loadingMessage);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: action,
                    nonce: '<?php echo wp_create_nonce('hp_table_management'); ?>'
                },
                success: function (response) {
                    button.prop('disabled', false).html(originalText);

                    if (response.success) {
                        showMessage('success', response.data.message);
                        // Refresh page to update the interface
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showMessage('error', response.data.message);
                    }
                },
                error: function () {
                    button.prop('disabled', false).html(originalText);
                    showMessage('error', '<?php esc_js_e('An error occurred while performing the bulk action.', 'heritagepress'); ?>');
                }
            });
        }

        function showMessage(type, message) {
            var messageDiv = '<div class="hp-notice ' + type + '">' + message + '</div>';
            $('#hp-table-messages').html(messageDiv);

            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function () {
                    $('#hp-table-messages .success').fadeOut();
                }, 5000);
            }
        }
    });
</script>