<?php
/**
 * Individual Model Class
 *
 * Represents a person in the genealogy database. This class handles all individual-related
 * data and relationships including personal details, life events, and family connections.
 *
 * @package HeritagePress
 * @subpackage Models
 */

namespace HeritagePress\Models;

use HeritagePress\Database\QueryBuilder as Query_Builder;

/**
 * Individual model class
 * 
 * @property string $given_names Individual's given names
 * @property string $surname Individual's surname
 * @property string $birth_date Birth date in YYYY-MM-DD format
 * @property int $birth_place_id ID of birth place
 * @property string $death_date Death date in YYYY-MM-DD format
 * @property int $death_place_id ID of death place
 * @property string $gender Gender (M/F)
 * @property bool $privacy Privacy flag
 * @property string $notes Additional notes
 */
class Individual extends Model {
    protected $table = 'individuals';
    
    protected $fillable = [
        'uuid',
        'file_id',
        'given_names',
        'surname',
        'birth_date',
        'birth_place_id',
        'death_date',
        'death_place_id',
        'gender',
        'privacy',
        'notes',
        'status'
    ];    protected $rules = [
        'given_names' => 'required|max:100',
        'surname' => 'max:100',
        'birth_date' => 'date',
        'death_date' => 'date',
        'gender' => 'required|in:M,F,U',
        'privacy' => 'numeric'
    ];

    /**
     * Get the individual's birth place
     * 
     * @return Place|null
     */    public function birthPlace() {
        if (!isset($this->data['birth_place_id'])) {
            return null;
        }
        return Place::find($this->data['birth_place_id']);
    }

    /**
     * Get the individual's death place
     * 
     * @return Place|null
     */
    public function deathPlace() {
        if (!isset($this->data['death_place_id'])) {
            return null;
        }
        return Place::find($this->data['death_place_id']);
    }

    /**
     * Get all events for this individual
     * 
     * @return array
     */
    public function events() {
        if (!isset($this->data['id'])) {
            return [];
        }
        return Event::findAll(['individual_id' => $this->data['id']]);
    }

    /**
     * Get the individual's birth event
     * 
     * @return Event|null
     */
    public function birthEvent() {
        if (!isset($this->data['id'])) {
            return null;
        }
        $query = new Query_Builder(new Event());
        return $query->where('individual_id', $this->data['id'])
                    ->where('type', 'birth')
                    ->first();
    }

    /**
     * Get the individual's death event
     * 
     * @return Event|null
     */
    public function deathEvent() {
        if (!isset($this->data['id'])) {
            return null;
        }
        $query = new Query_Builder(new Event());
        return $query->where('individual_id', $this->data['id'])
                    ->where('type', 'death')
                    ->first();
    }

    /**
     * Magic method to access relationships as properties
     */
    public function __get($key) {
        $value = parent::__get($key);
        if ($value !== null) {
            return $value;
        }

        // Check if we have a relationship method
        $method = $key;
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return null;
    }

    /**
     * Get families where this individual is a spouse
     * 
     * @return Family[]
     */
    public function spouseFamilies() {
        return array_merge(
            $this->loadRelationship('husbandFamilies', $this->hasMany(Family::class, 'husband_id')),
            $this->loadRelationship('wifeFamilies', $this->hasMany(Family::class, 'wife_id'))
        );
    }

    /**
     * Get families where this individual is a child
     * 
     * @return Family[]
     */
    public function childFamilies() {
        return $this->loadRelationship('childFamilies', $this->belongsToMany(
            Family::class,
            'family_child',
            'child_id',
            'family_id'
        ));
    }

    /**
     * Get all citations for this individual
     * 
     * @return Citation[]
     */
    public function citations() {
        return $this->loadRelationship('citations', $this->hasMany(Citation::class));
    }

