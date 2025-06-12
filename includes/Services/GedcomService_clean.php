<?php
namespace HeritagePress\Services;

use HeritagePress\Models\DateConverter;
use Exception;

/**
 * GEDCOM Import/Export Service
 */
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
     * @var int Current tree ID
     */
    private $tree_id;

    /**
     * @var array Statistics for import
     */
    private $stats = [
        'individuals' => 0,
        'families' => 0,
        'sources' => 0,
        'notes' => 0,
        'events' => 0,
        'errors' => []
    ];

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
     * @return array Import results with statistics
     */
    public function import($filepath, $tree_id)
    {
        global $wpdb;

        $this->tree_id = $tree_id;
        $this->stats = [
            'individuals' => 0,
            'families' => 0,
            'sources' => 0,
            'notes' => 0,
            'events' => 0,
            'errors' => []
        ];

        try {
            // Validate file exists
            if (!file_exists($filepath)) {
                throw new Exception("GEDCOM file not found: $filepath");
            }

            // Read and validate GEDCOM header
            $lines = $this->read_gedcom_file($filepath);
            if (empty($lines)) {
                throw new Exception("GEDCOM file is empty or unreadable");
            }

            // Parse header to get version
            $this->parse_header($lines);

            // Process GEDCOM records
            $this->process_gedcom_records($lines);

            // Return success with statistics
            return [
                'success' => true,
                'message' => 'GEDCOM import completed successfully',
                'stats' => $this->stats
            ];

        } catch (Exception $e) {
            error_log("GEDCOM Import Error: " . $e->getMessage());
            $this->stats['errors'][] = $e->getMessage();

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'stats' => $this->stats
            ];
        }
    }

    /**
     * Read GEDCOM file into array of lines
     *
     * @param string $filepath
     * @return array
     */
    private function read_gedcom_file($filepath)
    {
        $content = file_get_contents($filepath);
        if ($content === false) {
            throw new Exception("Failed to read GEDCOM file");
        }

        // Handle different line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);

        // Remove empty lines at the end
        while (end($lines) === '') {
            array_pop($lines);
        }

        return $lines;
    }

    /**
     * Parse GEDCOM header
     *
     * @param array $lines
     */
    private function parse_header($lines)
    {
        $header_found = false;

        foreach ($lines as $line) {
            if (preg_match('/^0 HEAD/', $line)) {
                $header_found = true;
                continue;
            }

            if ($header_found && preg_match('/^1 GEDC/', $line)) {
                continue;
            }

            if ($header_found && preg_match('/^2 VERS (.+)/', $line, $matches)) {
                $this->current_version = trim($matches[1]);
                break;
            }

            // Stop at first non-header record
            if ($header_found && preg_match('/^0 @/', $line)) {
                break;
            }
        }

        // Default to 5.5.1 if no version found
        if (empty($this->current_version)) {
            $this->current_version = '5.5.1';
        }

        error_log("GEDCOM version detected: " . $this->current_version);
    }

    /**
     * Process GEDCOM records
     *
     * @param array $lines
     */
    private function process_gedcom_records($lines)
    {
        $current_record = null;
        $record_lines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Parse level and tag
            if (!preg_match('/^(\d+)\s+(.*)/', $line, $matches)) {
                continue;
            }

            $level = (int) $matches[1];
            $content = $matches[2];

            // Level 0 indicates a new record
            if ($level === 0) {
                // Process previous record if exists
                if ($current_record !== null && !empty($record_lines)) {
                    $this->process_record($current_record, $record_lines);
                }

                // Start new record
                if (preg_match('/^@(.+)@\s+(.+)/', $content, $record_matches)) {
                    $current_record = [
                        'id' => $record_matches[1],
                        'type' => $record_matches[2]
                    ];
                    $record_lines = [$line];
                } else {
                    $current_record = null;
                    $record_lines = [];
                }
            } else {
                // Add to current record
                if ($current_record !== null) {
                    $record_lines[] = $line;
                }
            }
        }

        // Process last record
        if ($current_record !== null && !empty($record_lines)) {
            $this->process_record($current_record, $record_lines);
        }
    }

    /**
     * Process individual GEDCOM record
     *
     * @param array $record
     * @param array $lines
     */
    private function process_record($record, $lines)
    {
        try {
            switch ($record['type']) {
                case 'INDI':
                    $this->process_individual($record, $lines);
                    break;
                case 'FAM':
                    $this->process_family($record, $lines);
                    break;
                case 'SOUR':
                    $this->process_source($record, $lines);
                    break;
                case 'NOTE':
                    $this->process_note($record, $lines);
                    break;
                case 'REPO':
                    $this->process_repository($record, $lines);
                    break;
                case 'OBJE':
                    $this->process_media($record, $lines);
                    break;
                default:
                    // Skip unsupported record types
                    break;
            }
        } catch (Exception $e) {
            $this->stats['errors'][] = "Error processing {$record['type']} {$record['id']}: " . $e->getMessage();
            error_log("GEDCOM Record Error: " . $e->getMessage());
        }
    }

    /**
     * Process individual record
     *
     * @param array $record
     * @param array $lines
     */
    private function process_individual($record, $lines)
    {
        global $wpdb;

        $individual_data = [
            'tree_id' => $this->tree_id,
            'uuid' => $this->generate_uuid(),
            'external_id' => $record['id'],
            'gender' => 'U', // Unknown by default
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $names = [];

        foreach ($lines as $line) {
            if (preg_match('/^1 NAME (.+)/', $line, $matches)) {
                $names[] = $this->parse_name($matches[1]);
            } elseif (preg_match('/^1 SEX ([MF])/', $line, $matches)) {
                $individual_data['gender'] = $matches[1];
            }
        }

        // Insert individual
        $table = $wpdb->prefix . 'hp_individuals';

        // Set primary name
        if (!empty($names)) {
            $primary_name = $names[0];
            $individual_data['given_names'] = $primary_name['given'];
            $individual_data['surname'] = $primary_name['surname'];
        }

        $result = $wpdb->insert($table, $individual_data);

        if ($result === false) {
            throw new Exception("Failed to insert individual: " . $wpdb->last_error);
        }

        $individual_id = $wpdb->insert_id;
        $this->stats['individuals']++;

        error_log("Processed individual: {$record['id']} -> ID: $individual_id");
    }

    /**
     * Process family record
     *
     * @param array $record
     * @param array $lines
     */
    private function process_family($record, $lines)
    {
        global $wpdb;

        $family_data = [
            'tree_id' => $this->tree_id,
            'uuid' => $this->generate_uuid(),
            'external_id' => $record['id'],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        foreach ($lines as $line) {
            if (preg_match('/^1 HUSB @(.+)@/', $line, $matches)) {
                $family_data['husband_id'] = $this->get_individual_id_by_external($matches[1]);
            } elseif (preg_match('/^1 WIFE @(.+)@/', $line, $matches)) {
                $family_data['wife_id'] = $this->get_individual_id_by_external($matches[1]);
            }
        }

        // Insert family
        $table = $wpdb->prefix . 'hp_families';
        $result = $wpdb->insert($table, $family_data);

        if ($result === false) {
            throw new Exception("Failed to insert family: " . $wpdb->last_error);
        }

        $family_id = $wpdb->insert_id;
        $this->stats['families']++;

        error_log("Processed family: {$record['id']} -> ID: $family_id");
    }

    /**
     * Process source record
     *
     * @param array $record
     * @param array $lines
     */
    private function process_source($record, $lines)
    {
        global $wpdb;

        $source_data = [
            'tree_id' => $this->tree_id,
            'uuid' => $this->generate_uuid(),
            'external_id' => $record['id'],
            'title' => '',
            'author' => '',
            'publication_info' => '',
            'call_number' => '',
            'privacy_level' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        foreach ($lines as $line) {
            if (preg_match('/^1 TITL (.+)/', $line, $matches)) {
                $source_data['title'] = $matches[1];
            } elseif (preg_match('/^1 AUTH (.+)/', $line, $matches)) {
                $source_data['author'] = $matches[1];
            } elseif (preg_match('/^1 PUBL (.+)/', $line, $matches)) {
                $source_data['publication_info'] = $matches[1];
            } elseif (preg_match('/^1 REPO @(.+)@/', $line, $matches)) {
                // Find repository by external_id and get its internal ID
                $query = $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}hp_repositories WHERE external_id = %s AND tree_id = %d",
                    $matches[1],
                    $this->tree_id
                );
                $repo_id = $wpdb->get_var($query);
                if ($repo_id) {
                    $source_data['repository_id'] = $repo_id;
                }
            }
        }

        // Insert source
        $table = $wpdb->prefix . 'hp_sources';
        $result = $wpdb->insert($table, $source_data);

        if ($result === false) {
            throw new Exception("Failed to insert source: " . $wpdb->last_error);
        }

        $this->stats['sources']++;
    }

    /**
     * Process note record
     *
     * @param array $record
     * @param array $lines
     */
    private function process_note($record, $lines)
    {
        global $wpdb;

        $note_text = '';
        foreach ($lines as $line) {
            if (preg_match('/^1 CONT (.+)/', $line, $matches)) {
                $note_text .= $matches[1] . "\n";
            } elseif (preg_match('/^1 CONC (.+)/', $line, $matches)) {
                $note_text .= $matches[1];
            }
        }

        $note_data = [
            'tree_id' => $this->tree_id,
            'uuid' => $this->generate_uuid(),
            'external_id' => $record['id'],
            'content' => trim($note_text),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        // Insert note
        $table = $wpdb->prefix . 'hp_notes';
        $result = $wpdb->insert($table, $note_data);

        if ($result === false) {
            throw new Exception("Failed to insert note: " . $wpdb->last_error);
        }

        $this->stats['notes']++;
    }

    /**
     * Process repository record
     *
     * @param array $record
     * @param array $lines
     */
    private function process_repository($record, $lines)
    {
        global $wpdb;

        $repository_data = [
            'tree_id' => $this->tree_id,
            'uuid' => $this->generate_uuid(),
            'external_id' => $record['id'],
            'name' => '',
            'address' => '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        foreach ($lines as $line) {
            if (preg_match('/^1 NAME (.+)/', $line, $matches)) {
                $repository_data['name'] = $matches[1];
            } elseif (preg_match('/^1 ADDR (.+)/', $line, $matches)) {
                $repository_data['address'] = $matches[1];
            }
        }

        // Insert repository
        $table = $wpdb->prefix . 'hp_repositories';
        $result = $wpdb->insert($table, $repository_data);

        if ($result === false) {
            throw new Exception("Failed to insert repository: " . $wpdb->last_error);
        }

        // Update stats (add repositories to stats tracking)
        if (!isset($this->stats['repositories'])) {
            $this->stats['repositories'] = 0;
        }
        $this->stats['repositories']++;
    }

    /**
     * Process media object record
     *
     * @param array $record
     * @param array $lines
     */
    private function process_media($record, $lines)
    {
        global $wpdb;

        $media_data = [
            'tree_id' => $this->tree_id,
            'uuid' => $this->generate_uuid(),
            'external_id' => $record['id'],
            'title' => '',
            'filename' => '',
            'file_path' => '',
            'mime_type' => '',
            'file_size' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        foreach ($lines as $line) {
            if (preg_match('/^1 FILE (.+)/', $line, $matches)) {
                $file_path = $matches[1];
                $media_data['file_path'] = $file_path;
                $media_data['filename'] = basename($file_path);

                // Try to determine MIME type from extension
                $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                $mime_types = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'pdf' => 'application/pdf',
                    'txt' => 'text/plain'
                ];
                if (isset($mime_types[$extension])) {
                    $media_data['mime_type'] = $mime_types[$extension];
                }
            } elseif (preg_match('/^1 TITL (.+)/', $line, $matches)) {
                $media_data['title'] = $matches[1];
            } elseif (preg_match('/^2 FORM (.+)/', $line, $matches)) {
                // GEDCOM format field - can help determine MIME type
                $format = strtolower($matches[1]);
                if ($format === 'jpeg' || $format === 'jpg') {
                    $media_data['mime_type'] = 'image/jpeg';
                } elseif ($format === 'png') {
                    $media_data['mime_type'] = 'image/png';
                } elseif ($format === 'gif') {
                    $media_data['mime_type'] = 'image/gif';
                } elseif ($format === 'pdf') {
                    $media_data['mime_type'] = 'application/pdf';
                }
            }
        }

        // If no title provided, use filename without extension
        if (empty($media_data['title']) && !empty($media_data['filename'])) {
            $media_data['title'] = pathinfo($media_data['filename'], PATHINFO_FILENAME);
        }

        // Insert media
        $table = $wpdb->prefix . 'hp_media';
        $result = $wpdb->insert($table, $media_data);

        if ($result === false) {
            throw new Exception("Failed to insert media: " . $wpdb->last_error);
        }

        // Update stats (add media to stats tracking)
        if (!isset($this->stats['media'])) {
            $this->stats['media'] = 0;
        }
        $this->stats['media']++;
    }

    /**
     * Parse GEDCOM name field
     *
     * @param string $name_string
     * @return array
     */
    private function parse_name($name_string)
    {
        // Handle name format: Given names /Surname/
        if (preg_match('/^(.+?)\s*\/(.+?)\/$/', $name_string, $matches)) {
            return [
                'given' => trim($matches[1]),
                'surname' => trim($matches[2]),
                'full' => trim(str_replace(['/', '\\'], '', $name_string))
            ];
        }

        // Fallback for names without surname markers
        $parts = explode(' ', trim($name_string));
        $surname = array_pop($parts);
        $given = implode(' ', $parts);

        return [
            'given' => $given,
            'surname' => $surname,
            'full' => $name_string
        ];
    }

    /**
     * Get individual ID by external ID
     *
     * @param string $external_id
     * @return int|null
     */
    private function get_individual_id_by_external($external_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'hp_individuals';
        $query = $wpdb->prepare(
            "SELECT id FROM $table WHERE external_id = %s AND tree_id = %d",
            $external_id,
            $this->tree_id
        );
        $id = $wpdb->get_var($query);

        return $id ? (int) $id : null;
    }

    /**
     * Generate UUID
     *
     * @return string
     */
    private function generate_uuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Export to GEDCOM format
     *
     * @param int $tree_id
     * @return string
     */
    public function export($tree_id)
    {
        global $wpdb;

        $gedcom = "0 HEAD\n";
        $gedcom .= "1 SOUR HeritagePress\n";
        $gedcom .= "1 GEDC\n";
        $gedcom .= "2 VERS 5.5.1\n";
        $gedcom .= "1 CHAR UTF-8\n";
        $gedcom .= "1 DATE " . date('d M Y') . "\n";

        // Export individuals
        $individuals_table = $wpdb->prefix . 'hp_individuals';
        $query = $wpdb->prepare(
            "SELECT * FROM $individuals_table WHERE tree_id = %d ORDER BY id",
            $tree_id
        );
        $individuals = $wpdb->get_results($query);

        foreach ($individuals as $individual) {
            $gedcom .= $this->export_individual($individual);
        }

        // Export families
        $families_table = $wpdb->prefix . 'hp_families';
        $query = $wpdb->prepare(
            "SELECT * FROM $families_table WHERE tree_id = %d ORDER BY id",
            $tree_id
        );
        $families = $wpdb->get_results($query);

        foreach ($families as $family) {
            $gedcom .= $this->export_family($family);
        }

        $gedcom .= "0 TRLR\n";

        return $gedcom;
    }

    /**
     * Export individual to GEDCOM format
     *
     * @param object $individual
     * @return string
     */
    private function export_individual($individual)
    {
        $gedcom = "0 @I{$individual->id}@ INDI\n";

        if (!empty($individual->given_names) || !empty($individual->surname)) {
            $name = trim($individual->given_names . ' /' . $individual->surname . '/');
            $gedcom .= "1 NAME $name\n";
        }

        if (!empty($individual->gender)) {
            $gedcom .= "1 SEX {$individual->gender}\n";
        }

        return $gedcom;
    }

    /**
     * Export family to GEDCOM format
     *
     * @param object $family
     * @return string
     */
    private function export_family($family)
    {
        $gedcom = "0 @F{$family->id}@ FAM\n";

        if (!empty($family->husband_id)) {
            $gedcom .= "1 HUSB @I{$family->husband_id}@\n";
        }

        if (!empty($family->wife_id)) {
            $gedcom .= "1 WIFE @I{$family->wife_id}@\n";
        }

        return $gedcom;
    }

    /**
     * Get import statistics
     *
     * @return array
     */
    public function getStats()
    {
        return $this->stats;
    }
}
