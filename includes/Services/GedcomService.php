<?php
namespace HeritagePress\Services;

use HeritagePress\Models\GedzipArchive;
use Exception;

/**
 * GEDCOM Import/Export Service
 */
use HeritagePress\Models\DateConverter;

class GedcomService
{
    /**
     * @var array Supported GEDCOM versions
     */
    private $supported_versions = ['5.5.1', '7.0'];

    /**
     * @var DateConverter
     */
    private $date_converter;

    /**
     * @var string Current GEDCOM version being processed
     */
    private $current_version;

    /**
     * @var array Line buffer for processing
     */
    private $buffer = [];

    /**
     * @var int Current tree ID     */    
    private $tree_id;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->date_converter = new DateConverter();
    }

    /**
     * Import a GEDCOM file
     *
     * @param string $filepath Path to GEDCOM file
     * @param int    $tree_id  Tree ID to import into
     * @return bool True if successful
     * @throws Exception If file is invalid or unsupported
     */
    public function import($filepath, $tree_id)
    {
        if (!file_exists($filepath)) {
            throw new Exception('GEDCOM file not found: ' . $filepath);
        }

        $this->tree_id = $tree_id;
        $this->buffer = [];

        // Check if this is a GEDZIP file
        if (strtolower(pathinfo($filepath, PATHINFO_EXTENSION)) === 'gdz') {
            return $this->importGedzip($filepath);
        }

        // Process regular GEDCOM file
        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            throw new Exception('Could not open GEDCOM file: ' . $filepath);
        }

        try {
            // First pass: validate header and version
            $this->validateHeader($handle);

            // Second pass: process records
            rewind($handle);
            $this->processRecords($handle);

            return true;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Import a GEDZIP file
     *
     * @param string $filepath Path to GEDZIP file
     * @return bool True if successful
     * @throws Exception If file is invalid
     */
    private function importGedzip($filepath)
    {
        $gedzip = new GedzipArchive();
        $archive_id = $gedzip->import($filepath, $this->tree_id);

        if (!$archive_id) {
            throw new Exception('Failed to import GEDZIP archive');
        }

        // Extract to temporary directory
        $temp_dir = sys_get_temp_dir() . '/hp_gedzip_' . uniqid();
        if (!mkdir($temp_dir) || !$gedzip->extract($archive_id, $temp_dir)) {
            throw new Exception('Failed to extract GEDZIP archive');
        }

        try {
            // Find and process the GEDCOM file
            $gedcom_file = null;
            $dir = new \RecursiveDirectoryIterator($temp_dir);
            $iterator = new \RecursiveIteratorIterator($dir);

            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'ged') {
                    $gedcom_file = $file->getPathname();
                    break;
                }
            }

            if (!$gedcom_file) {
                throw new Exception('No GEDCOM file found in GEDZIP archive');
            }

            // Import the GEDCOM file
            return $this->import($gedcom_file, $this->tree_id);
        } finally {
            // Clean up temporary directory
            $this->removeDirectory($temp_dir);
        }
    }

    /**
     * Validate GEDCOM header
     *
     * @param resource $handle File handle
     * @throws Exception If header is invalid
     */
    private function validateHeader($handle)
    {
        // Read first few lines to find version
        $version = null;
        $count = 0;

        while (($line = fgets($handle)) !== false && $count < 100) {
            $count++;
            $line = trim($line);

            // Look for version information
            if (preg_match('/^1\s+VERS\s+(.+)$/', $line, $matches)) {
                $version = trim($matches[1]);
                break;
            }
        }

        if (!$version || !in_array($version, $this->supported_versions)) {
            throw new Exception('Unsupported GEDCOM version: ' . ($version ?: 'unknown'));
        }

        $this->current_version = $version;
    }

    /**
     * Process GEDCOM records
     *
     * @param resource $handle File handle
     */
    private function processRecords($handle)
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);

                // Skip empty lines
                if (empty($line)) {
                    continue;
                }

                // Process level 0 records
                if ($line[0] === '0') {
                    // Process any buffered record
                    if (!empty($this->buffer)) {
                        $this->processRecord($this->buffer);
                        $this->buffer = [];
                    }
                }

                // Add line to buffer
                $this->buffer[] = $line;
            }

            // Process final buffered record
            if (!empty($this->buffer)) {
                $this->processRecord($this->buffer);
            }

            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Process a single GEDCOM record
     *
     * @param array $lines Record lines
     */
    private function processRecord($lines)
    {
        // First line contains record type
        $parts = preg_split('/\s+/', $lines[0], 3);
        if (count($parts) < 2) {
            return;
        }

        $type = $parts[1];

        // Process based on record type
        switch ($type) {
            case 'INDI':
                $this->processIndividual($lines);
                break;
            case 'FAM':
                $this->processFamily($lines);
                break;
            case 'SOUR':
                $this->processSource($lines);
                break;
            case 'OBJE':
                $this->processMedia($lines);
                break;
            case 'NOTE':
                $this->processNote($lines);
                break;
            case 'REPO':
                $this->processRepository($lines);
                break;
        }
    }

    /**
     * Clean up a directory recursively
     *
     * @param string $dir Directory path
     */
    private function removeDirectory($dir)
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    // @TODO: Implement record processing methods
    private function processIndividual($lines)
    {
        global $wpdb;

        // First line should have ID
        $parts = preg_split('/\s+/', $lines[0], 3);
        $xref = trim($parts[1] ?? '', '@');

        // Initialize individual data
        $individual = [
            'tree_id' => $this->tree_id,
            'uuid' => uniqid('hp-', true), // Simple unique ID generator
            'external_id' => $xref,
            'living' => true, // Default to living unless death info found
            'privacy_level' => 0
        ];

        // Initialize event data
        $birth_event = null;
        $death_event = null;
        $events = [];

        // Initialize name data
        $names = [];
        $current_name = null;

        // Process lines
        $level = null;
        $context = null;
        $date_context = null;

        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', $line, 3);
            if (count($parts) < 2)
                continue;

            list($level, $tag) = $parts;
            $level = (int) $level;
            $value = trim($parts[2] ?? '');

            // Reset context when level drops
            if ($level <= 1) {
                $context = null;
                $date_context = null;
            }

            switch ($tag) {
                case 'NAME':
                    if ($level === 1) {
                        // Parse name parts
                        $name_parts = $this->parseNameParts($value);
                        $current_name = [
                            'individual_id' => null, // Will be set after individual insert
                            'type' => 'primary',
                            'given' => $name_parts['given'] ?? '',
                            'surname' => $name_parts['surname'] ?? '',
                            'prefix' => $name_parts['prefix'] ?? '',
                            'suffix' => $name_parts['suffix'] ?? '',
                            'nickname' => '',
                            'sort_order' => count($names)
                        ];
                        $names[] = $current_name;
                        $context = 'NAME';
                    } elseif ($level === 2 && $context === 'NAME') {
                        // Handle name subtags
                        switch ($value) {
                            case 'NPFX':
                                $current_name['prefix'] = $value;
                                break;
                            case 'GIVN':
                                $current_name['given'] = $value;
                                break;
                            case 'SURN':
                                $current_name['surname'] = $value;
                                break;
                            case 'NSFX':
                                $current_name['suffix'] = $value;
                                break;
                            case 'NICK':
                                $current_name['nickname'] = $value;
                                break;
                        }
                    }
                    break;

                case 'SEX':
                    $individual['gender'] = substr(trim($value), 0, 1);
                    break;

                case 'BIRT':
                case 'DEAT':
                    if ($level === 1) {
                        $context = $tag;
                        if ($tag === 'BIRT') {
                            $birth_event = [
                                'tree_id' => $this->tree_id,
                                'event_type' => 'birth',
                                'date' => null,
                                'place_id' => null
                            ];
                            $events[] = &$birth_event;
                        } else {
                            $death_event = [
                                'tree_id' => $this->tree_id,
                                'event_type' => 'death',
                                'date' => null,
                                'place_id' => null
                            ];
                            $events[] = &$death_event;
                            $individual['living'] = false;
                        }
                    }
                    break;

                case 'DATE':
                    if ($level === 2 && ($context === 'BIRT' || $context === 'DEAT')) {
                        $event = ($context === 'BIRT') ? $birth_event : $death_event;
                        if ($event) {
                            $event['date'] = $this->parseDateValue($value);
                        }
                    }
                    break;

                case 'PLAC':
                    if ($level === 2 && ($context === 'BIRT' || $context === 'DEAT')) {
                        $event = ($context === 'BIRT') ? $birth_event : $death_event;
                        if ($event) {
                            $place_id = $this->getOrCreatePlace($value);
                            if ($place_id) {
                                $event['place_id'] = $place_id;
                            }
                        }
                    }
                    break;

                case '_PRIVACY':
                case 'RESN':
                    if ($level === 1) {
                        $individual['privacy_level'] = $this->parsePrivacyLevel($value);
                    }
                    break;
            }
        }

        // Insert individual record
        $result = $wpdb->insert(
            $wpdb->prefix . 'hp_individuals',
            $individual
        );

        if (!$result) {
            throw new Exception("Failed to insert individual record: " . $wpdb->last_error);
        }

        $individual_id = $wpdb->insert_id;

        // Insert names
        foreach ($names as $name) {
            $name['individual_id'] = $individual_id;
            $wpdb->insert(
                $wpdb->prefix . 'hp_names',
                $name
            );
        }

        // Insert events and their dates
        foreach ($events as $event) {
            if ($event['date'] || $event['place_id']) {
                $event['individual_id'] = $individual_id;                // Store basic event info
                $wpdb->insert(
                    $wpdb->prefix . 'hp_events',
                    [
                        'tree_id' => $event['tree_id'],
                        'individual_id' => $event['individual_id'],
                        'event_type' => $event['event_type'],
                        'place_id' => $event['place_id'],
                        'created_at' => current_time('mysql')
                    ]
                );

                // Store detailed date information if available
                if (!empty($event['date']) && is_array($event['date'])) {
                    $this->storeEventDate($wpdb->insert_id, $event['date']);
                }
            }
        }

        return $individual_id;
    }

    /**
     * Parse name parts from a GEDCOM name string
     * 
     * @param string $name_string GEDCOM name string
     * @return array Name parts
     */
    private function parseNameParts($name_string)
    {
        $parts = [];

        // Remove extra spaces and slashes around surname
        $name_string = trim(preg_replace('/\s+/', ' ', $name_string));

        // Extract surname (between slashes)
        if (preg_match('/\/(.+?)\//', $name_string, $matches)) {
            $parts['surname'] = trim($matches[1]);
            $name_string = trim(str_replace('/' . $matches[1] . '/', '', $name_string));
        } else {
            // No surname delimiters, assume last word is surname
            $words = explode(' ', $name_string);
            if (count($words) > 1) {
                $parts['surname'] = array_pop($words);
                $name_string = implode(' ', $words);
            } else {
                $parts['surname'] = '';
            }
        }

        // Remaining string is given names
        $parts['given'] = trim($name_string);

        // Initialize other parts
        $parts['prefix'] = '';
        $parts['suffix'] = '';

        return $parts;
    }    /**
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
    private function parseDateValue($date_string)
    {
        return $this->date_converter->parseDateValue($date_string);
    }

    /**
     * Get or create a place record
     * 
     * @param string $place_name Place name/hierarchy
     * @return int|null Place ID or null if creation fails
     */
    private function getOrCreatePlace($place_name)
    {
        global $wpdb;        // Look up existing place
        $query = $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}hp_places WHERE name = %s AND tree_id = %d",
            [$place_name, $this->tree_id]
        );
        $place_id = $wpdb->get_var($query);

        if ($place_id) {
            return $place_id;
        }

        // Create new place
        $result = $wpdb->insert(
            $wpdb->prefix . 'hp_places',
            [
                'tree_id' => $this->tree_id,
                'name' => $place_name,
                'created_at' => current_time('mysql')
            ]
        );

        return $result ? $wpdb->insert_id : null;
    }

    /**
     * Parse privacy level from GEDCOM privacy value
     * 
     * @param string $value GEDCOM privacy value
     * @return int Privacy level (0-3)
     */
    private function parsePrivacyLevel($value)
    {
        $value = strtoupper(trim($value));
        switch ($value) {
            case 'CONFIDENTIAL':
            case 'LOCKED':
                return 3; // Maximum privacy
            case 'PRIVACY':
                return 2;
            case 'PRIVATE':
                return 1;
            default:
                return 0; // Public
        }
    }
    private function processFamily($lines)
    {
        global $wpdb;

        // First line should have ID
        $parts = preg_split('/\s+/', $lines[0], 3);
        $xref = trim($parts[1] ?? '', '@');

        // Initialize family data
        $family = [
            'tree_id' => $this->tree_id,
            'uuid' => uniqid('hp-', true),
            'external_id' => $xref,
            'privacy_level' => 0
        ];

        // Initialize family members
        $spouse1_xref = null;
        $spouse2_xref = null;
        $child_xrefs = [];

        // Initialize events
        $marriage_event = null;
        $divorce_event = null;
        $events = [];

        // Process lines
        $level = null;
        $context = null;

        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', $line, 3);
            if (count($parts) < 2)
                continue;

            list($level, $tag) = $parts;
            $level = (int) $level;
            $value = trim($parts[2] ?? '');

            // Reset context when level drops
            if ($level <= 1) {
                $context = null;
            }

            switch ($tag) {
                case 'HUSB':
                    $spouse1_xref = trim($value, '@');
                    break;

                case 'WIFE':
                    $spouse2_xref = trim($value, '@');
                    break;

                case 'CHIL':
                    $child_xrefs[] = trim($value, '@');
                    break;

                case 'MARR':
                    if ($level === 1) {
                        $context = $tag;
                        $marriage_event = [
                            'tree_id' => $this->tree_id,
                            'event_type' => 'marriage',
                            'date' => null,
                            'place_id' => null
                        ];
                        $events[] = &$marriage_event;
                    }
                    break;

                case 'DIV':
                    if ($level === 1) {
                        $context = $tag;
                        $divorce_event = [
                            'tree_id' => $this->tree_id,
                            'event_type' => 'divorce',
                            'date' => null,
                            'place_id' => null
                        ];
                        $events[] = &$divorce_event;
                    }
                    break;

                case 'DATE':
                    if ($level === 2) {
                        $event = ($context === 'MARR') ? $marriage_event :
                            (($context === 'DIV') ? $divorce_event : null);
                        if ($event) {
                            $event['date'] = $this->parseDateValue($value);
                        }
                    }
                    break;

                case 'PLAC':
                    if ($level === 2) {
                        $event = ($context === 'MARR') ? $marriage_event :
                            (($context === 'DIV') ? $divorce_event : null);
                        if ($event) {
                            $place_id = $this->getOrCreatePlace($value);
                            if ($place_id) {
                                $event['place_id'] = $place_id;
                            }
                        }
                    }
                    break;

                case '_PRIVACY':
                case 'RESN':
                    if ($level === 1) {
                        $family['privacy_level'] = $this->parsePrivacyLevel($value);
                    }
                    break;
            }
        }

        // Insert family record
        $result = $wpdb->insert(
            $wpdb->prefix . 'hp_families',
            $family
        );

        if (!$result) {
            throw new Exception("Failed to insert family record: " . $wpdb->last_error);
        }

        $family_id = $wpdb->insert_id;

        // Create family links for spouses and children
        if ($spouse1_xref) {
            $spouse1_id = $this->getIndividualIdByXref($spouse1_xref);
            if ($spouse1_id) {
                $wpdb->insert(
                    $wpdb->prefix . 'hp_family_links',
                    [
                        'family_id' => $family_id,
                        'individual_id' => $spouse1_id,
                        'role' => 'parent1',
                        'relationship_type' => 'biological',
                        'created_at' => current_time('mysql')
                    ]
                );
            }
        }

        if ($spouse2_xref) {
            $spouse2_id = $this->getIndividualIdByXref($spouse2_xref);
            if ($spouse2_id) {
                $wpdb->insert(
                    $wpdb->prefix . 'hp_family_links',
                    [
                        'family_id' => $family_id,
                        'individual_id' => $spouse2_id,
                        'role' => 'parent2',
                        'relationship_type' => 'biological',
                        'created_at' => current_time('mysql')
                    ]
                );
            }
        }

        foreach ($child_xrefs as $child_xref) {
            $child_id = $this->getIndividualIdByXref($child_xref);
            if ($child_id) {
                $wpdb->insert(
                    $wpdb->prefix . 'hp_family_links',
                    [
                        'family_id' => $family_id,
                        'individual_id' => $child_id,
                        'role' => 'child',
                        'relationship_type' => 'biological',
                        'created_at' => current_time('mysql')
                    ]
                );
            }
        }

        // Insert events and their dates
        foreach ($events as $event) {
            if ($event['date'] || $event['place_id']) {
                $event['family_id'] = $family_id;

                // Store basic event info
                $wpdb->insert(
                    $wpdb->prefix . 'hp_events',
                    [
                        'tree_id' => $event['tree_id'],
                        'family_id' => $event['family_id'],
                        'event_type' => $event['event_type'],
                        'place_id' => $event['place_id'],
                        'created_at' => current_time('mysql')
                    ]
                );

                // Store detailed date information if available
                if (is_array($event['date'])) {
                    $this->storeEventDate($wpdb->insert_id, $event['date']);
                }
            }
        }

        return $family_id;
    }

    /**
     * Get individual ID from external reference ID
     * 
     * @param string $xref External reference ID
     * @return int|null Individual ID or null if not found
     */
    private function getIndividualIdByXref($xref)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'hp_individuals';
        // @phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared        // @phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared        $query = $wpdb->prepare(
            "SELECT id FROM $table WHERE external_id = %s AND tree_id = %d",
            [$xref, $this->tree_id]
        );
        return $wpdb->get_var($query);
    }

    /**
     * Process source record
     * 
     * @param array $lines Record lines
     * @return int|null Source ID or null if creation fails
     */
    private function processSource($lines)
    {
        global $wpdb;

        // First line should have ID
        $parts = preg_split('/\s+/', $lines[0], 3);
        $xref = trim($parts[1] ?? '', '@');

        // Initialize source data
        $source = [
            'tree_id' => $this->tree_id,
            'uuid' => uniqid('hp-', true),
            'external_id' => $xref,
            'title' => '',
            'author' => '',
            'publication_info' => '',
            'repository_id' => null,
            'call_number' => '',
            'page_numbers' => '',
            'quality_assessment' => '',
            'privacy_level' => 0,
            'created_at' => current_time('mysql')
        ];

        // Process lines
        $context = null;
        $text_accumulator = '';
        $current_field = null;

        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', $line, 3);
            if (count($parts) < 2) {
                continue;
            }

            list($level, $tag) = $parts;
            $level = (int) $level;
            $value = trim($parts[2] ?? '');

            // Reset context and save accumulated text when level drops
            if ($level <= 1) {
                if ($current_field && !empty($text_accumulator)) {
                    $source[$current_field] = trim($text_accumulator);
                }
                $context = null;
                $current_field = null;
                $text_accumulator = '';
            }

            switch ($tag) {
                case 'TITL':
                    if ($level === 1) {
                        $current_field = 'title';
                        $text_accumulator = $value;
                    }
                    break;

                case 'AUTH':
                    if ($level === 1) {
                        $current_field = 'author';
                        $text_accumulator = $value;
                    }
                    break;

                case 'PUBL':
                    if ($level === 1) {
                        $current_field = 'publication_info';
                        $text_accumulator = $value;
                    }
                    break;

                case 'REPO':
                    if ($level === 1) {
                        $repo_xref = trim($value, '@');
                        $source['repository_id'] = $this->getRepositoryIdByXref($repo_xref);
                    }
                    break;

                case 'CALN':
                    $source['call_number'] = $value;
                    break;

                case 'PAGE':
                    $source['page_numbers'] = $value;
                    break;

                case 'QUAY':
                    // Convert GEDCOM quality assessment (0-3) to descriptive text
                    switch ($value) {
                        case '3':
                            $source['quality_assessment'] = 'Very reliable - primary evidence';
                            break;
                        case '2':
                            $source['quality_assessment'] = 'Reliable - secondary evidence';
                            break;
                        case '1':
                            $source['quality_assessment'] = 'Questionable reliability';
                            break;
                        case '0':
                            $source['quality_assessment'] = 'Unreliable evidence';
                            break;
                    }
                    break;

                case '_PRIVACY':
                case 'RESN':
                    if ($level === 1) {
                        $source['privacy_level'] = $this->parsePrivacyLevel($value);
                    }
                    break;

                case 'CONC':
                    if ($current_field) {
                        $text_accumulator .= $value;
                    }
                    break;

                case 'CONT':
                    if ($current_field) {
                        $text_accumulator .= "\n" . $value;
                    }
                    break;
            }
        }

        // Save any remaining accumulated text
        if ($current_field && !empty($text_accumulator)) {
            $source[$current_field] = trim($text_accumulator);
        }

        // Validate required fields
        if (empty($source['title'])) {
            error_log('HeritagePress: Source record missing required title');
            return null;
        }

        // Insert source
        $result = $wpdb->insert(
            $wpdb->prefix . 'hp_sources',
            $source
        );

        if (!$result) {
            error_log('HeritagePress: Failed to insert source record: ' . $wpdb->last_error);
            return null;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get repository ID from external reference ID
     * 
     * @param string $xref External reference ID
     * @return int|null Repository ID or null if not found
     */    private function getRepositoryIdByXref($xref)
    {
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}hp_repositories WHERE external_id = %s AND tree_id = %d",
            [$xref, $this->tree_id]
        );
        return $wpdb->get_var($query);
    }    /**
     * Compare two dates for sorting
     * 
     * @param array $date1 First date info array
     * @param array $date2 Second date info array
     * @return int -1 if date1 < date2, 0 if equal, 1 if date1 > date2
     */
    private function compareDates($date1, $date2)
    {
        return $this->date_converter->compareDates($date1, $date2);
    }    /**
     * Convert a date to Julian Day Number
     * 
     * @param array $date_info Date info array
     * @return int|null Julian Day Number or null if invalid
     */
    private function dateToJDN($date_info)
    {
        return $this->date_converter->dateToJDN($date_info);
    }

    /**
     * Check if a date falls within a valid range for its calendar
     * 
     * @param array $date_info Date info array
     * @return bool True if date is valid for its calendar
     */
    private function isValidCalendarDate($date_info)
    {
        return $this->date_converter->isValidCalendarDate($date_info);
    }

    /**
     * Store event date information
     * 
     * @param int   $event_id   Event ID
     * @param array $date_info  Date information array
     */
    private function storeEventDate($event_id, $date_info)
    {
        global $wpdb;

        $date_data = [
            'event_id' => $event_id,
            'date' => $date_info['date'],
            'date_end' => $date_info['date_end'],
            'modifier' => $date_info['modifier'],
            'calendar' => $date_info['calendar'],
            'is_range' => $date_info['is_range'] ? 1 : 0,
            'range_end' => $date_info['range_end'],
            'original_text' => $date_info['original'],
            'is_bce' => $date_info['is_bce'] ? 1 : 0,
            'is_season' => $date_info['is_season'] ? 1 : 0,
            'created_at' => current_time('mysql')
        ];

        $wpdb->insert(
            $wpdb->prefix . 'hp_event_dates',
            $date_data
        );
    }

    /**
     * Process media record
     * 
     * @param array $lines Record lines
     * @return int|null Media ID or null if creation fails
     */
    private function processMedia($lines)
    {
        global $wpdb;

        // First line should have ID
        $parts = preg_split('/\s+/', $lines[0], 3);
        $xref = trim($parts[1] ?? '', '@');

        // Initialize media data
        $media = [
            'tree_id' => $this->tree_id,
            'uuid' => uniqid('hp-', true),
            'external_id' => $xref,
            'title' => '',
            'file_path' => '',
            'format' => '',
            'type' => '',
            'description' => '',
            'privacy_level' => 0,
            'created_at' => current_time('mysql')
        ];

        // Process lines
        $context = null;
        $text_accumulator = '';
        $current_field = null;

        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', $line, 3);
            if (count($parts) < 2) {
                continue;
            }

            list($level, $tag) = $parts;
            $level = (int) $level;
            $value = trim($parts[2] ?? '');

            // Reset context and save accumulated text when level drops
            if ($level <= 1) {
                if ($current_field && !empty($text_accumulator)) {
                    $media[$current_field] = trim($text_accumulator);
                }
                $context = null;
                $current_field = null;
                $text_accumulator = '';
            }

            switch ($tag) {
                case 'TITL':
                    if ($level === 1) {
                        $current_field = 'title';
                        $text_accumulator = $value;
                    }
                    break;

                case 'FILE':
                    if ($level === 1) {
                        $media['file_path'] = $value;
                    }
                    break;

                case 'FORM':
                    if ($level === 1) {
                        $media['format'] = $value;
                    }
                    break;

                case 'TYPE':
                    if ($level === 1) {
                        $media['type'] = $value;
                    }
                    break;

                case 'NOTE':
                    if ($level === 1) {
                        $current_field = 'description';
                        $text_accumulator = $value;
                    }
                    break;

                case '_PRIVACY':
                case 'RESN':
                    if ($level === 1) {
                        $media['privacy_level'] = $this->parsePrivacyLevel($value);
                    }
                    break;

                case 'CONC':
                    if ($current_field) {
                        $text_accumulator .= $value;
                    }
                    break;

                case 'CONT':
                    if ($current_field) {
                        $text_accumulator .= "\n" . $value;
                    }
                    break;
            }
        }

        // Save any remaining accumulated text
        if ($current_field && !empty($text_accumulator)) {
            $media[$current_field] = trim($text_accumulator);
        }

        // Validate required fields
        if (empty($media['file_path'])) {
            error_log('HeritagePress: Media record missing required file path');
            return null;
        }

        // Insert media record
        $result = $wpdb->insert(
            $wpdb->prefix . 'hp_media',
            $media
        );

        if (!$result) {
            error_log('HeritagePress: Failed to insert media record: ' . $wpdb->last_error);
            return null;
        }

        return $wpdb->insert_id;
    }

    /**
     * Process note record
     * 
     * @param array $lines Record lines
     * @return int|null Note ID or null if creation fails
     */Normalize media type to standard categories
    private function processNote($lines)
    {* @param string $type GEDCOM media type* @return int|null Note ID or null if creation fails
        global $wpdb; Normalized media type
     */    private function processNote($lines)
        // First line should have ID
        $parts = preg_split('/\s+/', $lines[0], 3);
        $xref = trim($parts[1] ?? '', '@');
        $initial_text = trim($parts[2] ?? '');
        switch ($type) {        $parts = preg_split('/\s+/', $lines[0], 3);
        // Initialize note data '@');
        $note = ['photograph':text = trim($parts[2] ?? '');
            'tree_id' => $this->tree_id,
            'uuid' => uniqid('hp-', true),
            'external_id' => $xref,
            'text' => $initial_text,
            'privacy_level' => 0,,
            'created_at' => current_time('mysql')
        ];      return 'video';  'text' => $initial_text,
                            'privacy_level' => 0,
        // Process lines: => current_time('mysql')
        $text_accumulator = $initial_text;
            case 'recording':
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', $line, 3);
            if (count($parts) < 2) {
                continue;$line) {
            }ase 'certificate':parts = preg_split('/\s+/', $line, 3);
            case 'record':            if (count($parts) < 2) {
            list($level, $tag) = $parts;
            $level = (int) $level;
            $value = trim($parts[2] ?? '');
                return 'other';            list($level, $tag) = $parts;
            switch ($tag) {
                case 'CONC':
                    $text_accumulator .= $value;
                    break;{
     *                 case 'CONC':
                case 'CONT':ecord linescumulator .= $value;
                    $text_accumulator .= "\n" . $value;ls
                    break;
    private function processNote($lines)                case 'CONT':
                case '_PRIVACY':
                case 'RESN':
                    if ($level === 1) {
                        $note['privacy_level'] = $this->parsePrivacyLevel($value);
                    }_split('/\s+/', $lines[0], 3);'RESN':
                    break;s[1] ?? '', '@');evel === 1) {
            }ial_text = trim($parts[2] ?? '');           $note['privacy_level'] = $this->parsePrivacyLevel($value);
        }  }
        // Initialize note data                    break;
        // Save accumulated text
        $note['text'] = trim($text_accumulator);
            'uuid' => uniqid('hp-', true),
        // Validate required fields
        if (empty($note['text'])) {,accumulator);
            error_log('HeritagePress: Note record missing required text content');
            return null; => current_time('mysql')ired fields
        };f (empty($note['text'])) {
            error_log('HeritagePress: Note record missing required text content');
        // Insert note record
        $result = $wpdb->insert(
            $wpdb->prefix . 'hp_notes',
            $note
        );  $parts = preg_split('/\s+/', $line, 3);esult = $wpdb->insert(
            if (count($parts) < 2) {            $wpdb->prefix . 'hp_notes',
        if (!$result) {e;
            error_log('HeritagePress: Failed to insert note record: ' . $wpdb->last_error);
            return null;
        }   list($level, $tag) = $parts;f (!$result) {
            $level = (int) $level;            error_log('HeritagePress: Failed to insert note record: ' . $wpdb->last_error);
        return $wpdb->insert_id;[2] ?? '');
    }
            switch ($tag) {
    /**         case 'CONC': return $wpdb->insert_id;
     * Process repository recordlator .= $value;
     *              break;
     * @param array $lines Record lines
     * @return int|null Repository ID or null if creation fails
     */             $text_accumulator .= "\n" . $value;
    private function processRepository($lines)
    {turn int|null Repository ID or null if creation fails
        global $wpdb;'_PRIVACY':
                case 'RESN':    private function processRepository($lines)
        // First line should have ID) {
        $parts = preg_split('/\s+/', $lines[0], 3);his->parsePrivacyLevel($value);
        $xref = trim($parts[1] ?? '', '@');
                    break;        // First line should have ID
        // Initialize repository data
        $repository = [ '@');
            'tree_id' => $this->tree_id,
            'uuid' => uniqid('hp-', true),
            'external_id' => $xref,accumulator);
            'name' => '',
            'address' => '', fields('hp-', true),
            'phone' => '',ext'])) {=> $xref,
            'email' => '',heritagePress: Note record missing required text content');
            'website' => '',
            'notes' => '',
            'privacy_level' => 0,
            'created_at' => current_time('mysql')
        ];esult = $wpdb->insert(  'notes' => '',
            $wpdb->prefix . 'hp_notes',            'privacy_level' => 0,
        // Process linesrent_time('mysql')
        $context = null;
        $text_accumulator = '';
        $current_field = null;
        $address_lines = [];agePress: Failed to insert note record: ' . $wpdb->last_error);
            return null;        $text_accumulator = '';
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', $line, 3);
            if (count($parts) < 2) {
                continue;
            }split('/\s+/', $line, 3);
    /**            if (count($parts) < 2) {
            list($level, $tag) = $parts;
            $level = (int) $level;
            $value = trim($parts[2] ?? '');
     * @return int|null Repository ID or null if creation fails            list($level, $tag) = $parts;
            // Reset context and save accumulated text when level drops
            if ($level <= 1) {pository($lines)ts[2] ?? '');
                if ($current_field && !empty($text_accumulator)) {
                    $repository[$current_field] = trim($text_accumulator);
                }
                $context = null;e IDld && !empty($text_accumulator)) {
                $current_field = null;
            switch ($tag) {or = '';
                case 'NAME':is->tree_id,
                    if ($level === 1) {e),
                        $current_field = 'name';
                        $text_accumulator = $value;
                    } => '',f ($level === 1) {
                    break;urrent_field = 'name';
            'email' => '',                        $text_accumulator = $value;
                case 'ADDR':
                    if ($level === 1) {
                        $context = $tag;
                    }at' => current_time('mysql')'ADDR':
                    if (!empty($value)) {
                        $address_lines[] = $value;
                    }nes   if (!empty($value)) {
                    break;$address_lines[] = $value;
        $text_accumulator = '';                        }
                case 'ADR1':l;
                case 'ADR2':
                case 'CITY':
                case 'STAE':line) {
                case 'POST':lit('/\s+/', $line, 3);
                case 'CTRY':) < 2) {
                    if ($level === 2 && $context === 'ADDR') {
                        $address_lines[] = $value;
                    }
                    break;tag) = $parts;evel === 2 && $context === 'ADDR' && !empty($value)) {
            $level = (int) $level;                        $address_lines[] = $value;
                case 'PHON':arts[2] ?? '');
                    if ($level === 1) {
                        $repository['phone'] = $value; when level drops
                    }l <= 1) {'PHON':
                    break;nt_field && !empty($text_accumulator)) {evel === 1) {
                    $repository[$current_field] = trim($text_accumulator);                        $repository['phone'] = $value;
                case 'EMAIL':
                    if ($level === 1) {
                        $repository['email'] = $value;
                    }_accumulator = '';'EMAIL':
                    break;
                        $repository['email'] = $value;
                case 'WWW':
                    if ($level === 1) {
                        $repository['website'] = $value;
                    }   $current_field = 'name';'WWW':
                    break;ext_accumulator = $value;evel === 1) {
                    }                        $repository['website'] = $value;
                case 'NOTE':
                    if ($level === 1) {
                        $current_field = 'notes';
                        $text_accumulator = $value;
                    }   $context = $tag;f ($level === 1) {
                    break;t_field = 'notes';
                    if (!empty($value)) {                        $text_accumulator = $value;
                case '_PRIVACY':_lines[] = $value;
                case 'RESN':
                    if ($level === 1) {
                        $repository['privacy_level'] = $this->parsePrivacyLevel($value);
                    }'ADR1':'RESN':
                    break;':evel === 1) {
                case 'CITY':                        $repository['privacy_level'] = $this->parsePrivacyLevel($value);
                case 'CONC':
                    if ($current_field) {
                        $text_accumulator .= $value;
                    }f ($level === 2 && $context === 'ADDR') {'CONC':
                    break;ddress_lines[] = $value;urrent_field) {
                    }                        $text_accumulator .= $value;
                case 'CONT':
                    if ($current_field) {
                        $text_accumulator .= "\n" . $value;
                    }f ($level === 1) {'CONT':
                    break;epository['phone'] = $value;urrent_field) {
            }       }           $text_accumulator .= "\n" . $value;
        }           break;           } elseif ($context === 'ADDR') {
                        $address_lines[] = $value;
        // Save any remaining accumulated text
        if ($current_field && !empty($text_accumulator)) {tor)) {
            $repository[$current_field] = trim($text_accumulator);
        }           }
                    break;
        // Combine address lineslines
        if (!empty($address_lines)) {mulator)) {        if (!empty($address_lines)) {
            $repository['address'] = implode("\n", array_filter($address_lines));
        }               $repository['website'] = $value;
                    }
        // Validate required fieldsequired fields
        if (empty($repository['name'])) {
            error_log('HeritagePress: Repository record missing required name');
            return null;$level === 1) {
        }               $current_field = 'notes';
                        $text_accumulator = $value;        // Validate required fields
        // Insert repository record repository record
        $result = $wpdb->insert(Repository record missing required name');        $result = $wpdb->insert(
            $wpdb->prefix . 'hp_repositories',
            $repositoryPRIVACY':
        );      case 'RESN':
                    if ($level === 1) {        // Insert repository record
        if (!$result) { $repository['privacy_level'] = $this->parsePrivacyLevel($value);->insert( (!$result) {
            error_log('HeritagePress: Failed to insert repository record: ' . $wpdb->last_error);
            return null;k;
        }
                case 'CONC':
        return $wpdb->insert_id;_field) {db->insert_id;
    }                   $text_accumulator .= $value;       error_log('HeritagePress: Failed to insert repository record: ' . $wpdb->last_error);    }
}                   }           return null;
                    break;        }
/**
 * wpdb type definitions to suppress type checking errorsors
 * @method string prepare(string $query, mixed ...$args)
 * @method mixed get_var(string $query = null, int $x = 0, int $y = 0)
 * @method bool|int insert(string $table, array $data, array|string $format = null)
 */                 break;
            } * wpdb type definitions to suppress type checking errors * @method string prepare(string $query, mixed ...$args) * @method mixed get_var(string $query = null, int $x = 0, int $y = 0)








































 */ * @method bool|int insert(string $table, array $data, array|string $format = null) * @method mixed get_var(string $query = null, int $x = 0, int $y = 0) * @method string prepare(string $query, mixed ...$args) * wpdb type definitions to suppress type checking errors/**}    }        return $wpdb->insert_id;        }            return null;            error_log('HeritagePress: Failed to insert repository record: ' . $wpdb->last_error);        if (!$result) {        );            $repository            $wpdb->prefix . 'hp_repositories',        $result = $wpdb->insert(        // Insert repository record        }            return null;            error_log('HeritagePress: Repository record missing required name');        if (empty($repository['name'])) {        // Validate required fields        }            $repository['address'] = implode("\n", array_filter($address_lines));        if (!empty($address_lines)) {        // Combine address lines        }            $repository[$current_field] = trim($text_accumulator);        if ($current_field && !empty($text_accumulator)) {        // Save any remaining accumulated text        } * @method bool|int insert(string $table, array $data, array|string $format = null)
 */
