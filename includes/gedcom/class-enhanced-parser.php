<?php
/**
 * Heritage Press - Phase 2 Enhanced GEDCOM Parser
 * Improved GEDCOM 7.0 parser with progress tracking and error recovery
 * 
 * @package HeritagePress
 * @version 2.0.0
 */

namespace HeritagePress\GEDCOM;

class EnhancedParser {
    
    private $progress_callback;
    private $error_callback;
    private $batch_size = 100;
    private $memory_limit;
    private $errors = [];
    private $warnings = [];
    private $stats = [
        'individuals' => 0,
        'families' => 0,
        'sources' => 0,
        'total_lines' => 0,
        'processed_lines' => 0
    ];
    
    public function __construct($options = []) {
        $this->batch_size = $options['batch_size'] ?? 100;
        $this->memory_limit = $options['memory_limit'] ?? (64 * 1024 * 1024); // 64MB
        $this->progress_callback = $options['progress_callback'] ?? null;
        $this->error_callback = $options['error_callback'] ?? null;
    }
    
    /**
     * Parse GEDCOM file with enhanced features
     */
    public function parse_file($file_path, $options = []) {
        $this->reset_stats();
        
        if (!file_exists($file_path)) {
            throw new \Exception("GEDCOM file not found: $file_path");
        }
        
        // Validate file encoding and format
        $this->validate_file($file_path);
        
        // Get total line count for progress tracking
        $this->stats['total_lines'] = $this->count_file_lines($file_path);
        
        // Parse in chunks to manage memory
        return $this->parse_in_chunks($file_path, $options);
    }
    
    /**
     * Parse GEDCOM file in memory-efficient chunks
     */
    private function parse_in_chunks($file_path, $options) {
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new \Exception("Cannot open GEDCOM file: $file_path");
        }
        
        $current_record = null;
        $line_number = 0;
        $batch_records = [];
        
        while (($line = fgets($handle)) !== false) {
            $line_number++;
            $this->stats['processed_lines']++;
            
            // Update progress
            if ($this->progress_callback && $line_number % 100 === 0) {
                $progress = ($this->stats['processed_lines'] / $this->stats['total_lines']) * 100;
                call_user_func($this->progress_callback, $progress, $this->stats);
            }
            
            // Parse line
            $parsed_line = $this->parse_gedcom_line($line, $line_number);
            if (!$parsed_line) continue;
            
            // Handle record boundaries
            if ($parsed_line['level'] === 0) {
                // Save previous record if exists
                if ($current_record) {
                    $batch_records[] = $current_record;
                    
                    // Process batch if it reaches size limit
                    if (count($batch_records) >= $this->batch_size) {
                        $this->process_record_batch($batch_records);
                        $batch_records = [];
                        
                        // Check memory usage
                        $this->check_memory_usage();
                    }
                }
                
                // Start new record
                $current_record = [
                    'type' => $parsed_line['tag'],
                    'id' => $parsed_line['value'],
                    'line_number' => $line_number,
                    'data' => []
                ];
            } else {
                // Add to current record
                if ($current_record) {
                    $this->add_line_to_record($current_record, $parsed_line);
                }
            }
        }
        
        // Process final record and batch
        if ($current_record) {
            $batch_records[] = $current_record;
        }
        if (!empty($batch_records)) {
            $this->process_record_batch($batch_records);
        }
        
        fclose($handle);
        
        return [
            'success' => true,
            'stats' => $this->stats,
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }
    
    /**
     * Enhanced GEDCOM line parsing with error handling
     */
    private function parse_gedcom_line($line, $line_number) {
        $line = trim($line);
        if (empty($line)) return null;
        
        // Enhanced regex for GEDCOM 7.0 compliance
        if (!preg_match('/^(\d+)\s+(?:(@[^@]+@)\s+)?([A-Z_]+)(?:\s+(.*))?$/', $line, $matches)) {
            $this->add_error("Invalid GEDCOM line format", $line_number, $line);
            return null;
        }
        
        $level = (int)$matches[1];
        $xref = isset($matches[2]) ? trim($matches[2], '@') : null;
        $tag = $matches[3];
        $value = isset($matches[4]) ? $this->decode_gedcom_value($matches[4]) : '';
        
        return [
            'level' => $level,
            'xref' => $xref,
            'tag' => $tag,
            'value' => $value,
            'line_number' => $line_number,
            'raw_line' => $line
        ];
    }
    
    /**
     * Process a batch of records efficiently
     */
    private function process_record_batch($records) {
        global $wpdb;
        
        $individuals = [];
        $families = [];
        $sources = [];
        
        foreach ($records as $record) {
            try {
                switch ($record['type']) {
                    case 'INDI':
                        $individuals[] = $this->process_individual_record($record);
                        $this->stats['individuals']++;
                        break;
                        
                    case 'FAM':
                        $families[] = $this->process_family_record($record);
                        $this->stats['families']++;
                        break;
                        
                    case 'SOUR':
                        $sources[] = $this->process_source_record($record);
                        $this->stats['sources']++;
                        break;
                        
                    default:
                        // Handle other record types or skip
                        break;
                }
            } catch (\Exception $e) {
                $this->add_error("Error processing {$record['type']} record: " . $e->getMessage(), 
                               $record['line_number'], $record['id']);
            }
        }
        
        // Batch insert records
        $this->batch_insert_individuals($individuals);
        $this->batch_insert_families($families);
        $this->batch_insert_sources($sources);
    }
    
