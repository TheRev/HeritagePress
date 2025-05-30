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
    ];    public function birthPlace() {
        return $this->belongsTo(Place::class, 'birth_place_id');
    }

    public function deathPlace() {
        return $this->belongsTo(Place::class, 'death_place_id');
    }

    public function events() {
        return $this->hasMany(Event::class, 'individual_id');
    }

    public function familiesAsHusband() {
        return $this->hasMany(Family::class, 'husband_id');
    }

    public function familiesAsWife() {
        return $this->hasMany(Family::class, 'wife_id');
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
    }

    /**
     * Get all citations for this individual
     */
    public function citations() {
        return $this->hasMany(Citation::class, 'individual_id');
    }

    /**
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
