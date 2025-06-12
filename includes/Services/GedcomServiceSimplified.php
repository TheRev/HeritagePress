<?php
namespace HeritagePress\Services;

use HeritagePress\Models\DateConverter;
use Exception;

/**
 * Simplified GEDCOM Import Service - Direct Database Mapping
 * Based on proven genealogy software patterns for successful GEDCOM import
 */
class GedcomServiceSimplified
{
    private $wpdb;
    private $date_converter;
    private $tree_id;
    private $gedcom_id;

    /**
     * Import statistics
     */
    private $stats = [
        'people' => 0,
        'families' => 0,
        'sources' => 0,
        'repositories' => 0,
        'notes' => 0,
        'media' => 0,
        'errors' => []
    ];

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->date_converter = new DateConverter();
    }

    /**
     * Import GEDCOM file with direct table mapping
     */
    public function import($file_path, $tree_id)
    {
        $this->tree_id = $tree_id;
        $this->gedcom_id = 'tree_' . $tree_id;

        try {
            // Parse GEDCOM into records
            $records = $this->parse_gedcom($file_path);

            if (empty($records)) {
                throw new Exception('No valid GEDCOM records found');
            }

            // Process each record type
            foreach ($records as $record) {
                switch ($record['type']) {
                    case 'INDI':
                        $this->import_individual($record);
                        break;
                    case 'FAM':
                        $this->import_family($record);
                        break;
                    case 'SOUR':
                        $this->import_source($record);
                        break;
                    case 'REPO':
                        $this->import_repository($record);
                        break;
                    case 'NOTE':
                        $this->import_note($record);
                        break;
                    case 'OBJE':
                        $this->import_media($record);
                        break;
                }
            }

            return [
                'success' => true,
                'stats' => $this->stats,
                'message' => 'GEDCOM import completed successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $this->stats
            ];
        }
    }

    /**
     * Parse GEDCOM file into structured records
     */
    private function parse_gedcom($file_path)
    {
        if (!file_exists($file_path)) {
            throw new Exception('GEDCOM file not found: ' . $file_path);
        }

        $content = file_get_contents($file_path);

        // Remove BOM if present
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        $lines = explode("\n", $content);
        $records = [];
        $current_record = null;

        foreach ($lines as $line) {
            $line = rtrim($line, "\r\n");

            if (empty($line))
                continue;

            // Parse GEDCOM line level
            if (!preg_match('/^(\d+)\s+(.*)/', $line, $matches)) {
                continue;
            }

            $level = (int) $matches[1];
            $rest = $matches[2];

            if ($level === 0) {
                // Save previous record
                if ($current_record) {
                    $records[] = $current_record;
                }

                // Start new record
                if (preg_match('/^(@.+@)\s+(\w+)/', $rest, $record_matches)) {
                    $current_record = [
                        'id' => $record_matches[1],
                        'type' => $record_matches[2],
                        'data' => [$line]
                    ];
                } else {
                    $current_record = null;
                }
            } elseif ($current_record) {
                $current_record['data'][] = $line;
            }
        }

        // Add final record
        if ($current_record) {
            $records[] = $current_record;
        }

        return $records;
    }    /**
         * Import individual person record
         */
    private function import_individual($record)
    {
        $data = [
            'gedcom' => $this->gedcom_id,
            'personID' => $record['id'], // Use TNG column name
            'lnprefix' => '', // TNG has this field
            'lastname' => '',
            'firstname' => '',
            'birthdate' => '',
            'birthdatetr' => null, // TNG column name
            'sex' => '',
            'birthplace' => '',
            'deathdate' => '',
            'deathdatetr' => null, // TNG column name
            'deathplace' => '',
            'nickname' => '',
            'title' => '',
            'prefix' => '',
            'suffix' => '',
            'living' => 1,
            'private' => 0,
            'changedate' => current_time('mysql'), // TNG column name
            'changedby' => 'GEDCOM Import'
        ];

        foreach ($record['data'] as $line) {
            if (preg_match('/^1 NAME (.+)/', $line, $matches)) {
                $name = $this->parse_name($matches[1]);
                $data['firstname'] = $name['given'];
                $data['lastname'] = $name['surname'];
                $data['prefix'] = $name['prefix'];
                $data['suffix'] = $name['suffix'];
            } elseif (preg_match('/^1 SEX ([MF])/', $line, $matches)) {
                $data['sex'] = $matches[1];
            } elseif (preg_match('/^1 BIRT/', $line)) {
                $this->extract_event_data($record['data'], $line, 'BIRT', $data);
            } elseif (preg_match('/^1 DEAT/', $line)) {
                $this->extract_event_data($record['data'], $line, 'DEAT', $data);
                $data['living'] = 0;
            } elseif (preg_match('/^2 NICK (.+)/', $line, $matches)) {
                $data['nickname'] = trim($matches[1]);
            }
        }

        // Insert into people table
        $result = $this->wpdb->replace(
            $this->wpdb->prefix . 'hp_people',
            $data
        );

        if ($result !== false) {
            $this->stats['people']++;
        } else {
            $this->stats['errors'][] = 'Failed to import person: ' . $record['id'];
        }
    }

    /**
     * Extract event data (birth/death) from GEDCOM
     */
    private function extract_event_data($lines, $event_line, $event_type, &$data)
    {
        $in_event = false;
        $event_index = array_search($event_line, $lines);

        if ($event_index === false)
            return;

        for ($i = $event_index + 1; $i < count($lines); $i++) {
            $line = $lines[$i];

            // Exit if we hit another level 1 tag
            if (preg_match('/^1 /', $line)) {
                break;
            }
            if (preg_match('/^2 DATE (.+)/', $line, $matches)) {
                $date_str = trim($matches[1]);
                if ($event_type === 'BIRT') {
                    $data['birthdate'] = $date_str;
                    $data['birthdatetr'] = $this->parse_date($date_str); // Changed from birthdate_parsed
                } elseif ($event_type === 'DEAT') {
                    $data['deathdate'] = $date_str;
                    $data['deathdatetr'] = $this->parse_date($date_str); // Changed from deathdate_parsed
                }
            } elseif (preg_match('/^2 PLAC (.+)/', $line, $matches)) {
                if ($event_type === 'BIRT') {
                    $data['birthplace'] = trim($matches[1]);
                } elseif ($event_type === 'DEAT') {
                    $data['deathplace'] = trim($matches[1]);
                }
            }
        }
    }

    /**
     * Import family record
     */
    private function import_family($record)
    {
        $data = [
            'gedcom' => $this->gedcom_id,
            'familyID' => $record['id'], // Use TNG column name
            'husband' => '',
            'wife' => '',
            'marrdate' => '', // TNG column name
            'marrdatetr' => null, // TNG column name
            'marrplace' => '', // TNG column name
            'divdate' => '', // TNG column name
            'divdatetr' => null, // TNG column name
            'divplace' => '', // TNG column name
            'status' => '',
            'living' => 1,
            'private' => 0,
            'changedate' => current_time('mysql'), // TNG column name
            'changedby' => 'GEDCOM Import'
        ];

        $children = [];

        foreach ($record['data'] as $line) {
            if (preg_match('/^1 HUSB (@.+@)/', $line, $matches)) {
                $data['husband'] = $matches[1];
            } elseif (preg_match('/^1 WIFE (@.+@)/', $line, $matches)) {
                $data['wife'] = $matches[1];
            } elseif (preg_match('/^1 CHIL (@.+@)/', $line, $matches)) {
                $children[] = $matches[1];
            } elseif (preg_match('/^1 MARR/', $line)) {
                $this->extract_marriage_data($record['data'], $line, $data);
            } elseif (preg_match('/^1 DIV/', $line)) {
                $this->extract_divorce_data($record['data'], $line, $data);
            }
        }

        // Insert family record
        $result = $this->wpdb->replace(
            $this->wpdb->prefix . 'hp_families',
            $data
        );

        if ($result !== false) {
            $this->stats['families']++;

            // Insert children relationships
            foreach ($children as $i => $child_id) {
                $this->wpdb->replace(
                    $this->wpdb->prefix . 'hp_children',
                    [
                        'gedcom' => $this->gedcom_id,
                        'familyID' => $record['id'], // Use TNG column name
                        'personID' => $child_id, // Use TNG column name
                        'ordernum' => $i + 1
                    ]
                );
            }
        } else {
            $this->stats['errors'][] = 'Failed to import family: ' . $record['id'];
        }
    }

    /**
     * Extract marriage event data
     */
    private function extract_marriage_data($lines, $event_line, &$data)
    {
        $event_index = array_search($event_line, $lines);
        if ($event_index === false)
            return;

        for ($i = $event_index + 1; $i < count($lines); $i++) {
            $line = $lines[$i];

            if (preg_match('/^1 /', $line))
                break;
            if (preg_match('/^2 DATE (.+)/', $line, $matches)) {
                $data['marrdate'] = trim($matches[1]); // Changed from marriage_date
                $data['marrdatetr'] = $this->parse_date($matches[1]); // Changed from marriage_date_parsed
            } elseif (preg_match('/^2 PLAC (.+)/', $line, $matches)) {
                $data['marrplace'] = trim($matches[1]); // Changed from marriage_place
            }
        }
    }

    /**
     * Extract divorce event data
     */
    private function extract_divorce_data($lines, $event_line, &$data)
    {
        $event_index = array_search($event_line, $lines);
        if ($event_index === false)
            return;

        for ($i = $event_index + 1; $i < count($lines); $i++) {
            $line = $lines[$i];

            if (preg_match('/^1 /', $line))
                break;
            if (preg_match('/^2 DATE (.+)/', $line, $matches)) {
                $data['divdate'] = trim($matches[1]); // Changed from divorce_date
                $data['divdatetr'] = $this->parse_date($matches[1]); // Changed from divorce_date_parsed
            } elseif (preg_match('/^2 PLAC (.+)/', $line, $matches)) {
                $data['divplace'] = trim($matches[1]); // Changed from divorce_place
            }
        }
    }

    /**
     * Import source record
     */
    private function import_source($record)
    {
        $data = [
            'gedcom' => $this->gedcom_id,
            'sourceID' => $record['id'], // Use TNG column name
            'title' => '',
            'author' => '',
            'publisher' => '',
            'callnum' => '', // TNG column name
            'repoID' => '', // TNG column name instead of repository_id
            'comments' => '' // TNG uses comments instead of note
        ];

        foreach ($record['data'] as $line) {
            if (preg_match('/^1 TITL (.+)/', $line, $matches)) {
                $data['title'] = trim($matches[1]);
            } elseif (preg_match('/^1 AUTH (.+)/', $line, $matches)) {
                $data['author'] = trim($matches[1]);
            } elseif (preg_match('/^1 PUBL (.+)/', $line, $matches)) {
                $data['publisher'] = trim($matches[1]);
            } elseif (preg_match('/^1 REPO (@.+@)/', $line, $matches)) {
                $data['repoID'] = $matches[1]; // Use TNG column name
            }
        }

        $result = $this->wpdb->replace(
            $this->wpdb->prefix . 'hp_sources',
            $data
        );

        if ($result !== false) {
            $this->stats['sources']++;
        }
    }

    /**
     * Import repository record
     */
    private function import_repository($record)
    {
        $data = [
            'gedcom' => $this->gedcom_id,
            'repoID' => $record['id'], // Use TNG column name
            'reponame' => '', // Use TNG column name
            'addressID' => 0, // TNG uses separate address table
            'changedate' => current_time('mysql'),
            'changedby' => 'GEDCOM Import'
        ];

        foreach ($record['data'] as $line) {
            if (preg_match('/^1 NAME (.+)/', $line, $matches)) {
                $data['reponame'] = trim($matches[1]); // Use TNG column name
            }
            // Skip address details - TNG uses separate address table
            // We'll just import the repository name for now
        }

        $result = $this->wpdb->replace(
            $this->wpdb->prefix . 'hp_repositories',
            $data
        );

        if ($result !== false) {
            $this->stats['repositories']++;
        }
    }

    /**
     * Import note record
     */
    private function import_note($record)
    {
        $text = '';

        foreach ($record['data'] as $line) {
            if (preg_match('/^1 CONC (.+)/', $line, $matches)) {
                $text .= trim($matches[1]);
            } elseif (preg_match('/^1 CONT (.+)/', $line, $matches)) {
                $text .= "\n" . trim($matches[1]);
            } elseif (preg_match('/^0 @.+@ NOTE (.+)/', $line, $matches)) {
                $text = trim($matches[1]);
            }
        }

        $data = [
            'gedcom' => $this->gedcom_id,
            'note_id' => $record['id'],
            'text' => $text,
            'private' => 0
        ];

        $result = $this->wpdb->replace(
            $this->wpdb->prefix . 'hp_notes',
            $data
        );

        if ($result !== false) {
            $this->stats['notes']++;
        }
    }

    /**
     * Import media record
     */
    private function import_media($record)
    {
        $data = [
            'gedcom' => $this->gedcom_id,
            'mediakey' => $record['id'], // TNG uses mediakey instead of media_id
            'path' => '', // TNG uses path instead of mediafile
            'description' => '',
            'mediatypeID' => '', // TNG column name
            'notes' => '',
            'changedate' => current_time('mysql'),
            'changedby' => 'GEDCOM Import'
        ];

        foreach ($record['data'] as $line) {
            if (preg_match('/^1 FILE (.+)/', $line, $matches)) {
                $data['path'] = trim($matches[1]); // TNG uses path
            } elseif (preg_match('/^1 TITL (.+)/', $line, $matches)) {
                $data['description'] = trim($matches[1]); // Use description for title
            } elseif (preg_match('/^2 FORM (.+)/', $line, $matches)) {
                $data['mediatypeID'] = trim($matches[1]); // TNG column name
            }
        }

        $result = $this->wpdb->replace(
            $this->wpdb->prefix . 'hp_media',
            $data
        );

        if ($result !== false) {
            $this->stats['media']++;
        }
    }

    /**
     * Parse GEDCOM name format
     */
    private function parse_name($name_string)
    {
        $result = [
            'given' => '',
            'surname' => '',
            'prefix' => '',
            'suffix' => ''
        ];

        // Handle surname in slashes /SURNAME/
        if (preg_match('/^(.+?)\s*\/(.+?)\/\s*(.*)$/', $name_string, $matches)) {
            $result['given'] = trim($matches[1]);
            $result['surname'] = trim($matches[2]);
            $result['suffix'] = trim($matches[3]);
        } else {
            // No surname delimiters, treat as given name
            $result['given'] = trim($name_string);
        }

        return $result;
    }

    /**
     * Parse GEDCOM date to MySQL date format
     */
    private function parse_date($date_string)
    {
        if (empty($date_string)) {
            return null;
        }
        try {
            return $this->date_converter->parseDateValue($date_string);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get import statistics
     */
    public function getStats()
    {
        return $this->stats;
    }
}
