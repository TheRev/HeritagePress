<?php
namespace HeritagePress\GEDCOM;

use HeritagePress\GEDCOM\GedcomConverter;
use HeritagePress\GEDCOM\GedcomParser;
use HeritagePress\GEDCOM\Gedcom7Validator;
use HeritagePress\Database\GedcomDatabaseHandler;

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
    private $relationship_handler; // Assuming this was intended to be used or declared
    private $cache;
    private $db_handler;
    private $tree_id;
    private $data = []; 
    private $media_files = [];

    // Modified constructor to accept an optional db_handler
    public function __construct($file_path, $db_handler = null) {
        $this->file_path = $file_path;
        $this->validator = new Gedcom7Validator();
        $this->media_handler = new GedcomMediaHandler(dirname($file_path)); // Assumes GedcomMediaHandler exists
        $this->place_handler = new GedcomPlaceHandler(); // Assumes GedcomPlaceHandler exists
        $this->recovery_handler = new GedcomRecoveryHandler(); // Assumes GedcomRecoveryHandler exists
        $this->cache = new \HeritagePress\Core\GedcomCache(); // Assumes GedcomCache exists
        
        // Use the provided db_handler if it's an instance of GedcomDatabaseHandler, otherwise, it can be null or a default.
        // For CLI tests, we might pass null. For regular operation, it might instantiate its own or receive one.
        $this->db_handler = ($db_handler instanceof \HeritagePress\Database\GedcomDatabaseHandler) ? $db_handler : null;
        // If you want a default instantiation when null is not explicitly passed for other use cases:
        // if ($db_handler === null && !defined('PHPUNIT_TEST_SUITE')) { // Example condition
        //     $this->db_handler = new GedcomDatabaseHandler(); 
        // } else {
        //     $this->db_handler = $db_handler;
        // }
        
        $this->tree_id = null;
    }

    public function wasConverted() {
        return $this->was_converted;
    }

    public function getVersion() {
        return $this->version;
    }

    public function getRecoveryHandler() {
        return $this->recovery_handler;
    }

    public function getPlaceHandler() { // Added this method
        return $this->place_handler;
    }

    public function parse() {
        echo "DEBUG: Gedcom7Parser::parse() method ENTERED!\n";
        // Check cache first
        $cache_key = md5_file($this->file_path);
        // $cached_data = $this->cache->get($cache_key); // Temporarily commented out to force cache miss
        $cached_data = null; // Force cache miss for debugging
        if ($cached_data) {
            echo "DEBUG: Cache HIT! Returning cached data.\n"; 
            $this->data = $cached_data; 
            return $cached_data;
        }
        echo "DEBUG: Cache MISS. Proceeding with parse.\n";

        // Trigger before parse event
        \HeritagePress\Core\GedcomEvents::trigger('gedcom_before_parse', [
            'file' => $this->file_path
        ]);

        if (!file_exists($this->file_path)) {
            throw new \Exception('GEDCOM file not found: ' . $this->file_path);
        }

        $handle = $this->openGedcomFile();
        echo "DEBUG: openGedcomFile() called. Handle: " . ($handle ? 'valid' : 'invalid') . "\n"; // ADDED FOR DEBUGGING
        
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
        echo "DEBUG: Entering first pass loop to check GEDCOM version.\n"; // ADDED FOR DEBUGGING
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

        echo "DEBUG: isGedcom7 = " . ($isGedcom7 ? 'true' : 'false') . "\n"; // ADDED FOR DEBUGGING

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
                if (!$parsed) {
                    // error_log("DEBUG: Failed to parse line: " . $line);
                    echo "DEBUG: Failed to parse line: " . $line . "\n"; // Ensure this echoes
                    continue;
                }
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

        // Store in database only if a db_handler is available
        if ($this->db_handler) {
            try {
                $this->db_handler->storeGedcomData($data);
            } catch (\Exception $e) {
                if ($this->recovery_handler) {
                    $this->recovery_handler->handleError('Database storage failed: ' . $e->getMessage());
                }
            }
        }

        $this->data = $data;

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
        if (preg_match('/^(\\d+)\\s+(?:(@[^@]*@)\\s+)?(\\w+)(?:\\s+(.*))?$/', $line, $matches)) {
            return [
                'level' => (int)$matches[1],
                'xref' => isset($matches[2]) ? trim($matches[2], '@') : '',
                'tag' => $matches[3], // $matches[3] (tag) is non-optional in regex, should always be set
                'value' => isset($matches[4]) ? trim($matches[4]) : ''
            ];
        }
        return null; // Ensure it returns null if preg_match fails or is not present
    }

    /**
     * Process a record
     */
    private function processRecord($parsed, &$data) {
        $parsedTagForDebug = $parsed['tag'] ?? 'TAG_MISSING_IN_PARSED';
        $parsedValForDebug = $parsed['value'] ?? 'VALUE_MISSING_IN_PARSED';
        $parsedLvlForDebug = $parsed['level'] ?? 'LVL_MISSING_IN_PARSED';
        echo "DEBUG: Entered processRecord. Tag: " . $parsedTagForDebug . ", Level: " . $parsedLvlForDebug . ", Value: " . $parsedValForDebug . "\\n"; // ADDED FOR DEBUGGING

        try {
            // Check for version in header
            if ($parsed['tag'] === 'GEDC') { // Assuming $parsed['tag'] is safe
                return;
            }

            if ($this->current_record && isset($this->current_record['type']) && $this->current_record['type'] === 'HEAD' && $parsed['tag'] === 'VERS') {
                $this->version = $parsed['value']; // Assuming $parsed['value'] is safe
                return;
            }

            // Start of a new record
            if ($parsed['level'] === 0) {
                if ($this->current_record) {
                    $this->saveCurrentRecord($data);
                }

                $this->current_record = [
                    'type' => $parsed['tag'], // Assuming $parsed['tag'] is safe
                    'id' => $parsed['xref'],   // Assuming $parsed['xref'] is safe
                    'data' => []
                ];
                $this->record_stack = []; // Reset stack for new top-level record
                return;
            }

            if (!$this->current_record) { // Should not happen after a level 0
                echo "DEBUG: processRecord called with no current_record for level " . $parsedLvlForDebug . "\\n";
                return;
            }
            
            $data_point = [
                'level' => $parsed['level'],
                'tag' => $parsed['tag'],
                'value' => $parsed['value']
            ];

            $currentIdForDebug = $this->current_record['id'] ?? 'UNKNOWN_ID';
            $currentTypeForDebug = $this->current_record['type'] ?? 'UNKNOWN_TYPE';

            if ($currentTypeForDebug === 'INDI') {
                $dpTagInit = $data_point['tag'] ?? 'DP_TAG_MISSING';
                $dpValInit = $data_point['value'] ?? 'DP_VAL_MISSING';
                $dpLvlInit = $data_point['level'] ?? 'DP_LVL_MISSING';
                echo "DEBUG INDI (" . $currentIdForDebug . "): Initial data_point: Tag=" . $dpTagInit . ", Value=" . $dpValInit . ", Level=" . $dpLvlInit . "\\n";
            }

            // Special handling for different types of data
            // These handlers must ensure they return/modify $data_point to have 'level', 'tag', 'value'
            switch ($parsed['tag']) { // Use $parsed['tag'] as $data_point['tag'] might be changed by a handler
                case 'PLAC':
                    $data_point = $this->place_handler->handlePlace($data_point);
                    break;
                case 'DATE':
                    $this->validateAndRecoverDate($data_point); // Modifies by reference
                    break;
                case 'NAME':
                    $this->validateAndRecoverName($data_point); // Modifies by reference
                    break;
            }
            
            $dpTagAfterHandlers = $data_point['tag'] ?? 'TAG_MISSING_POST_HANDLER';
            $dpValAfterHandlers = $data_point['value'] ?? 'VAL_MISSING_POST_HANDLER';
            $dpLvlAfterHandlers = $data_point['level'] ?? 'LVL_MISSING_POST_HANDLER';

            if ($currentTypeForDebug === 'INDI') {
                 echo "DEBUG INDI (" . $currentIdForDebug . "): Processing data_point (post-handlers): Tag=" . $dpTagAfterHandlers . ", Value=" . $dpValAfterHandlers . ", Level=" . $dpLvlAfterHandlers . "\\n";
            }

            // Manage stack: Pop elements from stack that are at the same or higher level
            while (!empty($this->record_stack)) {
                $parent_on_stack_peek = $this->record_stack[count($this->record_stack)-1];
                if (isset($parent_on_stack_peek['level']) && $parent_on_stack_peek['level'] >= $parsed['level']) {
                    array_pop($this->record_stack);
                } else {
                    break; 
                }
            }

            if (empty($this->record_stack)) {
                // This item is a direct child of current_record (i.e., a level 1 tag)
                if ($parsed['level'] == 1) {
                    $this->current_record['data'][] = $data_point;
                    if ($currentTypeForDebug === 'INDI') {
                         echo "DEBUG INDI (" . $currentIdForDebug . "): Added to current_record['data']: Tag=" . $dpTagAfterHandlers . ", Value=" . $dpValAfterHandlers . "\\n";
                    }
                    if ($this->canBeParentTag($dpTagAfterHandlers)) {
                        $this->record_stack[] = &$this->current_record['data'][count($this->current_record['data'])-1];
                    }
                } else {
                    // This is an orphaned item: level > 1 but stack is empty.
                    if ($currentTypeForDebug === 'INDI') {
                        echo "DEBUG INDI (" . $currentIdForDebug . "): SKIPPED adding (orphaned, stack empty, level > 1): Tag=" . $dpTagAfterHandlers . ", Value=" . $dpValAfterHandlers . ", Level=" . $dpLvlAfterHandlers . "\\n";
                    }
                }
            } else {
                // Stack is not empty, add as child to item at top of stack
                $current_parent_on_stack = &$this->record_stack[count($this->record_stack)-1];
                $parentTagForDebug = $current_parent_on_stack['tag'] ?? 'PARENT_TAG_MISSING';
                $parentLvlForDebug = $current_parent_on_stack['level'] ?? 'PARENT_LVL_MISSING';
                
                // Ensure the current item's level is greater than its parent's level
                if (isset($data_point['level']) && isset($current_parent_on_stack['level']) && $data_point['level'] > $current_parent_on_stack['level']) {
                    if (!isset($current_parent_on_stack['children'])) {
                        $current_parent_on_stack['children'] = [];
                    }
                    $current_parent_on_stack['children'][] = $data_point;
                    if ($currentTypeForDebug === 'INDI') {
                        echo "DEBUG INDI (" . $currentIdForDebug . "): Added as child to " . $parentTagForDebug . ": Tag=" . $dpTagAfterHandlers . ", Value=" . $dpValAfterHandlers . "\\n";
                    }
                    if ($this->canBeParentTag($dpTagAfterHandlers)) {
                         $this->record_stack[] = &$current_parent_on_stack['children'][count($current_parent_on_stack['children'])-1];
                    }
                } else {
                     // Level is not strictly greater than parent's level on stack. GEDCOM structure issue or stack logic flaw.
                    if ($currentTypeForDebug === 'INDI') {
                        echo "DEBUG INDI (" . $currentIdForDebug . "): Stack/level issue. Parent: " . $parentTagForDebug . "@L" . $parentLvlForDebug . ". Current: " . $dpTagAfterHandlers . "@L" . $dpLvlAfterHandlers . "\\n";
                    }
                }
            }
        } catch (\\Exception $e) { // Changed Exception to \Exception
            if ($this->recovery_handler) {
                $this->recovery_handler->handleError($e->getMessage(), [
                    'line' => $parsed, // $parsed might be incomplete if error is in its creation
                    'context' => $this->current_record
                ]);
            }
            // Optionally rethrow or log critical errors
            // error_log("Critical error in processRecord: " . $e->getMessage());
            echo "CRITICAL ERROR in processRecord: " . $e->getMessage() . " for line: Tag=" . ($parsed['tag'] ?? 'N/A') . ", Value=" . ($parsed['value'] ?? 'N/A') . "\\n";
        }
    }

    /**
     * Save the current record
     */
    private function saveCurrentRecord(&$data) {
        if (!$this->current_record) return;

        try {
            switch ($this->current_record['type']) {
                // OBJE case for media is handled first by media_handler
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
                // The duplicate OBJE case was here and is now removed.
                case 'NOTE':
                    $data['notes'][] = $this->current_record;
                    break;
                case '_PLAC': // Assuming _PLAC is a custom or specific tag to be treated like PLAC
                case 'PLAC':
                    $data['places'][] = $this->current_record;
                    break;
                // case 'HEAD': // Header is usually processed differently, not typically saved as a separate record here
                // default: // Optional: handle unknown top-level tags if necessary
                //     $this->recovery_handler->handleWarning("Unknown top-level record type: {$this->current_record['type']}");
                //     break;
            }
        } catch (\Exception $e) {
            if ($this->recovery_handler) {
                 $this->recovery_handler->handleError($e->getMessage(), [
                    'record' => $this->current_record
                ]);
            }
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
    private function validateAndRecoverName(&$name_data) {
        // TODO: Implement name validation and recovery
    }

    // Helper function to determine if a tag can be a parent in the stack
    private function canBeParentTag($tag) {
        if ($tag === null || $tag === 'TAG_MISSING_POST_HANDLER') return false; 
    
        $tag = strtoupper($tag); // Ensure case-insensitivity
    
        // Common structural tags that often have children
        $structuralTags = [
            'NAME', 'PLAC', // Can have detailed sub-structures
            'NOTE', 'SOUR', 'OBJE' // Can have their own sub-records or detailed content
        ];
    
        // Event tags (most can have DATE, PLAC, SOUR, NOTE, etc.)
        $eventTags = [
            'ADOP', 'ANUL', 'BAPM', 'BARM', 'BASM', 'BIRT', 'BLES', 'BURI', 
            'CAST', 'CENS', 'CHRA', 'CONF', 'CREM', 'DEAT', 'DIV', 'DIVF', 
            'EDUC', 'EMIG', 'ENGA', 'EVEN', 'FACT', 'FCOM', 'GRAD', 'IDNO', 
            'IMMI', 'LANG', 'MARB', 'MARC', 'MARL', 'MARR', 'MARS', 'MEDI', 
            'NATI', 'NATU', 'NCHI', 'NMR', 'OCCU', 'ORDN', 'PROB', 'PROP', 
            'RELI', 'RESI', 'RESN', 'RETI', 'SLGC', 'SLGS', 'TITL', 'WILL'
            // Some of these might be attributes rather than events but can have children.
        ];
    
        // Record linkage tags
        $linkageTags = ['FAMC', 'FAMS', 'ASSO'];
    
        // Top-level record types that can appear as SOURce records or have complex structures
        // when referenced or embedded (though usually handled by their own record processing)
        // For stack purposes, if they appear as a sub-tag (e.g. 1 SOUR then 2 REPO), REPO might be a parent.
        $recordTypeTags = ['REPO', 'SUBM']; // HEAD is handled at level 0 mostly.
    
        if (in_array($tag, $structuralTags)) return true;
        if (in_array($tag, $eventTags)) return true;
        if (in_array($tag, $linkageTags)) return true;
        if (in_array($tag, $recordTypeTags)) return true;
    
        return false; // Default to not being a parent for stack purposes if not in lists
    }    
}
