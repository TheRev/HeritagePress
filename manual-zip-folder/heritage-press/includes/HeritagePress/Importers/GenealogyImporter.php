<?php
/**
 * Genealogy Importer Interface
 *
 * Defines the contract for genealogy data importers from various software formats.
 *
 * @package HeritagePress
 * @subpackage Importers
 */

namespace HeritagePress\Importers;

interface GenealogyImporter {
    /**
     * Check if the given file is in a format this importer can handle
     *
     * @param string $file_path Path to the file to check
     * @return bool True if the file can be imported by this importer
     */
    public function can_import($file_path);

    /**
     * Get the name of the format this importer handles
     *
     * @return string Format name (e.g., "Family Tree Maker", "RootsMagic")
     */
    public function get_format_name();

    /**
     * Get supported file extensions for this importer
     *
     * @return array Array of supported file extensions (e.g., ["ftm", "ftmb"])
     */
    public function get_supported_extensions();

    /**
     * Import the genealogy data from the file
     *
     * @param string $file_path Path to the file to import
     * @param array $options Import options (optional)
     * @return array Results of the import operation
     */
    public function import($file_path, $options = []);

    /**
     * Validate the file before importing
     *
     * @param string $file_path Path to the file to validate
     * @return array Validation results with any errors or warnings
     */
    public function validate($file_path);
}
