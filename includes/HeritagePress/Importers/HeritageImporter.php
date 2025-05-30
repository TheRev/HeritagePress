<?php
/**
 * Heritage Importer Interface
 *
 * Defines the contract for heritage data importers from various software formats.
 *
 * @package HeritagePress
 * @subpackage Importers
 */

namespace HeritagePress\Importers;

interface HeritageImporter {
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
     * @return array List of file extensions this importer supports
     */
    public function get_supported_extensions();

    /**
     * Import data from a file
     *
     * @param string $file_path Path to the file to import
     * @param array $options Optional import options
     * @return bool True if import was successful
     */
    public function import($file_path, $options = []);

    /**
     * Get import progress
     *
     * @return array Progress info with 'current' and 'total' counts
     */
    public function get_progress();

    /**
     * Cancel an in-progress import
     *
     * @return bool True if import was cancelled successfully
     */
    public function cancel_import();
}
