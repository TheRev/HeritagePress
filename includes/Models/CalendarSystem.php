<?php
namespace HeritagePress\Models;

/**
 * Calendar system model
 */
class CalendarSystem extends Model
{
    /**
     * Database table name
     *
     * @var string
     */
    protected $table = 'calendar_systems';

    /**
     * Default calendar systems
     *
     * @var array
     */
    private $default_calendars = [
        [
            'code' => 'GREGORIAN',
            'name' => 'Gregorian Calendar',
            'description' => 'Standard calendar used worldwide',
            'earliest_date' => '1582-10-15',
            'latest_date' => null
        ],
        [
            'code' => 'JULIAN',
            'name' => 'Julian Calendar',
            'description' => 'Calendar used before Gregorian reform',
            'earliest_date' => null,
            'latest_date' => '1582-10-04'
        ],
        [
            'code' => 'HEBREW',
            'name' => 'Hebrew Calendar',
            'description' => 'Traditional Jewish calendar',
            'earliest_date' => null,
            'latest_date' => null
        ],
        [
            'code' => 'FRENCH_R',
            'name' => 'French Republican Calendar',
            'description' => 'Calendar used during French Revolution',
            'earliest_date' => '1793-09-22',
            'latest_date' => '1805-12-31'
        ]
    ];

    /**
     * Initialize default calendar systems
     *
     * @return bool True if successful
     */
    public function initDefaults()
    {
        $success = true;

        foreach ($this->default_calendars as $calendar) {
            if (!$this->findByCode($calendar['code'])) {
                if (!$this->insert($calendar)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Find a calendar system by its code
     *
     * @param string $code Calendar code
     * @return object|null Database row
     */
    public function findByCode($code)
    {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE code = %s",
                $code
            )
        );
    }

    /**
     * Get all calendar systems
     *
     * @return array Array of calendar system records
     */
    public function getAll()
    {
        return $this->db->get_results(
            "SELECT * FROM {$this->table} ORDER BY code"
        );
    }

    /**
     * Convert a date between calendar systems
     *
     * @param string $date_string Date string in original calendar
     * @param string $from_code   Source calendar code
     * @param string $to_code     Target calendar code
     * @return string|false Converted date string or false on failure
     */
    public function convertDate($date_string, $from_code, $to_code)
    {
        // This is a placeholder for actual calendar conversion logic
        // We would need to implement specific conversion algorithms for each calendar pair
        if ($from_code === $to_code) {
            return $date_string;
        }

        // For now, return false to indicate conversion not supported
        return false;
    }
}
