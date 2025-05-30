<?php
/**
 * Place Model Class
 *
 * Represents a geographical location in the genealogy database. Places can have
 * hierarchical relationships (e.g., city within a state) and geographical coordinates.
 *
 * @package HeritagePress
 * @subpackage Models
 */

namespace HeritagePress\Models;

/**
 * Place model class
 * 
 * @property string $name Place name
 * @property float $latitude Geographical latitude
 * @property float $longitude Geographical longitude
 * @property int $parent_id Optional ID of parent place
 * @property string $notes Additional notes
 */
class Place extends Model {
    protected $table = 'places';
    
    protected $fillable = [
        'uuid',
        'file_id',
        'name',
        'latitude',
        'longitude',
        'parent_id',
        'notes',
        'status'
    ];

    public function getParent() {
        return self::find($this->parent_id);
    }

    public function getChildren() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'genealogy_';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$prefix}places WHERE parent_id = %d",
                $this->id
            ),
            ARRAY_A
        );

        return array_map(function($data) {
            return new self($data);
        }, $results);
    }

    public function getFullName() {
        $names = [$this->name];
        $parent = $this->getParent();
        
        while ($parent) {
            $names[] = $parent->name;
            $parent = $parent->getParent();
        }

        return implode(', ', array_reverse($names));
    }
}
