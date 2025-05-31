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
    private $relationship_handler;
    private $cache;
    private $db_handler;
    private $tree_id;
    private $data = []; 
    private $media_files = [];

    public function __construct($file_path, $db_handler = null) {
        $this->file_path = $file_path;
        $this->validator = new Gedcom7Validator();
        $this->media_handler = new GedcomMediaHandler(dirname($file_path));
        $this->place_handler = new GedcomPlaceHandler();
        $this->recovery_handler = new GedcomRecoveryHandler();
        $this->cache = new \HeritagePress\Core\GedcomCache();
        
        $this->db_handler = ($db_handler instanceof \HeritagePress\Database\GedcomDatabaseHandler) ? $db_handler : null;
        $this->tree_id = null;
    }

    public function wasConverted() {
        return $this->was_converted;
    }

    public function getRecoveryHandler() {
        return $this->recovery_handler;
    }

    public function getPlaceHandler() {
        return $this->place_handler;
    }

    public function parse() {
        if (!file_exists($this->file_path)) {
            throw new \Exception("GEDCOM file not found: " . $this->file_path);
        }

        $content = file_get_contents($this->file_path);
        if ($content === false) {
            throw new \Exception("Could not read GEDCOM file: " . $this->file_path);
        }

        // Remove BOM if present
        $content = $this->removeBOM($content);
        
        // Check GEDCOM version
        $this->detectVersion($content);
        
        // Convert to GEDCOM 7 if necessary
        if ($this->version !== '7.0') {
            $converter = new GedcomConverter();
            $content = $converter->convertToGedcom7($content);
            $this->was_converted = true;
        }

        // Parse the content
        $this->parseContent($content);
        
        return $this->data;
    }

    private function removeBOM($content) {
        $bom = pack('H*','EFBBBF');
        return preg_replace("/^$bom/", '', $content);
    }

    private function detectVersion($content) {
        // Look for version in HEAD.GEDC.VERS
        if (preg_match('/^1 GEDC\s*$/m', $content)) {
            if (preg_match('/^2 VERS (.+)$/m', $content, $matches)) {
                $this->version = trim($matches[1]);
                return;
            }
        }
        
        // Default to 5.5.1 if not found
        $this->version = '5.5.1';
    }

    private function parseContent($content) {
        $lines = explode("\n", $content);
        $this->data = [];
        $this->record_stack = [];
        
        foreach ($lines as $line_number => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            try {
                $parsed = $this->parseLine($line);
                if ($parsed) {
                    $this->processRecord($parsed, $line_number + 1);
                }
            } catch (\Exception $e) {
                if ($this->recovery_handler) {
                    $this->recovery_handler->handleError($e->getMessage(), [
                        'line_number' => $line_number + 1,
                        'line_content' => $line
                    ]);
                }
                echo "Error parsing line " . ($line_number + 1) . ": " . $e->getMessage() . "\n";
            }
        }
    }

    private function parseLine($line) {
        // GEDCOM line format: LEVEL [XREF_ID] TAG [VALUE]
        if (!preg_match('/^(\d+)\s+(@[^@]+@\s+)?([A-Z0-9_]+)(\s+(.*))?$/', $line, $matches)) {
            return null;
        }

        return [
            'level' => (int)$matches[1],
            'xref' => isset($matches[2]) ? trim($matches[2]) : null,
            'tag' => $matches[3],
            'value' => isset($matches[5]) ? $matches[5] : ''
        ];
    }

    private function processRecord($parsed, $line_number) {
        $level = $parsed['level'];
        $tag = $parsed['tag'];
        $value = $parsed['value'];
        $xref = $parsed['xref'];

        // Create record structure
        $record = [
            'level' => $level,
            'tag' => $tag,
            'value' => $value,
            'xref' => $xref,
            'children' => []
        ];

        if ($level === 0) {
            // Top-level record
            $this->current_record = $record;
            $this->data[] = &$this->current_record;
            $this->record_stack = [&$this->current_record];
        } else {
            // Find the correct parent
            $this->adjustStackToLevel($level);
            
            if (!empty($this->record_stack)) {
                $parent = &$this->record_stack[count($this->record_stack) - 1];
                $parent['children'][] = $record;
                
                // Add to stack if this could be a parent
                if ($this->canBeParentTag($tag)) {
                    $this->record_stack[] = &$parent['children'][count($parent['children']) - 1];
                }
            }
        }
    }

    private function adjustStackToLevel($target_level) {
        // Pop stack until we find the appropriate parent level
        while (count($this->record_stack) >= $target_level) {
            array_pop($this->record_stack);
        }
    }

    private function canBeParentTag($tag) {
        $parent_tags = [
            'INDI', 'FAM', 'SOUR', 'REPO', 'OBJE', 'NOTE', 'SUBM', 'SUBN',
            'HEAD', 'TRLR', 'NAME', 'BIRT', 'DEAT', 'MARR', 'DIV', 'ADDR',
            'PLAC', 'DATE', 'GEDC', 'CHAR', 'FILE', 'FORM', 'HUSB', 'WIFE',
            'CHIL', 'FAMS', 'FAMC', 'RESN', 'SEX', 'REFN', 'RIN', 'CHAN',
            'DATA', 'EVEN', 'FACT', 'OCCU', 'RESI', 'EDUC', 'RELI', 'NATI',
            'TITL', 'PUBL', 'TEXT', 'REPO', 'CALN', 'MEDI', 'CONC', 'CONT'
        ];
        
        return in_array($tag, $parent_tags);
    }

    public function saveToDatabase($tree_id = null) {
        if (!$this->db_handler) {
            throw new \Exception("Database handler not available");
        }

        $this->tree_id = $tree_id ?: $this->generateTreeId();
        
        try {
            foreach ($this->data as $record) {
                $this->saveRecord($record);
            }
        } catch (\Exception $e) {
            if ($this->recovery_handler) {
                $this->recovery_handler->handleError($e->getMessage(), [
                    'context' => 'database_save',
                    'tree_id' => $this->tree_id
                ]);
            }
            throw $e;
        }
    }

    private function saveRecord($record) {
        // Implementation depends on your database schema
        // This is a placeholder that should be customized
        if ($this->db_handler) {
            $this->db_handler->saveRecord($record, $this->tree_id);
        }
    }

    private function generateTreeId() {
        return 'tree_' . time() . '_' . rand(1000, 9999);
    }

    public function getValidationErrors() {
        return $this->validation_errors;
    }

    public function getValidationWarnings() {
        return $this->validation_warnings;
    }

    public function getData() {
        return $this->data;
    }

    public function getMediaFiles() {
        return $this->media_files;
    }

    public function validate() {
        if ($this->validator) {
            $result = $this->validator->validate($this->data);
            $this->validation_errors = $result['errors'] ?? [];
            $this->validation_warnings = $result['warnings'] ?? [];
            return count($this->validation_errors) === 0;
        }
        return true;
    }
}

// Placeholder classes that would need to be implemented
class GedcomMediaHandler {
    private $base_path;
    
    public function __construct($base_path) {
        $this->base_path = $base_path;
    }
}

class GedcomPlaceHandler {
    public function __construct() {
        // Initialize place handling
    }
}

class GedcomRecoveryHandler {
    public function __construct() {
        // Initialize recovery handling
    }
    
    public function handleError($message, $context = []) {
        error_log("GEDCOM Recovery: $message - Context: " . json_encode($context));
    }
}
