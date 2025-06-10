<?php
/**
 * Export tab template for Import/Export interface
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get saved export settings
$export_settings = get_option('heritagepress_export_settings', array());
$default_version = isset($export_settings['default_version']) ? $export_settings['default_version'] : '5.5.1';
$default_format = isset($export_settings['default_format']) ? $export_settings['default_format'] : 'gedcom';
$privacy_living = isset($export_settings['privacy_living']) ? (bool)$export_settings['privacy_living'] : true;
$privacy_notes = isset($export_settings['privacy_notes']) ? (bool)$export_settings['privacy_notes'] : false;
$privacy_media = isset($export_settings['privacy_media']) ? (bool)$export_settings['privacy_media'] : false;

// Get available trees
// In a real implementation, this would come from a tree manager class
$available_trees = array(
    array('id' => 1, 'name' => 'Smith Family Tree'),
    array('id' => 2, 'name' => 'Johnson Family'),
    array('id' => 3, 'name' => 'Williams Genealogy')
);
?>

<div class="hp-export-container">
    <h3><?php esc_html_e('Export GEDCOM File', 'heritagepress'); ?></h3>
    
    <form method="post" id="hp-gedcom-export-form" class="hp-form">
        <?php wp_nonce_field('hp_gedcom_export', 'hp_gedcom_export_nonce'); ?>
        
        <table class="form-table">