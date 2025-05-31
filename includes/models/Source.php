<?php
/**
 * Source Model Class
 *
 * Represents a genealogical source record with basic metadata.
 *
 * @package HeritagePress
 * @subpackage Models
 */

namespace HeritagePress\Models;

class Source extends Model {
    protected $table = 'sources';
    
    protected $fillable = [
        'uuid',
        'title',
        'author',
        'publication_info',
        'repository',
        'call_number',
        'type',
        'url',
        'notes'
    ];

    protected $rules = [
        'title' => ['required', 'max:255'],
        'type' => ['max:50']
    ];

    /**
     * Get all citations referencing this source
     */
    public function citations() {
        return $this->hasMany(Citation::class, 'source_id');
    }

    /**
     * Get all individuals referenced by this source
     */
    public function individuals() {
        return $this->belongsToMany(
            Individual::class,
            'citations',
            'source_id',
            'individual_id'
        );
    }

    /**
     * Get all families referenced by this source
     */
    public function families() {
        return $this->belongsToMany(
            Family::class,
            'citations',
            'source_id',
            'family_id'
        );
    }

    /**
     * Get all events referenced by this source
     */
    public function events() {
        return $this->belongsToMany(
            Event::class,
            'citations',
            'source_id',
            'event_id'
        );
    }
}
