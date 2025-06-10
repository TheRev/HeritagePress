<?php
namespace HeritagePress\Models;

/**
 * Source Model
 * 
 * Represents a genealogical source document or record
 */
class Source extends Model
{
    /**
     * @var array Required fields for a valid source
     */
    protected $required_fields = ['title'];

    /**
     * @var array Database field mappings
     */
    protected $fields = [
        'id' => ['type' => 'int', 'auto_increment' => true],
        'tree_id' => ['type' => 'int'],
        'uuid' => ['type' => 'string', 'length' => 36],
        'external_id' => ['type' => 'string', 'length' => 20],
        'title' => ['type' => 'string', 'length' => 255],
        'author' => ['type' => 'string', 'length' => 255],
        'publication_info' => ['type' => 'text'],
        'repository_id' => ['type' => 'int', 'null' => true],
        'call_number' => ['type' => 'string', 'length' => 50],
        'page_numbers' => ['type' => 'string', 'length' => 50],
        'quality_assessment' => ['type' => 'string', 'length' => 50],
        'privacy_level' => ['type' => 'int', 'default' => 0],
        'created_at' => ['type' => 'datetime'],
        'updated_at' => ['type' => 'datetime']
    ];

    /**
     * Get source by external ID
     */
    public static function findByExternalId($external_id, $tree_id)
    {
        global $wpdb;
        $table = self::getTableName();

        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE external_id = %s AND tree_id = %d",
            $external_id,
            $tree_id
        ), ARRAY_A);

        return $data ? new static($data) : null;
    }

    /**
     * Get sources by repository ID
     */
    public static function findByRepositoryId($repository_id)
    {
        global $wpdb;
        $table = self::getTableName();

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE repository_id = %d ORDER BY title",
            $repository_id
        ), ARRAY_A);

        return array_map(function ($data) {
            return new static($data);
        }, $results);
    }

    /**
     * Get sources for a tree
     */
    public static function findByTreeId($tree_id)
    {
        global $wpdb;
        $table = self::getTableName();

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE tree_id = %d ORDER BY title",
            $tree_id
        ), ARRAY_A);

        return array_map(function ($data) {
            return new static($data);
        }, $results);
    }

    /**
     * Get repository this source belongs to
     */
    public function getRepository()
    {
        return $this->repository_id ? Repository::find($this->repository_id) : null;
    }

    /**
     * Convert quality assessment code to descriptive text
     */
    public function getQualityDescription()
    {
        switch ($this->quality_assessment) {
            case '3':
                return 'Very reliable - primary evidence';
            case '2':
                return 'Reliable - secondary evidence';
            case '1':
                return 'Questionable reliability';
            case '0':
                return 'Unreliable evidence';
            default:
                return 'Unknown reliability';
        }
    }

    /**
     * Validate source data
     */
    public function validate()
    {
        if (!parent::validate()) {
            return false;
        }

        // Validate repository exists if specified
        if ($this->repository_id && !Repository::find($this->repository_id)) {
            $this->addError('repository_id', 'Repository not found');
            return false;
        }

        // Validate privacy level
        if ($this->privacy_level < 0 || $this->privacy_level > 3) {
            $this->addError('privacy_level', 'Invalid privacy level');
            return false;
        }

        // Validate quality assessment
        if ($this->quality_assessment && !in_array($this->quality_assessment, ['0', '1', '2', '3'])) {
            $this->addError('quality_assessment', 'Invalid quality assessment level');
            return false;
        }

        return true;
    }

    /**
     * Get table name
     */
    protected static function getTableName()
    {
        global $wpdb;
        return $wpdb->prefix . 'hp_sources';
    }
}
