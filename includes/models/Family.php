<?php
/**
 * Family Model Class
 *
 * Represents a family unit in the genealogy database. This class manages family relationships
 * between individuals and related events like marriages and divorces.
 *
 * @package HeritagePress
 * @subpackage Models
 */

namespace HeritagePress\Models;

/**
 * Family model class
 * 
 * @property int $husband_id ID of the husband
 * @property int $wife_id ID of the wife
 * @property string $marriage_date Marriage date in YYYY-MM-DD format
 * @property int $marriage_place_id ID of marriage place
 * @property string $divorce_date Divorce date in YYYY-MM-DD format
 * @property int $divorce_place_id ID of divorce place
 * @property bool $privacy Privacy flag
 * @property string $notes Additional notes
 */
class Family extends Model {
    protected $table = 'families';
    
    protected $fillable = [
        'uuid',
        'file_id',
        'husband_id',
        'wife_id',
        'marriage_date',
        'marriage_place_id',
        'divorce_date',
        'divorce_place_id',
        'notes',
        'privacy',
        'status'
    ];    public function husband() {
        return $this->belongsTo(Individual::class, 'husband_id');
    }

    public function wife() {
        return $this->belongsTo(Individual::class, 'wife_id');
    }

    public function marriagePlace() {
        return $this->belongsTo(Place::class, 'marriage_place_id');
    }

    public function divorcePlace() {
        return $this->belongsTo(Place::class, 'divorce_place_id');
    }

    public function events() {
        return $this->hasMany(Event::class, 'family_id');
    }

    public function children() {
        // This requires a new table to track child-family relationships
        return $this->belongsToMany(
            Individual::class, 
            'family_children', 
            'family_id', 
            'child_id'
        );
    }

    /**
     * Get cached husband
     */
    public function getHusband() {
        return $this->load('husband');
    }

    /**
     * Get cached wife
     */
    public function getWife() {
        return $this->load('wife');
    }

    /**
     * Get cached children
     */
    public function getChildren() {
        return $this->load('children') ?? $this->children()->get();
    }

    /**
     * Get cached events
     */
    public function getEvents() {
        return $this->load('events') ?? $this->events()->get();
    }

    /**
     * Get timeline of family events in chronological order
     */
    public function getTimeline() {
        $timeline = [];

        // Add family events
        $events = $this->getEvents();
        foreach ($events as $event) {
            $timeline[] = [
                'date' => $event->date,
                'type' => $event->type,
                'description' => $event->description,
                'place' => $event->getPlace(),
                'event' => $event
            ];
        }

        // Add husband events
        if ($husband = $this->getHusband()) {
            $husbandEvents = $husband->events()->get();
            foreach ($husbandEvents as $event) {
                $timeline[] = [
                    'date' => $event->date,
                    'type' => $event->type,
                    'description' => $event->description,
                    'person' => $husband,
                    'place' => $event->getPlace(),
                    'event' => $event
                ];
            }
        }

        // Add wife events
        if ($wife = $this->getWife()) {
            $wifeEvents = $wife->events()->get();
            foreach ($wifeEvents as $event) {
                $timeline[] = [
                    'date' => $event->date,
                    'type' => $event->type,
                    'description' => $event->description,
                    'person' => $wife,
                    'place' => $event->getPlace(),
                    'event' => $event
                ];
            }
        }

        // Add children's events
        $children = $this->getChildren();
        foreach ($children as $child) {
            $childEvents = $child->events()->get();
            foreach ($childEvents as $event) {
                $timeline[] = [
                    'date' => $event->date,
                    'type' => $event->type,
                    'description' => $event->description,
                    'person' => $child,
                    'place' => $event->getPlace(),
                    'event' => $event
                ];
            }
        }

        // Sort by date
        usort($timeline, function($a, $b) {
            return strcmp($a['date'] ?? '', $b['date'] ?? '');
        });

        return $timeline;
    }

