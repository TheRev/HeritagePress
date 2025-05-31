<?php
/**
 * Repository Model Class
 *
 * Represents a physical or digital repository where genealogical sources are held.
 *
 * @package HeritagePress
 * @subpackage Models
 */

namespace HeritagePress\Models;

/**
 * Repository model class
 * 
 * @property string $name Repository name
 * @property string $type Type of repository (archive, library, online, etc.)
 * @property string $address Physical address
 * @property string $website Website URL
 * @property string $contact_info Contact information
 * @property string $access_notes Notes about access requirements
 */
class Repository extends Model {
    protected $table = 'repositories';
    
    protected $fillable = [
        'uuid',
        'file_id',
        'name',
        'type',
        'address',
        'website',
        'contact_info',
        'access_notes',
        'notes',
        'status'
    ];

    protected $rules = [
        'name' => ['required', 'max:255'],
        'type' => ['required', 'max:50'],
        'website' => ['max:255']
    ];

    /**
     * Get sources from this repository
     */
    public function sources() {
        return $this->hasMany(Source::class, 'repository_id');
    }

    /**
     * Get online access URL if available
     */
    public function getAccessUrl() {
        if ($this->type === 'online' && $this->website) {
            return $this->website;
        }
        return null;
    }

    /**
     * Check if repository is currently accessible
     */
    public function isAccessible() {
        if ($this->type === 'online') {
            return $this->website !== null;
        }
        return true; // Physical repositories assumed accessible unless noted
    }
}
