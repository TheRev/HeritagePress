<?php
namespace HeritagePress\GEDCOM;

use HeritagePress\GEDCOM\GedcomConverter;
use HeritagePress\GEDCOM\GedcomParser;
use HeritagePress\GEDCOM\Gedcom7Validator;

class Gedcom7Parser {
    private $file_path;
    private $version;
    private $current_record = null;
    private $record_stack = [];
    private $was_converted = false;
    private $validator;
    private $validation_errors = [];
    private $validation_warnings = [];
    private $media_handler;
    private $place_handler;
    private $recovery_handler;
    private $cache;
    private $db_handler;

    public function __construct($file_path) {
        $this->file_path = $file_path;
        $this->validator = new Gedcom7Validator();
        $this->media_handler = new GedcomMediaHandler(dirname($file_path));
        $this->place_handler = new GedcomPlaceHandler();
        $this->recovery_handler = new GedcomRecoveryHandler();
        $this->cache = new \HeritagePress\Core\GedcomCache();
        $this->db_handler = new \HeritagePress\Database\GedcomDatabaseHandler();
    }

    public function wasConverted() {
        return $this->was_converted;
    }

    public function parse() {
        // Check cache first
        $cache_key = md5_file($this->file_path);
        $cached_data = $this->cache->get($cache_key);
        if ($cached_data) {
            return $cached_data;
        }

        // Trigger before parse event
        \HeritagePress\Core\GedcomEvents::trigger('gedcom_before_parse', [
            'file' => $this->file_path
        ]);

        if (!file_exists($this->file_path)) {
            throw new \Exception('GEDCOM file not found: ' . $this->file_path);
        }

        $handle = $this->openGedcomFile();
        
        if (!$handle) {
            throw new \Exception('Could not open GEDCOM file');
        }

        $data = [
            'header' => [],
            'individuals' => [],
            'families' => [],
            'sources' => [],
            'places' => [],
            'repositories' => [],
            'media' => [],
            'notes' => []
        ];

        // First pass: check version
        $isGedcom7 = false;
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $parsed = $this->parseLine($line);
            if (!$parsed) continue;

            if ($parsed['tag'] === 'GEDC') {
                // Look for version in next line
                $next_line = fgets($handle);
                if ($next_line) {
                    $next_parsed = $this->parseLine(trim($next_line));
                    if ($next_parsed && $next_parsed['tag'] === 'VERS') {
                        $this->version = $next_parsed['value'];
                        if (version_compare($this->version, '7.0', '>=')) {
                            $isGedcom7 = true;
                        }
                        break;
                    }
                }
            }
        }

        // Rewind file
        rewind($handle);

