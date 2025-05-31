<?php
/**
 * Main Admin Page
 *
 * @package HeritagePress
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $wpdb;
$prefix = $wpdb->prefix . 'heritage_press_';

// Get statistics
$individuals_count = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}individuals WHERE status = 'active'");
$families_count = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}families WHERE status = 'active'");
$gedcom_trees_count = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}gedcom_trees WHERE status = 'active'");

// Check table status
$tables = [
    'individuals' => 'Individuals',
    'families' => 'Families', 
    'events' => 'Events',
    'places' => 'Places',
    'sources' => 'Sources',
    'repositories' => 'Repositories',
    'gedcom_trees' => 'GEDCOM Trees'
];

$table_status = [];
foreach ($tables as $table => $label) {
    $table_name = $prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    $table_status[$table] = $exists;
}
?>

<div class="wrap">
    <h1><?php _e('Heritage Press - Genealogy Management', 'heritage-press'); ?></h1>
    
    <div class="heritage-press-dashboard">
        <!-- Statistics Cards -->
        <div class="heritage-stats">
            <div class="stat-card">
                <h3><?php _e('Individuals', 'heritage-press'); ?></h3>
                <div class="stat-number"><?php echo number_format($individuals_count ?: 0); ?></div>
                <a href="<?php echo admin_url('admin.php?page=heritage-press-individuals'); ?>" class="button">
                    <?php _e('Manage Individuals', 'heritage-press'); ?>
                </a>
            </div>
            
            <div class="stat-card">
                <h3><?php _e('Families', 'heritage-press'); ?></h3>
                <div class="stat-number"><?php echo number_format($families_count ?: 0); ?></div>
                <a href="<?php echo admin_url('admin.php?page=heritage-press-families'); ?>" class="button">
                    <?php _e('Manage Families', 'heritage-press'); ?>
                </a>
            </div>
            
            <div class="stat-card">
                <h3><?php _e('GEDCOM Trees', 'heritage-press'); ?></h3>
                <div class="stat-number"><?php echo number_format($gedcom_trees_count ?: 0); ?></div>
                <a href="<?php echo admin_url('admin.php?page=heritage-press-import'); ?>" class="button">
                    <?php _e('Import GEDCOM', 'heritage-press'); ?>
                </a>
            </div>
        </div>

        <!-- System Status -->
        <div class="heritage-system-status">
            <h2><?php _e('System Status', 'heritage-press'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Component', 'heritage-press'); ?></th>
                        <th><?php _e('Status', 'heritage-press'); ?></th>
                        <th><?php _e('Details', 'heritage-press'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php _e('Plugin Version', 'heritage-press'); ?></strong></td>
                        <td><span class="status-active">✅ <?php echo HERITAGE_PRESS_VERSION; ?></span></td>
                        <td><?php _e('Plugin is active and running', 'heritage-press'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Database Version', 'heritage-press'); ?></strong></td>
                        <td><span class="status-active">✅ <?php echo get_option('heritage_press_db_version', '1.0.0'); ?></span></td>
                        <td><?php _e('Database schema is up to date', 'heritage-press'); ?></td>
                    </tr>
                    <?php foreach ($table_status as $table => $exists): ?>
                    <tr>
                        <td><strong><?php echo esc_html($tables[$table]); ?> <?php _e('Table', 'heritage-press'); ?></strong></td>
                        <td>
                            <?php if ($exists): ?>
                                <span class="status-active">✅ <?php _e('Created', 'heritage-press'); ?></span>
                            <?php else: ?>
                                <span class="status-error">❌ <?php _e('Missing', 'heritage-press'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($exists): ?>
                                <?php _e('Table exists and ready', 'heritage-press'); ?>
                            <?php else: ?>
                                <?php _e('Table missing - please reactivate plugin', 'heritage-press'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Quick Actions -->
        <div class="heritage-quick-actions">
            <h2><?php _e('Quick Actions', 'heritage-press'); ?></h2>
            <div class="action-buttons">
                <a href="<?php echo admin_url('admin.php?page=heritage-press-import'); ?>" class="button button-primary">
                    <?php _e('Import GEDCOM File', 'heritage-press'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=heritage-press-individuals'); ?>" class="button">
                    <?php _e('Add Individual', 'heritage-press'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=heritage-press-families'); ?>" class="button">
                    <?php _e('Add Family', 'heritage-press'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=evidence-admin'); ?>" class="button">
                    <?php _e('Evidence Analysis', 'heritage-press'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.heritage-press-dashboard {
    max-width: 1200px;
}

.heritage-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    flex: 1;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.stat-card h3 {
    margin: 0 0 10px 0;
    color: #1d2327;
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #2271b1;
    margin-bottom: 15px;
}

.heritage-system-status {
    margin-bottom: 30px;
}

.heritage-system-status table {
    margin-top: 15px;
}

.status-active {
    color: #00a32a;
    font-weight: bold;
}

.status-error {
    color: #d63638;
    font-weight: bold;
}

.heritage-quick-actions {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.action-buttons .button {
    margin: 0;
}
</style>
