<?php
namespace HeritagePress\Models;

/**
 * Repository Model
 * 
 * Represents a genealogical repository (archive, library, etc.)
 */
class Repository extends Model
{
    /**
     * @var array Required fields for a valid repository
     */
    protected $required_fields = ['name'];

    /**
     * @var array Database field mappings
     */
    protected $fields = [
        'id' => ['type' => 'int', 'auto_increment' => true],
        'tree_id' => ['type' => 'int'],
        'uuid' => ['type' => 'string', 'length' => 36],
        'external_id' => ['type' => 'string', 'length' => 20],
        'name' => ['type' => 'string', 'length' => 255],
        'address' => ['type' => 'text'],
        'phone' => ['type' => 'string', 'length' => 50],
        'email' => ['type' => 'string', 'length' => 255],
        'website' => ['type' => 'string', 'length' => 255],
        'notes' => ['type' => 'text'],
        'privacy_level' => ['type' => 'int', 'default' => 0],
        'created_at' => ['type' => 'datetime'],
        'updated_at' => ['type' => 'datetime']
    ];

    /**
     * Get repository by external ID
     * 
     * @param string $external_id External reference ID
     * @param int $tree_id Tree ID
     * @return Repository|null Repository instance or null if not found
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
     * Get repositories for a tree
     * 
     * @param int $tree_id Tree ID
     * @return array Array of Repository instances
     */
    public static function findByTreeId($tree_id)
    {
        global $wpdb;
        $table = self::getTableName();

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE tree_id = %d ORDER BY name",
            $tree_id
        ), ARRAY_A);

        return array_map(function ($data) {
            return new static($data);
        }, $results);
    }

    /**
     * Get sources linked to this repository
     * 
     * @return array Array of Source instances
     */
    public function getSources()
    {
        return Source::findByRepositoryId($this->id);
    }

    /**
     * Validate repository data
     * 
     * @return bool True if valid
     */
    public function validate()
    {
        if (!parent::validate()) {
            return false;
        }

        // Validate email if present
        if (!empty($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('email', 'Invalid email address');
            return false;
        }

        // Validate website if present
        if (!empty($this->website) && !filter_var($this->website, FILTER_VALIDATE_URL)) {
            $this->addError('website', 'Invalid website URL');
            return false;
        }

        // Validate privacy level
        if ($this->privacy_level < 0 || $this->privacy_level > 3) {
            $this->addError('privacy_level', 'Invalid privacy level');
            return false;
        }

        return true;
    }

    /**
     * Get table name
     * 
     * @return string Table name
     */
    protected static function getTableName()
    {
        global $wpdb;
        return $wpdb->prefix . 'hp_repositories';
    }
}
