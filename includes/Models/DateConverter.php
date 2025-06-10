<?php
namespace HeritagePress\Models;

/**
 * Date conversion and comparison utilities
 */
class DateConverter
{
    /**
     * @var array Date modifiers and their normalized values
     */
    private $date_modifiers = [
        'ABT' => 'about',
        'CAL' => 'calculated',
        'EST' => 'estimated',
        'BEF' => 'before',
        'AFT' => 'after',
        'BET' => 'between',
        'FROM' => 'from',
        'TO' => 'to'
    ];

    /**
     * @var array Month names and their numeric values
     */
    private $month_names = [
        'JAN' => '01',
        'FEB' => '02',
        'MAR' => '03',
        'APR' => '04',
        'MAY' => '05',
        'JUN' => '06',
        'JUL' => '07',
        'AUG' => '08',
        'SEP' => '09',
        'OCT' => '10',
        'NOV' => '11',
        'DEC' => '12'
    ];

    /**
     * @var array Calendar escape sequences
     */
    private $calendar_escapes = [
        '@#DGREGORIAN@' => 'GREGORIAN',
        '@#DJULIAN@' => 'JULIAN',
        '@#DHEBREW@' => 'HEBREW',
        '@#DFRENCH R@' => 'FRENCH_R'
    ];

    /**
     * @var array Season names with their start and end months
     */
    private $season_dates = [
        'SPRING' => ['start' => '03', 'end' => '05'],
        'SUMMER' => ['start' => '06', 'end' => '08'],
        'FALL' => ['start' => '09', 'end' => '11'],
        'AUTUMN' => ['start' => '09', 'end' => '11'],
        'WINTER' => ['start' => '12', 'end' => '02']
    ];

    /**
     * @var array Quarter names and their start months
     */
    private $quarter_names = [
        'Q1' => '01',
        'Q2' => '04',
        'Q3' => '07',
        'Q4' => '10'
    ];

    /**
     * Compare two dates for sorting
     * 
     * @param array $date1 First date info array
     * @param array $date2 Second date info array
     * @return int -1 if date1 < date2, 0 if equal, 1 if date1 > date2
     */
    public function compareDates($date1, $date2)
    {
        // Handle null dates
        if (!$date1 && !$date2)
            return 0;
        if (!$date1)
            return -1;
        if (!$date2)
            return 1;

        // Get Julian Day Numbers for comparison
        $jdn1 = $this->dateToJDN($date1);
        $jdn2 = $this->dateToJDN($date2);

        if ($jdn1 === $jdn2)
            return 0;
        return ($jdn1 < $jdn2) ? -1 : 1;
    }

    /**
     * Convert a date to Julian Day Number
     * 
     * @param array $date_info Date info array
     * @return float|null Julian Day Number or null if invalid
     */
    public function dateToJDN($date_info)
    {
        if (!isset($date_info['date']) || !$date_info['date']) {
            return null;
        }

        // Parse date components
        $matches = [];
        if (!preg_match('/^(-?\d{4})-(\d{2})-(\d{2})$/', $date_info['date'], $matches)) {
            return null;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        // Convert based on calendar system
        switch ($date_info['calendar']) {
            case 'JULIAN':
                return $this->julianToJDN($year, $month, $day);
            case 'HEBREW':
                return $this->hebrewToJDN($year, $month, $day);
            case 'FRENCH_R':
                return $this->frenchRepublicanToJDN($year, $month, $day);
            default: // GREGORIAN
                return $this->gregorianToJDN($year, $month, $day);
        }
    }

    /**
     * @var CalendarSystem
     */
    private $calendar_system;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->calendar_system = new CalendarSystem();
    }

    /**
     * Convert Gregorian date to Julian Day Number
     */
    private function gregorianToJDN($year, $month, $day)
    {
        return $this->calendar_system->toJulianDayNumber($year, $month, $day, 'GREGORIAN');
    }

    /**
     * Convert Julian date to Julian Day Number
     */
    private function julianToJDN($year, $month, $day)
    {
        return $this->calendar_system->toJulianDayNumber($year, $month, $day, 'JULIAN');
    }

    /**
     * Convert Hebrew date to Julian Day Number
     * 
     * Implements a simplified Hebrew calendar conversion.
     * For a production system, you would want to use a more complete
     * implementation that handles all the complexities of the Hebrew calendar.
     */
    private function hebrewToJDN($year, $month, $day)
    {
        // Convert Hebrew year to Gregorian approximation
        $greg_year = $year + 3760;

        // Adjust month to approximate Gregorian month
        $greg_month = (($month + 6) % 12) + 1;
        if ($greg_month < 1)
            $greg_month += 12;

        return $this->gregorianToJDN($greg_year, $greg_month, $day);
    }