    /**
     * Load a relationship if not already loaded
     * 
     * @param string $name Relationship name
     * @param array $config Relationship configuration
     * @return mixed The related model(s)
     */
    protected function loadRelationship($name, $config) {
        if (!isset($this->relationships[$name])) {
            $query = new Query_Builder();
            $className = $config['model'];
            
            switch ($config['type']) {
                case 'belongsTo':
                    $data = $query->table((new $className())->table)
                                ->where($config['ownerKey'], $this->{$config['foreignKey']})
                                ->first();
                    $this->relationships[$name] = $data ? new $className($data) : null;
                    break;
                    
                case 'hasMany':
                    $data = $query->table((new $className())->table)
                                ->where($config['foreignKey'], $this->{$config['localKey']})
                                ->get();
                    $this->relationships[$name] = array_map(function($row) use ($className) {
                        return new $className($row);
                    }, $data);
                    break;
                    
                case 'belongsToMany':
                    $data = $query->table($config['pivotTable'])
                                ->join(
                                    (new $className())->table,
                                    $config['pivotTable'] . '.' . $config['relatedKey'],
                                    '=',
                                    (new $className())->table . '.id'
                                )
                                ->where($config['pivotTable'] . '.' . $config['foreignKey'], $this->id)
                                ->get();
                    $this->relationships[$name] = array_map(function($row) use ($className) {
                        return new $className($row);
                    }, $data);
                    break;
            }
        }
        
        return $this->relationships[$name];
    }

    public function getFamilies() {
        return array_merge(
            $this->familiesAsHusband()->get(),
            $this->familiesAsWife()->get()
        );
    }

    public function getParentFamilies() {
        // This requires a new table to track child-family relationships
        return $this->belongsToMany(
            Family::class, 
            'family_children', 
            'child_id', 
            'family_id'
        )->get();
    }

    public function getChildren() {
        $families = $this->getFamilies();
        $children = [];
        
        foreach ($families as $family) {
            $children = array_merge($children, $family->getChildren());
        }
        
        return $children;
    }

    /**
     * Get siblings with the same parents
     */
    public function getSiblings($includeHalfSiblings = false) {
        $parentFamilies = $this->getParentFamilies();
        $siblings = [];
        
        foreach ($parentFamilies as $family) {
            $familySiblings = $family->getChildren();
            foreach ($familySiblings as $sibling) {
                if ($sibling->id !== $this->id) {
                    $siblings[$sibling->id] = $sibling;
                }
            }
        }

        if ($includeHalfSiblings) {
            $parents = $this->getParents();
            foreach ($parents as $parent) {
                $parentFamilies = $parent->getFamilies();
                foreach ($parentFamilies as $family) {
                    $familySiblings = $family->getChildren();
                    foreach ($familySiblings as $sibling) {
                        if ($sibling->id !== $this->id) {
                            $siblings[$sibling->id] = $sibling;
                        }
                    }
                }
            }
        }

        return array_values($siblings);
    }

    /**
     * Get all parents (biological and adoptive)
     */
    public function getParents() {
        $parents = [];
        $parentFamilies = $this->getParentFamilies();
        
        foreach ($parentFamilies as $family) {
            if ($family->husband) {
                $parents[$family->husband->id] = $family->husband;
            }
            if ($family->wife) {
                $parents[$family->wife->id] = $family->wife;
            }
        }

        return array_values($parents);
    }

    /**
     * Get ancestors up to a specified number of generations
     */
    public function getAncestors($generations = null, $currentGen = 1) {
        $ancestors = [];
        
        if ($generations !== null && $currentGen > $generations) {
            return $ancestors;
        }

        $parents = $this->getParents();
        foreach ($parents as $parent) {
            $ancestors[] = [
                'individual' => $parent,
                'generation' => $currentGen
            ];

            $parentAncestors = $parent->getAncestors($generations, $currentGen + 1);
            $ancestors = array_merge($ancestors, $parentAncestors);
        }

        return $ancestors;
    }

    /**
     * Get descendants up to a specified number of generations
     */
    public function getDescendants($generations = null, $currentGen = 1) {
        $descendants = [];
        
        if ($generations !== null && $currentGen > $generations) {
            return $descendants;
        }

        $children = $this->getChildren();
        foreach ($children as $child) {
            $descendants[] = [
                'individual' => $child,
                'generation' => $currentGen
            ];

            $childDescendants = $child->getDescendants($generations, $currentGen + 1);
            $descendants = array_merge($descendants, $childDescendants);
        }

        return $descendants;
    }