    /**
     * Get family statistics
     */
    public function getStatistics() {
        $children = $this->getChildren();
        $events = $this->getEvents();
        
        return [
            'marriage_length' => $this->getMarriageLength(),
            'number_of_children' => count($children),
            'average_child_spacing' => $this->getAverageChildSpacing($children),
            'places_lived' => $this->getPlacesLived($events),
            'age_at_marriage' => [
                'husband' => $this->getAgeAtMarriage('husband'),
                'wife' => $this->getAgeAtMarriage('wife')
            ]
        ];
    }

    /**
     * Calculate marriage length in years
     */
    private function getMarriageLength() {
        if (!$this->marriage_date) {
            return null;
        }

        $end_date = $this->divorce_date ?? 'now';
        $marriage = new \DateTime($this->marriage_date);
        $end = new \DateTime($end_date);
        
        return $marriage->diff($end)->y;
    }

    /**
     * Calculate average spacing between children in months
     */
    private function getAverageChildSpacing($children) {
        if (count($children) < 2) {
            return null;
        }

        $birthDates = [];
        foreach ($children as $child) {
            if ($child->birth_date) {
                $birthDates[] = new \DateTime($child->birth_date);
            }
        }

        if (count($birthDates) < 2) {
            return null;
        }

        sort($birthDates);
        $totalMonths = 0;
        $intervals = 0;

        for ($i = 1; $i < count($birthDates); $i++) {
            $interval = $birthDates[$i-1]->diff($birthDates[$i]);
            $totalMonths += ($interval->y * 12) + $interval->m;
            $intervals++;
        }

        return $totalMonths / $intervals;
    }

    /**
     * Get list of places where the family lived
     */
    private function getPlacesLived($events) {
        $places = [];
        foreach ($events as $event) {
            if ($place = $event->getPlace()) {
                $places[$place->id] = $place;
            }
        }
        return array_values($places);
    }

    /**
     * Calculate age at marriage for husband or wife
     */
    private function getAgeAtMarriage($spouse) {
        if (!$this->marriage_date) {
            return null;
        }

        $person = $spouse === 'husband' ? $this->getHusband() : $this->getWife();
        if (!$person || !$person->birth_date) {
            return null;
        }

        $birth = new \DateTime($person->birth_date);
        $marriage = new \DateTime($this->marriage_date);
        
        return $birth->diff($marriage)->y;
    }

    /**
     * Get all citations for this family
     */
    public function citations() {
        return $this->hasMany(Citation::class, 'family_id');
    }

    /**
     * Get all sources cited for this family
     */
    public function sources() {
        return $this->belongsToMany(
            Source::class,
            'citations',
            'family_id',
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
            'family_id' => $this->id,
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
        
        // Marriage information
        if ($this->marriage_date || $this->marriage_place_id) {
            $marriage_citations = Citation::query()
                ->where('family_id', $this->id)
                ->whereIn('event_id', 
                    Event::query()
                        ->where('family_id', $this->id)
                        ->where('type', 'MARRIAGE')
                        ->get()
                        ->pluck('id')
                )
                ->get();
            
            $facts['marriage'] = [
                'date' => $this->marriage_date,
                'place' => $this->getMarriagePlace(),
                'citations' => $marriage_citations
            ];
        }

        // Divorce information
        if ($this->divorce_date || $this->divorce_place_id) {
            $divorce_citations = Citation::query()
                ->where('family_id', $this->id)
                ->whereIn('event_id', 
                    Event::query()
                        ->where('family_id', $this->id)
                        ->where('type', 'DIVORCE')
                        ->get()
                        ->pluck('id')
                )
                ->get();
            
            $facts['divorce'] = [
                'date' => $this->divorce_date,
                'place' => $this->getDivorcePlace(),
                'citations' => $divorce_citations
            ];
        }

        // Other events with citations
        $events = $this->events()->get();
        foreach ($events as $event) {
            if (in_array($event->type, ['MARRIAGE', 'DIVORCE'])) continue;
            
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