    /**
     * Convert French Republican date to Julian Day Number
     * 
     * Implements a simplified French Republican calendar conversion.
     * The French Republican calendar was used from 1793 to 1805.
     */
    private function frenchRepublicanToJDN($year, $month, $day)
    {
        // French Republican calendar started on 22 September 1793 (Gregorian)
        $start_jdn = 2380953;

        // Convert to days since start of calendar
        // Each year had 12 months of 30 days each, plus 5-6 complementary days
        $days = ($year - 1) * 365;
        $days += ($month - 1) * 30;
        $days += ($day - 1);

        return $start_jdn + $days;
    }

    /**
     * Check if a date falls within a valid range for its calendar
     * 
     * @param array $date_info Date info array
     * @return bool True if date is valid for its calendar
     */
    public function isValidCalendarDate($date_info)
    {
        if (!isset($date_info['date']) || !isset($date_info['calendar'])) {
            return false;
        }

        $jdn = $this->dateToJDN($date_info);
        if ($jdn === null) {
            return false;
        }

        switch ($date_info['calendar']) {
            case 'JULIAN':
                // Julian calendar was used before Gregorian adoption
                // This is a simplified check - actual adoption dates varied by region
                return $jdn <= 2299161; // October 15, 1582 Gregorian

            case 'GREGORIAN':
                // Gregorian calendar was adopted after October 4, 1582 Julian
                return $jdn >= 2299162; // October 15, 1582 Gregorian

            case 'HEBREW':
                // Hebrew calendar dates back to 3761 BCE
                return true;

            case 'FRENCH_R':
                // French Republican calendar was used from 1793 to 1805
                $start = 2380953; // September 22, 1793
                $end = $start + (12 * 365); // Approximate end date
                return $jdn >= $start && $jdn <= $end;

            default:
                return true;
        }
    }

    /**
     * Parse a GEDCOM date value into standardized format with season ranges
     * 
     * @param string $date_string GEDCOM date string
     * @return array{
     *   date: string|null,
     *   date_end: string|null,
     *   modifier: string|null,
     *   calendar: string,
     *   range_end: string|null,
     *   is_range: boolean,
     *   original: string,
     *   is_bce: boolean,
     *   is_season: boolean
     * } Parsed date components
     */
    public function parseDateValue($date_string)
    {
        $result = [
            'date' => null,
            'date_end' => null,
            'modifier' => null,
            'calendar' => 'GREGORIAN',
            'range_end' => null,
            'is_range' => false,
            'original' => $date_string,
            'is_bce' => false,
            'is_season' => false
        ];

        $date_string = trim($date_string);
        if (empty($date_string)) {
            return $result;
        }

        // Check for calendar escape sequence
        foreach ($this->calendar_escapes as $escape => $calendar) {
            if (strpos($date_string, $escape) === 0) {
                $result['calendar'] = $calendar;
                $date_string = trim(str_replace($escape, '', $date_string));
                break;
            }
        }

        // Check for BCE/BC indicators before other processing
        if (preg_match('/(B\.?C\.?E?\.?|BCE|BC)$/i', $date_string, $matches)) {
            $result['is_bce'] = true;
            $date_string = trim(str_replace($matches[0], '', $date_string));
        }

        // Check for date modifiers
        $words = preg_split('/\s+/', $date_string);
        $first_word = strtoupper($words[0]);

        if (isset($this->date_modifiers[$first_word])) {
            $result['modifier'] = $this->date_modifiers[$first_word];
            array_shift($words);
            $date_string = implode(' ', $words);
        }

        // Check for seasons
        foreach ($words as $word) {
            $word = strtoupper($word);
            if (isset($this->season_dates[$word])) {
                $result['is_season'] = true;
                $year = null;

                // Find the year in the date string
                foreach ($words as $year_word) {
                    if (is_numeric($year_word) && (int) $year_word > 31) {
                        $year = (int) $year_word;
                        break;
                    }
                }

                if ($year !== null) {
                    $season = $this->season_dates[$word];
                    $start_year = $year;
                    $end_year = ($word === 'WINTER') && $season['end'] < $season['start'] ? $year + 1 : $year;

                    $result['date'] = sprintf('%04d-%02d-01', $start_year, (int) $season['start']);
                    $result['date_end'] = sprintf(
                        '%04d-%02d-%02d',
                        $end_year,
                        (int) $season['end'],
                        cal_days_in_month(CAL_GREGORIAN, (int) $season['end'], $end_year)
                    );

                    if ($result['is_bce']) {
                        $result['date'] = '-' . $result['date'];
                        $result['date_end'] = '-' . $result['date_end'];
                    }
                }
                break;
            }
        }

        // If not a season, process as normal date
        if (!$result['is_season']) {
            if ($result['modifier'] === 'between' && strpos($date_string, 'AND') !== false) {
                $result['is_range'] = true;
                list($start, $end) = explode('AND', $date_string, 2);
                $start_date = $this->standardizeDate(trim($start));
                $end_date = $this->standardizeDate(trim($end));
                if ($result['is_bce'] && $start_date && $end_date) {
                    $start_date = '-' . $start_date;
                    $end_date = '-' . $end_date;
                }
                $result['date'] = $start_date;
                $result['range_end'] = $end_date;
            } elseif (in_array($result['modifier'], ['from', 'to'])) {
                $result['is_range'] = true;
                $date = $this->standardizeDate($date_string);
                if ($result['is_bce'] && $date) {
                    $date = '-' . $date;
                }
                $result['date'] = $date;
            } else {
                // Single date
                $date = $this->standardizeDate($date_string);
                if ($result['is_bce'] && $date) {
                    $date = '-' . $date;
                }
                $result['date'] = $date;
            }
        }

        return $result;
    }

