<?php
/**
 *            <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-import-export&tab=' . $tab_id)); ?>"Header template for Import/Export interface
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="wrap heritagepress-import-export">
    <h1><?php esc_html_e('GEDCOM Import & Export', 'heritagepress'); ?></h1>
    <h2 class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_id => $tab_name): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-import-export&tab=' . $tab_id)); ?>"
                class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_name); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <div class="hp-tab-content">