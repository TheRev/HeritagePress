<?php
namespace HeritagePress\Services;

use HeritagePress\Models\GedzipArchive;
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
     * @var string Current GEDCOM version being processed
     */
    private $current_version;

    /**
     * @var array Line buffer for processing
     */
    private $buffer = [];

    /**
     * @var int Current tree ID
     */
    private $tree_id;

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
    }
    private function processFamily($lines)
    {
    }
    private function processSource($lines)
    {
    }
    private function processMedia($lines)
    {
    }
    private function processNote($lines)
    {
    }
    private function processRepository($lines)
    {
    }
}