    /**
     * Get all spouses with marriage dates
     */
    public function getSpouses() {
        $spouses = [];
        $families = $this->getFamilies();

        foreach ($families as $family) {
            if ($family->husband_id === $this->id && $family->wife) {
                $spouses[] = [
                    'spouse' => $family->wife,
                    'marriage_date' => $family->marriage_date,
                    'divorce_date' => $family->divorce_date,
                    'family' => $family
                ];
            } elseif ($family->wife_id === $this->id && $family->husband) {
                $spouses[] = [
                    'spouse' => $family->husband,
                    'marriage_date' => $family->marriage_date,
                    'divorce_date' => $family->divorce_date,
                    'family' => $family
                ];
            }
        }

        return $spouses;
    }

    /**
     * Find blood relatives within a certain degree
     */
    public function getRelatives($maxDegree = 3) {
        $relatives = [];
        
        // Add ancestors up to maxDegree generations
        $ancestors = $this->getAncestors($maxDegree);
        foreach ($ancestors as $ancestor) {
            $degree = $ancestor['generation'];
            if (!isset($relatives[$degree])) {
                $relatives[$degree] = [];
            }
            $relatives[$degree][] = [
                'individual' => $ancestor['individual'],
                'relationship' => $this->determineRelationship($degree, 'ancestor')
            ];
        }

        // Add descendants up to maxDegree generations
        $descendants = $this->getDescendants($maxDegree);
        foreach ($descendants as $descendant) {
            $degree = $descendant['generation'];
            if (!isset($relatives[$degree])) {
                $relatives[$degree] = [];
            }
            $relatives[$degree][] = [
                'individual' => $descendant['individual'],
                'relationship' => $this->determineRelationship($degree, 'descendant')
            ];
        }

        return $relatives;
    }

    /**
     * Determine the relationship type based on degree and direction
     */
    private function determineRelationship($degree, $direction) {
        if ($direction === 'ancestor') {
            switch ($degree) {
                case 1: return 'parent';
                case 2: return 'grandparent';
                case 3: return 'great-grandparent';
                default: return sprintf('great-%d-grandparent', $degree - 2);
            }
        } else {
            switch ($degree) {
                case 1: return 'child';
                case 2: return 'grandchild';
                case 3: return 'great-grandchild';
                default: return sprintf('great-%d-grandchild', $degree - 2);
            }
        }
    }    /**
     * Get all sources cited for this individual
     */
    public function sources() {
        return $this->belongsToMany(
            Source::class,
            'citations',
            'individual_id',
            'source_id'
        );
    }

    /**
     * Add a source citation
     */
    public function addSource($source, $citation_data = []) {
        $source_id = is_object($source) ? $source->id : $source;
        
        $citation = new Citation([
            'source_id' => $source_id,
            'individual_id' => $this->id,
            'file_id' => $this->file_id,
            ...$citation_data
        ]);

        return $citation->save();
    }

    /**
     * Get facts with their sources
     */
    public function getFactsWithSources() {
        $facts = [];
        
        // Birth information
        if ($this->birth_date || $this->birth_place_id) {
            $birth_citations = Citation::query()
                ->where('individual_id', $this->id)
                ->whereIn('event_id', 
                    Event::query()
                        ->where('individual_id', $this->id)
                        ->where('type', 'BIRTH')
                        ->get()
                        ->pluck('id')
                )
                ->get();
            
            $facts['birth'] = [
                'date' => $this->birth_date,
                'place' => $this->getBirthPlace(),
                'citations' => $birth_citations
            ];
        }

        // Death information
        if ($this->death_date || $this->death_place_id) {
            $death_citations = Citation::query()
                ->where('individual_id', $this->id)
                ->whereIn('event_id', 
                    Event::query()
                        ->where('individual_id', $this->id)
                        ->where('type', 'DEATH')
                        ->get()
                        ->pluck('id')
                )
                ->get();
            
            $facts['death'] = [
                'date' => $this->death_date,
                'place' => $this->getDeathPlace(),
                'citations' => $death_citations
            ];
        }

        // Other events with citations
        $events = $this->events()->get();
        foreach ($events as $event) {
            if (in_array($event->type, ['BIRTH', 'DEATH'])) continue;
            
            $facts[$event->type] = [
                'date' => $event->date,
                'place' => $event->getPlace(),
                'description' => $event->description,
                'citations' => $event->citations()->get()
            ];
        }

        return $facts;
    }
}
