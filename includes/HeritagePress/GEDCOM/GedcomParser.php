<?php
/**
 * GEDCOM Parser Class
 *
 * @package HeritagePress
 */

namespace HeritagePress\GEDCOM;

class GedcomParser {
    private $file_path;
    private $version;
    private $current_record = null;
    private $current_data = [];
    private $record_stack = [];

    /**
     * Constructor
     *
     * @param string $file_path Path to GEDCOM file
     */
    public function __construct($file_path) {
        $this->file_path = $file_path;
    }

    /**
     * Get GEDCOM version
     *
     * @return string GEDCOM version
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * Parse GEDCOM file
     *
     * @return array Parsed data
     */
    public function parse() {
        if (!file_exists($this->file_path)) {
            throw new \Exception("GEDCOM file not found: {$this->file_path}");
        }

        $handle = fopen($this->file_path, 'r');
        if (!$handle) {
            throw new \Exception('Could not open GEDCOM file');
        }

        echo "Starting to parse GEDCOM file: {$this->file_path}\n";

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;

            $parsed = $this->parseLine($line);
            if (!$parsed) continue;

            $this->processRecord($parsed);
        }

        fclose($handle);

        // Save last record if exists
        if ($this->current_record) {
            $this->saveCurrentRecord();
        }

        echo "GEDCOM Version detected: {$this->version}\n";

        return $this->current_data;
    }

    /**
     * Parse a GEDCOM line into components
     */
    private function parseLine($line) {
        if (preg_match('/^(\d+)\s+(?:(@[^@]*@)\s+)?(\w+)(?:\s+(.*))?$/', $line, $matches)) {
            return [
                'level' => (int)$matches[1],
                'xref' => isset($matches[2]) ? trim($matches[2], '@') : '',
                'tag' => $matches[3],
                'value' => isset($matches[4]) ? trim($matches[4]) : ''
            ];
        }
        return null;
    }

    /**
     * Process a GEDCOM record
     */
    private function processRecord($parsed) {
        // Check for version in header
        if ($parsed['tag'] === 'GEDC') {
            // Version will be in next line
            return;
        }

        if ($this->current_record && $this->current_record['type'] === 'HEAD' && $parsed['tag'] === 'VERS') {
            $this->version = $parsed['value'];
            return;
        }

        // Start of a new record
        if ($parsed['level'] === 0) {
            // Save previous record if exists
            if ($this->current_record) {
                $this->saveCurrentRecord();
            }

            // Start new record
            $this->current_record = [
                'type' => $parsed['tag'],
                'id' => $parsed['xref'],
                'data' => []
            ];
            $this->record_stack = [];
            return;
        }

        // Add data to current record
        if ($this->current_record) {
            while (!empty($this->record_stack) && $this->record_stack[count($this->record_stack)-1]['level'] >= $parsed['level']) {
                array_pop($this->record_stack);
            }

            $data_point = [
                'level' => $parsed['level'],
                'tag' => $parsed['tag'],
                'value' => $parsed['value']
            ];

            if (empty($this->record_stack)) {
                $this->current_record['data'][] = $data_point;
            } else {
                $current = &$this->record_stack[count($this->record_stack)-1];
                if (!isset($current['children'])) {
                    $current['children'] = [];
                }
                $current['children'][] = $data_point;
            }

            $this->record_stack[] = &$data_point;
        }
    }

    /**
     * Save the current record to the appropriate category
     */
    private function saveCurrentRecord() {
        if (!$this->current_record) return;

        $type = $this->current_record['type'];
        if (!isset($this->current_data[$type])) {
            $this->current_data[$type] = [];
        }
        $this->current_data[$type][] = $this->current_record;
    }
}
