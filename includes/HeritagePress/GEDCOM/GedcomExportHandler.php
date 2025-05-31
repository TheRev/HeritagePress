<?php
namespace HeritagePress\GEDCOM;

class GedcomExportHandler {
    private $data;
    private $target_version;
    private $output = '';
    private $level = 0;

    public function __construct($data, $target_version = '7.0') {
        $this->data = $data;
        $this->target_version = $target_version;
    }

    /**
     * Export GEDCOM data to string
     */
    public function export() {
        // Start with header
        $this->addHeader();

        // Export each record type
        foreach ($this->data as $type => $records) {
            if ($type !== 'header') {
                foreach ($records as $record) {
                    $this->exportRecord($record);
                }
            }
        }

        // Add trailer
        $this->addLine(0, 'TRLR');

        return $this->output;
    }

    /**
     * Add GEDCOM header
     */
    private function addHeader() {
        $this->addLine(0, 'HEAD');
        $this->addLine(1, 'GEDC');
        $this->addLine(2, 'VERS', $this->target_version);
        $this->addLine(2, 'FORM', 'LINEAGE-LINKED');
        $this->addLine(1, 'CHAR', 'UTF-8');
        $this->addLine(1, 'DATE', date('d M Y'));
        $this->addLine(1, 'FILE', 'GEDCOM Export');
        $this->addLine(1, 'LANG', 'English');
    }

    /**
     * Export a single record
     */
    private function exportRecord($record) {
        if (empty($record['type'])) return;

        $this->level = 0;
        
        // Add record header with ID if present
        if (!empty($record['id'])) {
            $this->addLine(0, $record['id'], $record['type']);
        } else {
            $this->addLine(0, $record['type']);
        }

        // Add record data
        if (!empty($record['data'])) {
            $this->level++;
            foreach ($record['data'] as $item) {
                $this->exportData($item);
            }
        }
    }

    /**
     * Export record data
     */
    private function exportData($item, $parentLevel = 1) {
        // Add the current item
        $tag = isset($item['tag']) ? $item['tag'] : ''; // Ensure 'tag' key exists
        $value = isset($item['value']) ? $item['value'] : ''; // Ensure 'value' key exists

        if (empty($tag)) { // Do not output line if tag is missing
            // Optionally log a warning or error here
            // error_log("Warning: Missing tag in exportData for item: " . print_r($item, true));
            return;
        }

        $this->addLine($parentLevel, $tag, $value);

        // Add any children
        if (!empty($item['children']) && is_array($item['children'])) {
            foreach ($item['children'] as $child) {
                if (is_array($child)) { // Ensure child is an array before recursing
                    $this->exportData($child, $parentLevel + 1);
                } else {
                    // Optionally log a warning or error here
                    // error_log("Warning: Invalid child structure in exportData for item: " . print_r($item, true));
                }
            }
        }
    }

    /**
     * Format and add a GEDCOM line
     */
    private function addLine($level, $tag, $value = '') {
        if (!empty($value)) {
            $line = sprintf("%d %s %s", $level, $tag, $value);
        } else {
            $line = sprintf("%d %s", $level, $tag);
        }

        $this->output .= $line . "\n";
    }

    /**
     * Convert to GEDCOM 5.5.1 format
     */
    private function convertTo551() {
        // Implement conversion logic for 5.5.1
        // This would handle differences in structure and tags
    }

    /**
     * Save GEDCOM to file
     */
    public function saveToFile($filepath) {
        return file_put_contents($filepath, $this->export());
    }
}