    /**
     * Convert a date string to standardized format
     * 
     * @param string $date_string Date string
     * @return string|null Standardized date or null if invalid
     */
    public function standardizeDate($date_string)
    {
        // Handle text-only dates or incomplete dates
        if (empty($date_string) || strtoupper($date_string) === 'UNKNOWN') {
            return null;
        }

        $words = preg_split('/\s+/', trim($date_string));
        $parts = ['year' => null, 'month' => null, 'day' => null];

        // Handle dual dating (e.g., 1750/51)
        if (preg_match('/^(\d{4})\/\d{2}$/', $words[0], $matches)) {
            $parts['year'] = $matches[1]; // Use the first year
            array_shift($words);
        }

        // Handle interpreted dates
        if ($words[0] === 'INT') {
            array_shift($words); // Remove INT
        }

        // Process each word
        foreach ($words as $word) {
            $word = strtoupper(trim($word));

            // Check for seasons
            if (isset($this->season_dates[$word])) {
                $parts['month'] = $this->season_dates[$word]['start'];
                continue;
            }

            // Check for quarters
            if (isset($this->quarter_names[$word])) {
                $parts['month'] = $this->quarter_names[$word];
                continue;
            }

            // Check for months
            if (isset($this->month_names[$word])) {
                $parts['month'] = $this->month_names[$word];
                continue;
            }

            // Handle ordinal numbers (1st, 2nd, 3rd, etc.)
            if (preg_match('/^(\d+)(ST|ND|RD|TH)$/i', $word, $matches)) {
                $num = (int) $matches[1];
                if ($parts['day'] === null && $num <= 31) {
                    $parts['day'] = sprintf('%02d', $num);
                }
                continue;
            }

            // Check if it's a number
            if (is_numeric($word)) {
                $num = (int) $word;
                if ($parts['year'] === null && $num > 31) {
                    // Assume it's a year if > 31
                    $parts['year'] = sprintf('%04d', $num);
                } elseif ($parts['day'] === null && $num <= 31) {
                    // Assume it's a day if ≤ 31
                    $parts['day'] = sprintf('%02d', $num);
                }
            }
        }

        // Return null if no year found
        if ($parts['year'] === null) {
            return null;
        }

        // Validate day/month combinations
        if ($parts['month'] !== null && $parts['day'] !== null) {
            $days_in_month = cal_days_in_month(CAL_GREGORIAN, (int) $parts['month'], (int) $parts['year']);
            if ((int) $parts['day'] > $days_in_month) {
                $parts['day'] = null; // Invalid day for month
            }
        }

        // Build date string
        $date = $parts['year'];
        if ($parts['month'] !== null) {
            $date = $parts['year'] . '-' . $parts['month'];
            if ($parts['day'] !== null) {
                $date .= '-' . $parts['day'];
            }
        }

        return $date;
    }
}
