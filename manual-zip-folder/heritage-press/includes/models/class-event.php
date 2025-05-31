<?php
/**
 * Event Model Class
 *
 * Represents a genealogical event in the database. Events can be associated with
 * either individuals or families and include details like dates and places.
 *
 * @package HeritagePress
 * @subpackage Models
 */

namespace HeritagePress\Models;

/**
 * Event model class
 * 
 * @property int $individual_id Optional ID of associated individual
 * @property int $family_id Optional ID of associated family
 * @property string $type Event type (BIRTH, DEATH, etc.)
 * @property string $date Event date in YYYY-MM-DD format
 * @property int $place_id ID of event place
 * @property string $description Event description
 * @property bool $privacy Privacy flag
 */
class Event extends Model {
    protected $table = 'events';
    
    protected $fillable = [
        'uuid',
        'file_id',
        'individual_id',
        'family_id',
        'type',
        'date',
        'place_id',
        'description',
        'privacy',
        'status'
    ];

    public function getIndividual() {
        return Individual::find($this->individual_id);
    }

    public function getFamily() {
        return Family::find($this->family_id);
    }

    public function getPlace() {
        return Place::find($this->place_id);
    }
}