    /**
     * Enhanced individual record processing
     */
    private function process_individual_record($record) {
        $individual = [
            'gedcom_id' => $record['id'],
            'first_name' => '',
            'last_name' => '',
            'birth_date' => null,
            'death_date' => null,
            'birth_place' => '',
            'death_place' => '',
            'gender' => '',
            'notes' => '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        foreach ($record['data'] as $line) {
            switch ($line['tag']) {
                case 'NAME':
                    $this->parse_name_value($line['value'], $individual);
                    break;
                    
                case 'SEX':
                    $individual['gender'] = strtoupper($line['value']);
                    break;
                    
                case 'BIRT':
                    $this->parse_event_data($line, $individual, 'birth');
                    break;
                    
                case 'DEAT':
                    $this->parse_event_data($line, $individual, 'death');
                    break;
                    
                case 'NOTE':
                    $individual['notes'] .= $line['value'] . "\n";
                    break;
            }
        }
        
        return $individual;
    }
    
    /**
     * Enhanced name parsing for GEDCOM 7.0
     */
    private function parse_name_value($name_value, &$individual) {
        // Handle GEDCOM 7.0 name structure
        if (preg_match('/^([^\/]*)\s*\/([^\/]*)\/', $name_value, $matches)) {
            $individual['first_name'] = trim($matches[1]);
            $individual['last_name'] = trim($matches[2]);
        } else {
            // Fallback for non-standard formats
            $parts = explode(' ', trim($name_value));
            if (count($parts) > 1) {
                $individual['last_name'] = array_pop($parts);
                $individual['first_name'] = implode(' ', $parts);
            } else {
                $individual['first_name'] = $name_value;
            }
        }
    }
    
    /**
     * Batch insert individuals with error handling
     */
    private function batch_insert_individuals($individuals) {
        if (empty($individuals)) return;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'heritage_individuals';
        
        foreach ($individuals as $individual) {
            $result = $wpdb->insert($table_name, $individual);
            if ($result === false) {
                $this->add_error("Failed to insert individual: " . $individual['gedcom_id'], 
                               0, $wpdb->last_error);
            }
        }
    }
    
    /**
     * Enhanced file validation
     */
    private function validate_file($file_path) {
        // Check file size
        $file_size = filesize($file_path);
        if ($file_size > 50 * 1024 * 1024) { // 50MB limit
            $this->add_warning("Large file detected ({$file_size} bytes). Processing may take time.");
        }
        
        // Check encoding
        $handle = fopen($file_path, 'r');
        $first_line = fgets($handle);
        fclose($handle);
        
        if (!mb_check_encoding($first_line, 'UTF-8')) {
            $this->add_warning("File may not be UTF-8 encoded. Some characters may not display correctly.");
        }
        
        // Validate GEDCOM header
        if (strpos($first_line, 'HEAD') === false) {
            throw new \Exception("Invalid GEDCOM file: Missing header");
        }
    }
    
    /**
     * Utility methods
     */
    private function count_file_lines($file_path) {
        $line_count = 0;
        $handle = fopen($file_path, 'r');
        while (!feof($handle)) {
            fgets($handle);
            $line_count++;
        }
        fclose($handle);
        return $line_count;
    }
    
    private function decode_gedcom_value($value) {
        // Handle GEDCOM escape sequences
        $value = str_replace(['@#DFRENCH R@', '@#DJULIAN@', '@#DGREGORIAN@'], '', $value);
        return trim($value);
    }
    
    private function check_memory_usage() {
        $memory_usage = memory_get_usage(true);
        if ($memory_usage > $this->memory_limit) {
            // Force garbage collection
            gc_collect_cycles();
            
            if (memory_get_usage(true) > $this->memory_limit) {
                throw new \Exception("Memory limit exceeded during GEDCOM parsing");
            }
        }
    }
    
    private function add_error($message, $line_number = 0, $context = '') {
        $error = [
            'message' => $message,
            'line' => $line_number,
            'context' => $context,
            'timestamp' => current_time('mysql')
        ];
        
        $this->errors[] = $error;
        
        if ($this->error_callback) {
            call_user_func($this->error_callback, $error);
        }
    }
    
    private function add_warning($message) {
        $this->warnings[] = [
            'message' => $message,
            'timestamp' => current_time('mysql')
        ];
    }
    
    private function reset_stats() {
        $this->stats = [
            'individuals' => 0,
            'families' => 0,
            'sources' => 0,
            'total_lines' => 0,
            'processed_lines' => 0
        ];
        $this->errors = [];
        $this->warnings = [];
    }
    
    // Placeholder methods for family and source processing
    private function process_family_record($record) { return []; }
    private function process_source_record($record) { return []; }
    private function batch_insert_families($families) {}
    private function batch_insert_sources($sources) {}
    private function parse_event_data($line, &$individual, $event_type) {}
    private function add_line_to_record(&$record, $line) {
        $record['data'][] = $line;
    }
}
