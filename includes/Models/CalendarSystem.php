<?php
namespace HeritagePress\Models;

/**
 * Calendar system model
 */
class CalendarSystem extends Model
{    /**
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
        // If same calendar, no conversion needed
        if ($from_code === $to_code) {
            return $date_string;
        }

        // Parse the date string
        $parts = $this->parseDateString($date_string);
        if (!$parts) {
            return false;
        }

        // Convert to Julian Day Number first
        $jdn = $this->toJulianDayNumber($parts['year'], $parts['month'], $parts['day'], $from_code);
        if ($jdn === false) {
            return false;
        }

        // Then convert from Julian Day Number to target calendar
        $date = $this->fromJulianDayNumber($jdn, $to_code);
        if (!$date) {
            return false;
        }

        return sprintf('%04d-%02d-%02d', $date['year'], $date['month'], $date['day']);
    }

    /**
     * Parse a date string into components
     *
     * @param string $date_string Date string in Y-m-d format
     * @return array|false Array with year, month, day or false on failure
     */
    private function parseDateString($date_string)
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date_string, $matches)) {
            return false;
        }

        return [
            'year' => (int) $matches[1],
            'month' => (int) $matches[2],
            'day' => (int) $matches[3]
        ];
    }    /**
         * Convert a date to Julian Day Number
         *
         * @param int    $year Year
         * @param int    $month Month
         * @param int    $day Day
         * @param string $calendar Calendar code
         * @return int|false Julian Day Number or false on failure
         */
    public function toJulianDayNumber($year, $month, $day, $calendar)
    {
        switch ($calendar) {
            case 'GREGORIAN':
                return $this->gregorianToJDN($year, $month, $day);
            case 'JULIAN':
                return $this->julianToJDN($year, $month, $day);
            case 'HEBREW':
                return $this->hebrewToJDN($year, $month, $day);
            case 'FRENCH_R':
                return $this->frenchRepublicanToJDN($year, $month, $day);
            default:
                return false;
        }
    }

    /**
     * Convert from Julian Day Number to target calendar date
     *
     * @param int    $jdn Julian Day Number
     * @param string $calendar Calendar code
     * @return array|false Array with year, month, day or false on failure
     */
    private function fromJulianDayNumber($jdn, $calendar)
    {
        switch ($calendar) {
            case 'GREGORIAN':
                return $this->jdnToGregorian($jdn);
            case 'JULIAN':
                return $this->jdnToJulian($jdn);
            case 'HEBREW':
                return $this->jdnToHebrew($jdn);
            case 'FRENCH_R':
                return $this->jdnToFrenchRepublican($jdn);
            default:
                return false;
        }
    }

    /**
     * Convert Gregorian date to Julian Day Number
     */
    private function gregorianToJDN($year, $month, $day)
    {
        if ($month <= 2) {
            $year -= 1;
            $month += 12;
        }

        $a = floor($year / 100);
        $b = 2 - $a + floor($a / 4);

        return floor(365.25 * ($year + 4716)) +
            floor(30.6001 * ($month + 1)) +
            $day + $b - 1524.5;
    }

    /**
     * Convert Julian Day Number to Gregorian date
     */
    private function jdnToGregorian($jdn)
    {
        $jdn = $jdn + 0.5;
        $z = floor($jdn);
        $f = $jdn - $z;

        if ($z < 2299161) {
            $a = $z;
        } else {
            $alpha = floor(($z - 1867216.25) / 36524.25);
            $a = $z + 1 + $alpha - floor($alpha / 4);
        }

        $b = $a + 1524;
        $c = floor(($b - 122.1) / 365.25);
        $d = floor(365.25 * $c);
        $e = floor(($b - $d) / 30.6001);

        $day = $b - $d - floor(30.6001 * $e) + $f;
        $month = $e < 14 ? $e - 1 : $e - 13;
        $year = $month > 2 ? $c - 4716 : $c - 4715;

        return ['year' => $year, 'month' => $month, 'day' => floor($day)];
    }

    /**
     * Convert Julian date to Julian Day Number
     */
    private function julianToJDN($year, $month, $day)
    {
        if ($month <= 2) {
            $year -= 1;
            $month += 12;
        }

        return floor(365.25 * ($year + 4716)) +
            floor(30.6001 * ($month + 1)) +
            $day - 1524.5;
    }

    /**
     * Convert Julian Day Number to Julian date
     */
    private function jdnToJulian($jdn)
    {
        $jdn = $jdn + 0.5;
        $z = floor($jdn);
        $f = $jdn - $z;

        $j = $z + 1524;
        $k = floor(($j - 122.1) / 365.25);
        $l = floor(365.25 * $k);
        $n = floor(($j - $l) / 30.6001);

        $day = $j - $l - floor(30.6001 * $n) + $f;
        $month = $n < 14 ? $n - 1 : $n - 13;
        $year = $month > 2 ? $k - 4716 : $k - 4715;

        return ['year' => $year, 'month' => $month, 'day' => floor($day)];
    }

    /**
     * Convert Hebrew date to Julian Day Number
     */
    private function hebrewToJDN($year, $month, $day)
    {
        // Convert Hebrew date to JDN using standard algorithms
        $months = $this->getHebrewMonths($year);
        $jdn = $this->hebrewToAbsolute($year, $month, $day);
        return $jdn + 347997;
    }

    /**
     * Convert Julian Day Number to Hebrew date
     */
    private function jdnToHebrew($jdn)
    {
        $absolute = $jdn - 347997;
        $year = floor($absolute / 365) + 3760;

        // Adjust year based on the Hebrew calendar rules
        while ($this->hebrewToAbsolute($year + 1, 7, 1) <= $absolute) {
            $year++;
        }

        // Find month and day
        $month = 1;
        while ($month <= 13) {
            $next_month = $this->hebrewToAbsolute($year, $month + 1, 1);
            if ($absolute < $next_month) {
                break;
            }
            $month++;
        }

        $day = $absolute - $this->hebrewToAbsolute($year, $month, 1) + 1;

        return ['year' => $year, 'month' => $month, 'day' => $day];
    }

    /**
     * Convert French Republican date to Julian Day Number
     */
    private function frenchRepublicanToJDN($year, $month, $day)
    {
        // French Republican calendar started at 22 September 1793 (Gregorian)
        $start_jdn = 2380953; // JDN for 22 September 1793

        // Calculate days since start of calendar
        $days = ($year - 1) * 365 + floor(($year - 1) / 4) +
            ($month - 1) * 30 + ($day - 1);

        return $start_jdn + $days;
    }

    /**
     * Convert Julian Day Number to French Republican date
     */
    private function jdnToFrenchRepublican($jdn)
    {
        // French Republican calendar started at 22 September 1793 (Gregorian)
        $start_jdn = 2380953; // JDN for 22 September 1793

        if ($jdn < $start_jdn) {
            return false;
        }

        $days = $jdn - $start_jdn;
        $year = floor($days / 365) + 1;
        $remaining_days = $days % 365;

        $month = floor($remaining_days / 30) + 1;
        $day = ($remaining_days % 30) + 1;

        return ['year' => $year, 'month' => $month, 'day' => $day];
    }

    /**
     * Helper function for Hebrew calendar
     */
    private function hebrewToAbsolute($year, $month, $day)
    {
        $months = $this->getHebrewMonths($year);
        $days = $day;

        // Add days in prior months this year
        for ($m = 1; $m < $month; $m++) {
            $days += $months[$m];
        }

        // Add days in prior years
        $days += 365 * ($year - 1) +
            floor(($year - 1) / 4) -
            floor(($year - 1) / 100) +
            floor(($year - 1) / 400);

        return $days;
    }

    /**
     * Get number of days in each Hebrew month for a given year
     */
    private function getHebrewMonths($year)
    {
        // Determine if it's a leap year in the Hebrew calendar
        $leap = ((7 * $year + 1) % 19) < 7;

        return [
            1 => 30,  // Tishri
            2 => 29,  // Heshvan
            3 => 30,  // Kislev
            4 => 29,  // Tevet
            5 => 30,  // Shevat
            6 => $leap ? 30 : 29,  // Adar I (30 in leap years)
            7 => $leap ? 29 : 30,  // Adar II (29 in leap years) or Nisan
            8 => 29,  // Iyar
            9 => 30,  // Sivan
            10 => 29, // Tammuz
            11 => 30, // Av
            12 => 29, // Elul
            13 => $leap ? 29 : 0   // Adar II (only in leap years)
        ];
    }
}
