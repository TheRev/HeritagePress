<?php
/**
 * Media Settings Admin Page
 */

namespace HeritagePress\Admin;

class Media_Settings {
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings() {
        register_setting('heritage_press_media', 'heritage_press_use_wp_media');
        register_setting('heritage_press_media', 'heritage_press_media_privacy');
        register_setting('heritage_press_media', 'heritage_press_optimize_images');
        register_setting('heritage_press_media', 'heritage_press_max_upload_mb');        

        add_settings_section(
            'heritage_press_media_section',
            'Media Import Settings',
            [$this, 'section_callback'],
            'heritage_press_media'
        );

        add_settings_field(
            'heritage_press_use_wp_media',
            'Use WordPress Media Library',
            [$this, 'checkbox_callback'],
            'heritage_press_media',
            'heritage_press_media_section',
            [
                'label_for' => 'heritage_press_use_wp_media',
                'description' => 'Import media files into WordPress Media Library'
            ]
        );

        add_settings_field(
            'heritage_press_media_privacy',
            'Media Privacy',
            [$this, 'select_callback'],
            'heritage_press_media',
            'heritage_press_media_section',
            [
                'label_for' => 'heritage_press_media_privacy',
                'options' => [
                    'public' => 'Public',
                    'private' => 'Private'
                ],
                'description' => 'Default privacy setting for imported media'
            ]
        );

        add_settings_field(
            'heritage_press_optimize_images',
            'Optimize Images',
            [$this, 'checkbox_callback'],
            'heritage_press_media',
            'heritage_press_media_section',
            [
                'label_for' => 'heritage_press_optimize_images',
                'description' => 'Automatically optimize imported images for web use'
            ]
        );

        add_settings_field(
            'heritage_press_max_upload_mb',
            'Maximum Upload Size (MB)',
            [$this, 'number_callback'],
            'heritage_press_media',
            'heritage_press_media_section',
            [
                'label_for' => 'heritage_press_max_upload_mb',
                'description' => 'Maximum file size for media imports (in megabytes)',
                'min' => 1,
                'max' => wp_max_upload_size() / (1024 * 1024)
            ]
        );
    }

    public function section_callback() {
        echo '<p>Configure how media files are handled during GEDCOM imports.</p>';
    }

    public function checkbox_callback($args) {
        $option = get_option($args['label_for']);
        ?>
        <input
            type="checkbox"
            id="<?php echo esc_attr($args['label_for']); ?>"
            name="<?php echo esc_attr($args['label_for']); ?>"
            value="1"
            <?php checked(1, $option, true); ?>
        >
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }

    public function select_callback($args) {
        $option = get_option($args['label_for']);
        ?>
        <select
            id="<?php echo esc_attr($args['label_for']); ?>"
            name="<?php echo esc_attr($args['label_for']); ?>"
        >
            <?php foreach ($args['options'] as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($option, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }

    public function number_callback($args) {
        $option = get_option($args['label_for']);
        ?>
        <input
            type="number"
            id="<?php echo esc_attr($args['label_for']); ?>"
            name="<?php echo esc_attr($args['label_for']); ?>"
            value="<?php echo esc_attr($option); ?>"
            min="<?php echo esc_attr($args['min']); ?>"
            max="<?php echo esc_attr($args['max']); ?>"
        >
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }
}
