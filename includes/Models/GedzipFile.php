<?php
namespace HeritagePress\Models;

/**
 * GEDZIP file model
 */
class GedzipFile extends Model
{
    /**
     * Database table name
     *
     * @var string
     */
    protected $table = 'gedzip_files';

    /**
     * Get all files for an archive
     *
     * @param int $archive_id Archive ID
     * @return array Array of file records
     */
    public function getArchiveFiles($archive_id)
    {
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE archive_id = %d ORDER BY path",
                $archive_id
            )
        );
    }

    /**
     * Find a file by its path within an archive
     *
     * @param int    $archive_id Archive ID
     * @param string $path       File path within archive
     * @return object|null Database row
     */
    public function findByPath($archive_id, $path)
    {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE archive_id = %d AND path = %s",
                $archive_id,
                $path
            )
        );
    }
}
