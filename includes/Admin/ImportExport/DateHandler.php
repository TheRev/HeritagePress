<?php
/**
 * Date Handler for HeritagePress
 *
 * @package HeritagePress
 * @subpackage Admin
 */

namespace HeritagePress\Admin\ImportExport;

/**
 * Class DateHandler
 *
 * Handles date validation and conversion operations
 */
class DateHandler extends BaseManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Register date-specific AJAX handlers
        add_action('wp_ajax_hp_validate_date', array($this, 'handle_date_validation'));
        add_action('wp_ajax_hp_convert_date', array($this, 'handle_date_conversion'));
    }

    /**
     * AJAX handler for date validation
     */
    public function handle_date_validation()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hp_admin_nonce')) {
            wp_send_json_error(array('message' => 'Invalid request'));
            return;
        }

        $date_string = sanitize_text_field($_POST['date_string'] ?? '');

        if (empty($date_string)) {
            wp_send_json_error(array('message' => 'Date string is required'));
            return;
        }

        try {
            $parsed_date = $this->date_converter->parseDateValue($date_string);

            wp_send_json_success(array(
                'parsed' => $parsed_date,
                'is_valid' => !empty($parsed_date['date']),
                'formatted' => $parsed_date['date'],
                'calendar' => $parsed_date['calendar'],
                'modifier' => $parsed_date['modifier'],
                'is_range' => $parsed_date['is_range'],
                'original' => $date_string
            ));
        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => 'Date parsing failed: ' . $e->getMessage()
            ));
        }
    }

    /**
     * AJAX handler for date format conversion
     */
    public function handle_date_conversion()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hp_admin_nonce')) {
            wp_send_json_error(array('message' => 'Invalid request'));
            return;
        }

        $date_string = sanitize_text_field($_POST['date_string'] ?? '');
        $target_format = sanitize_text_field($_POST['target_format'] ?? 'standard');

        if (empty($date_string)) {
            wp_send_json_error(array('message' => 'Date string is required'));
            return;
        }

        try {
            $parsed_date = $this->date_converter->parseDateValue($date_string);

            // Convert to different formats
            $conversions = array(
                'original' => $date_string,
                'parsed' => $parsed_date,
                'standard' => $parsed_date['date'],
                'calendar' => $parsed_date['calendar']
            );

            // Add Julian Day Number if valid date
            if (!empty($parsed_date['date'])) {
                $jdn = $this->date_converter->dateToJDN($parsed_date);
                if ($jdn !== null) {
                    $conversions['julian_day'] = $jdn;
                }
            }

            wp_send_json_success($conversions);
        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => 'Date conversion failed: ' . $e->getMessage()
            ));
        }
    }

    /**
     * Validate date string format
     *
     * @param string $date_string Date string to validate
     * @return array Validation result
     */
    public function validate_date_string($date_string)
    {
        try {
            $parsed_date = $this->date_converter->parseDateValue($date_string);

            return array(
                'valid' => !empty($parsed_date['date']),
                'parsed' => $parsed_date,
                'formatted' => $parsed_date['date'] ?? '',
                'calendar' => $parsed_date['calendar'] ?? 'Gregorian',
                'modifier' => $parsed_date['modifier'] ?? '',
                'is_range' => $parsed_date['is_range'] ?? false
            );
        } catch (\Exception $e) {
            return array(
                'valid' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Convert date between different calendar systems
     *
     * @param string $date_string Date string to convert
     * @param string $target_calendar Target calendar system
     * @return array Conversion result
     */
    public function convert_date_calendar($date_string, $target_calendar = 'Gregorian')
    {
        try {
            $parsed_date = $this->date_converter->parseDateValue($date_string);

            // Get Julian Day Number
            $jdn = $this->date_converter->dateToJDN($parsed_date);

            if ($jdn === null) {
                throw new \Exception('Invalid date for conversion');
            }

            // Convert to target calendar
            $converted_date = $this->date_converter->JDNToDate($jdn, $target_calendar);

            return array(
                'success' => true,
                'original' => $date_string,
                'converted' => $converted_date,
                'julian_day' => $jdn,
                'source_calendar' => $parsed_date['calendar'] ?? 'Gregorian',
                'target_calendar' => $target_calendar
            );
        } catch (\Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Get supported calendar systems
     *
     * @return array List of supported calendars
     */
    public function get_supported_calendars()
    {
        return array(
            'Gregorian' => __('Gregorian', 'heritagepress'),
            'Julian' => __('Julian', 'heritagepress'),
            'Hebrew' => __('Hebrew', 'heritagepress'),
            'French' => __('French Republican', 'heritagepress')
        );
    }

    /**
     * Format date for display
     *
     * @param array $parsed_date Parsed date array
     * @param string $format Display format
     * @return string Formatted date
     */
    public function format_date_for_display($parsed_date, $format = 'full')
    {
        if (empty($parsed_date['date'])) {
            return '';
        }

        $formatted = $parsed_date['date'];

        // Add modifier if present
        if (!empty($parsed_date['modifier'])) {
            $formatted = $parsed_date['modifier'] . ' ' . $formatted;
        }

        // Add calendar if not Gregorian
        if (!empty($parsed_date['calendar']) && $parsed_date['calendar'] !== 'Gregorian') {
            $formatted .= ' (' . $parsed_date['calendar'] . ')';
        }

        return $formatted;
    }
}
