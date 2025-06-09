<?php
namespace HeritagePress\Models;

use ZipArchive;

/**
 * GEDZIP archive model
 */
class GedzipArchive extends Model
{
    /**
     * Database table name
     *
     * @var string
     */
    protected $table = 'gedzip_archives';

    /**
     * Import a GEDZIP file
     *
     * @param string $filepath Path to GEDZIP file
     * @param int    $tree_id  Tree ID to associate with
     * @return int|false The archive ID if successful, false on failure
     */
    public function import($filepath, $tree_id)
    {
        if (!file_exists($filepath)) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            return false;
        }

        // Extract version from gedcom file
        $version = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (preg_match('/\.ged$/i', $filename)) {
                $contents = $zip->getFromIndex($i);
                if (preg_match('/1 GEDC.*\n2 VERS (.+)/', $contents, $matches)) {
                    $version = trim($matches[1]);
                    break;
                }
            }
        }

        // Insert archive record
        $archive_data = [
            'tree_id' => $tree_id,
            'uuid' => wp_generate_uuid4(),
            'filename' => basename($filepath),
            'version' => $version,
            'hash' => hash_file('sha256', $filepath)
        ];

        $this->db->begin_transaction();

        try {
            // Insert archive record
            if (!$this->insert($archive_data)) {
                throw new \Exception('Failed to insert archive record');
            }
            $archive_id = $this->db->insert_id;

            // Import files
            $files_model = new GedzipFile();
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $file_data = [
                    'archive_id' => $archive_id,
                    'path' => $zip->getNameIndex($i),
                    'mime_type' => wp_get_mime_type($zip->getNameIndex($i)),
                    'file_size' => $zip->statIndex($i)['size'],
                    'hash' => hash('sha256', $zip->getFromIndex($i))
                ];

                if (!$files_model->insert($file_data)) {
                    throw new \Exception('Failed to insert file record');
                }
            }

            $this->db->commit();
            return $archive_id;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        } finally {
            $zip->close();
        }
    }

    /**
     * Extract files from archive
     *
     * @param int    $archive_id Archive ID
     * @param string $dest_dir   Destination directory
     * @return bool True if successful, false on failure
     */
    public function extract($archive_id, $dest_dir)
    {
        $archive = $this->find($archive_id);
        if (!$archive) {
            return false;
        }

        $filepath = wp_upload_dir()['basedir'] . '/gedzip/' . $archive->filename;
        if (!file_exists($filepath)) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            return false;
        }

        $result = $zip->extractTo($dest_dir);
        $zip->close();

        return $result;
    }
}
