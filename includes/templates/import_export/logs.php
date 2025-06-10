<?php
/**
 * Logs tab template for Import/Export interface
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get logs from the database
$logs = get_option('heritagepress_importexport_logs', array());

// Default to empty array if no logs found
if (!is_array($logs)) {
    $logs = array();
}

// Reverse array to show newest logs first
$logs = array_reverse($logs);

// Add some demo data if no logs exist yet
if (empty($logs)) {
    // Demo logs for development/preview
    $logs = array(
        array(
            'timestamp' => strtotime('-2 days'),
            'action' => 'import',
            'message' => 'GEDCOM import completed: smith_family.ged',
            'details' => array(
                'tree_id' => 1,
                'tree_name' => 'Smith Family Tree',
                'filename' => 'smith_family.ged',
                'status' => 'success',
                'records' => 462,
                'individuals' => 250,
                'duration' => 65,
            ),
            'user_id' => get_current_user_id(),
        ),
        array(
            'timestamp' => strtotime('-4 days'),
            'action' => 'export',
            'message' => 'GEDCOM export completed: smith_family_export.ged',
            'details' => array(
                'tree_id' => 1,
                'tree_name' => 'Smith Family Tree',
                'filename' => 'smith_family_export.ged',
                'status' => 'success',
                'records' => 462,
                'individuals' => 250,
                'duration' => 12,
            ),
            'user_id' => get_current_user_id(),
        ),
        array(
            'timestamp' => strtotime('-7 days'),
            'action' => 'settings_update',
            'message' => 'Import/Export settings updated by user ID ' . get_current_user_id(),
            'details' => array(),
            'user_id' => get_current_user_id(),
        ),
    );
}
?>

<div class="hp-logs-container">
    <h3><?php esc_html_e('Import/Export Logs', 'heritagepress'); ?></h3>

    <div class="hp-log-filters">
        <form id="hp-log-filter-form" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="heritagepress-importexport">
            <input type="hidden" name="tab" value="logs">

            <select name="log_action">
                <option value=""><?php esc_html_e('All actions', 'heritagepress'); ?></option>
                <option value="import"><?php esc_html_e('Import', 'heritagepress'); ?></option>
                <option value="export"><?php esc_html_e('Export', 'heritagepress'); ?></option>
                <option value="settings_update"><?php esc_html_e('Settings', 'heritagepress'); ?></option>
            </select>

            <select name="log_status">
                <option value=""><?php esc_html_e('All statuses', 'heritagepress'); ?></option>
                <option value="success"><?php esc_html_e('Success', 'heritagepress'); ?></option>
                <option value="error"><?php esc_html_e('Error', 'heritagepress'); ?></option>
            </select>

            <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'heritagepress'); ?>">
        </form>
    </div>

    <table class="widefat hp-logs-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Date', 'heritagepress'); ?></th>
                <th><?php esc_html_e('Action', 'heritagepress'); ?></th>
                <th><?php esc_html_e('Message', 'heritagepress'); ?></th>
                <th><?php esc_html_e('User', 'heritagepress'); ?></th>
                <th><?php esc_html_e('Details', 'heritagepress'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="5"><?php esc_html_e('No logs found.', 'heritagepress'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $index => $log):
                    // Get user info
                    $user_info = get_userdata($log['user_id']);
                    $username = $user_info ? $user_info->user_login : __('Unknown', 'heritagepress');

                    // Format status label
                    $status = isset($log['details']['status']) ? $log['details']['status'] : '';
                    $status_class = $status === 'error' ? 'hp-status-error' : 'hp-status-success';

                    // Row class for even/odd styling
                    $row_class = $index % 2 === 0 ? 'alternate' : '';
                    ?>
                    <tr class="<?php echo esc_attr($row_class); ?>">
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $log['timestamp'])); ?>
                        </td>
                        <td>
                            <?php
                            switch ($log['action']) {
                                case 'import':
                                    esc_html_e('Import', 'heritagepress');
                                    break;
                                case 'export':
                                    esc_html_e('Export', 'heritagepress');
                                    break;
                                case 'settings_update':
                                    esc_html_e('Settings', 'heritagepress');
                                    break;
                                default:
                                    echo esc_html($log['action']);
                                    break;
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($log['message']); ?></td>
                        <td><?php echo esc_html($username); ?></td>
                        <td>
                            <?php if (!empty($log['details'])): ?>
                                <a href="#" class="hp-log-details-toggle"><?php esc_html_e('View Details', 'heritagepress'); ?></a>
                            <?php else: ?>
                                <span class="hp-no-details"><?php esc_html_e('No details', 'heritagepress'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (!empty($log['details'])): ?>
                        <tr class="hp-log-details-row" style="display: none;">
                            <td colspan="5" class="hp-log-details">
                                <table class="widefat">
                                    <tbody>
                                        <?php foreach ($log['details'] as $key => $value): ?>
                                            <tr>
                                                <th><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?></th>
                                                <td><?php echo is_array($value) ? esc_html(json_encode($value)) : esc_html($value); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    .hp-logs-container {
        margin-top: 20px;
    }

    .hp-log-filters {
        margin-bottom: 20px;
        padding: 10px;
        background: #f9f9f9;
        border: 1px solid #e0e0e0;
    }

    .hp-log-filters form {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .hp-logs-table {
        border-collapse: collapse;
        width: 100%;
        margin-bottom: 20px;
    }

    .hp-log-details {
        background: #f9f9f9;
        padding: 15px;
    }

    .hp-log-details table {
        margin: 0;
    }

    .hp-status-error {
        color: #d63638;
        font-weight: bold;
    }

    .hp-status-success {
        color: #00a32a;
    }

    .hp-log-details-toggle {
        text-decoration: none;
        color: #0073aa;
    }

    .hp-log-details-toggle:hover {
        color: #006799;
    }

    .hp-no-details {
        color: #999;
        font-style: italic;
    }
</style>