        if (!$isGedcom7) {
            // For GEDCOM 5.5.1 or earlier, use the old parser and convert
            $this->was_converted = true;
            $oldParser = new GedcomParser($this->file_path);
            $oldData = $oldParser->parse();
            
            // Convert to GEDCOM 7.0
            $converter = new GedcomConverter($oldData);
            $data = $converter->convert();
            $this->version = '7.0'; // Updated version after conversion
        } else {
            // Parse as GEDCOM 7.0
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if (empty($line)) continue;
                
                $parsed = $this->parseLine($line);
                if (!$parsed) continue;

                $this->processRecord($parsed, $data);
            }
        }

        fclose($handle);

        // Process any remaining record
        if ($this->current_record) {
            $this->saveCurrentRecord($data);
        }

        // Validate the data
        $this->validator->validate($data);
        $this->validation_errors = $this->validator->getErrors();
        $this->validation_warnings = $this->validator->getWarnings();

        // Store in database
        try {
            $this->db_handler->storeGedcomData($data);
        } catch (\Exception $e) {
            $this->recovery_handler->handleError('Database storage failed: ' . $e->getMessage());
        }

        // Cache the results
        $this->cache->set($cache_key, $data);

        // Trigger after parse event
        \HeritagePress\Core\GedcomEvents::trigger('gedcom_after_parse', [
            'file' => $this->file_path,
            'data' => $data
        ]);

        return $data;
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors() {
        return $this->validation_errors;
    }

    /**
     * Get validation warnings
     */
    public function getValidationWarnings() {
        return $this->validation_warnings;
    }

    private function openGedcomFile() {
        // Handle GEDCOM 7.0 ZIP format (.gdz)
        if (strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)) === 'gdz') {
            if (!class_exists('ZipArchive')) {
                throw new \Exception('ZIP support is required for GEDCOM 7.0 files');
            }

            $zip = new \ZipArchive();
            if ($zip->open($this->file_path) !== true) {
                throw new \Exception('Failed to open GDZ file');
            }

            // First look for tree.ged
            $gedIndex = $zip->locateName('tree.ged');
            if ($gedIndex === false) {
                // Look for any .ged file
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    if (pathinfo($filename, PATHINFO_EXTENSION) === 'ged') {
                        $gedIndex = $i;
                        break;
                    }
                }
            }

            if ($gedIndex === false) {
                $zip->close();
                throw new \Exception('No GEDCOM file found in GDZ archive');
            }

            // Store media files information
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (preg_match('/\.(jpe?g|png|gif|bmp|pdf|doc|docx)$/i', $filename)) {
                    $this->media_files[] = $filename;
                }
            }

            $contents = $zip->getFromIndex($gedIndex);
            $zip->close();

            $handle = fopen('php://memory', 'r+');
            fwrite($handle, $contents);
            rewind($handle);
            return $handle;
        }

        // Regular GEDCOM file
        return fopen($this->file_path, 'r');
    }

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
     * Process a record
     */
    private function processRecord($parsed, &$data) {
        try {
            // Check for version in header
            if ($parsed['tag'] === 'GEDC') {
                return;
            }

            if ($this->current_record && $this->current_record['type'] === 'HEAD' && $parsed['tag'] === 'VERS') {
                $this->version = $parsed['value'];
                return;
            }

            // Start of a new record
            if ($parsed['level'] === 0) {
                if ($this->current_record) {
                    $this->saveCurrentRecord($data);
                }

                $this->current_record = [
                    'type' => $parsed['tag'],
                    'id' => $parsed['xref'],
                    'data' => []
                ];
                $this->record_stack = [];
                return;
            }

            // Process data based on type
            if ($this->current_record) {
                $data_point = [
                    'level' => $parsed['level'],
                    'tag' => $parsed['tag'],
                    'value' => $parsed['value']
                ];

                // Special handling for different types of data
                switch ($parsed['tag']) {
                    case 'PLAC':
                        $data_point = $this->place_handler->handlePlace($data_point);
                        break;
                    case 'DATE':
                        $this->validateAndRecoverDate($data_point);
                        break;
                    case 'NAME':
                        $this->validateAndRecoverName($data_point);
                        break;
                }

                // Add to current record
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
        } catch (\Exception $e) {
            $this->recovery_handler->handleError($e->getMessage(), [
                'line' => $parsed,
                'context' => $this->current_record
            ]);
        }
    }

    /**
     * Save the current record
     */
    private function saveCurrentRecord(&$data) {
        if (!$this->current_record) return;

        try {
            switch ($this->current_record['type']) {
                case 'OBJE':
                    $media = $this->media_handler->handleMedia($this->current_record);
                    if ($media) {
                        $data['media'][] = $media;
                    }
                    break;
                case 'INDI':
                    $data['individuals'][] = $this->current_record;
                    break;
                case 'FAM':
                    $data['families'][] = $this->current_record;
                    break;
                case 'SOUR':
                    $data['sources'][] = $this->current_record;
                    break;
                case 'REPO':
                    $data['repositories'][] = $this->current_record;
                    break;
                case 'OBJE':
                    $data['media'][] = $this->current_record;
                    break;
                case 'NOTE':
                    $data['notes'][] = $this->current_record;
                    break;
                case '_PLAC':
                case 'PLAC':
                    $data['places'][] = $this->current_record;
                    break;
            }
        } catch (\Exception $e) {
            $this->recovery_handler->handleError($e->getMessage(), [
                'record' => $this->current_record
            ]);
        }
    }

    /**
     * Validate and recover date format
     */
    private function validateAndRecoverDate(&$data_point) {
        if (!empty($data_point['value'])) {
            try {
                // Basic date validation
                if (!$this->validator->validateDate($data_point['value'])) {
                    $recovered = $this->recovery_handler->handleError(
                        'Invalid date format',
                        ['type' => 'date', 'value' => $data_point['value']]
                    );
                    if ($recovered) {
                        $data_point['value'] = $recovered;
                    }
                }
            } catch (\Exception $e) {
                $this->recovery_handler->handleWarning($e->getMessage(), [
                    'date' => $data_point['value']
                ]);
            }
        }
    }

    /**
     * Validate and recover name format
     */
    private function validateAndRecoverName(&$data_point) {
        if (!empty($data_point['value'])) {
            try {
                if (!$this->validator->validateName($data_point['value'])) {
                    $recovered = $this->recovery_handler->handleError(
                        'Invalid name format',
                        ['type' => 'name', 'value' => $data_point['value']]
                    );
                    if ($recovered) {
                        $data_point['value'] = $recovered;
                    }
                }
            } catch (\Exception $e) {
                $this->recovery_handler->handleWarning($e->getMessage(), [
                    'name' => $data_point['value']
                ]);
            }
        }
    }

    /**
     * Get recovery handler
     */
    public function getRecoveryHandler() {
        return $this->recovery_handler;
    }

    /**
     * Get place handler
     */
    public function getPlaceHandler() {
        return $this->place_handler;
    }

    /**
     * Get media handler
     */
    public function getMediaHandler() {
        return $this->media_handler;
    }

    /**
     * Export to GEDCOM format
     */
    public function export($target_version = '7.0') {
        $exporter = new GedcomExportHandler($this->data, $target_version);
        return $exporter->export();
    }

    public function getVersion() {
        return $this->version;
    }

    public function getMediaFiles() {
        return $this->media_files;
    }
}
