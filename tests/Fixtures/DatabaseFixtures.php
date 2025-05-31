<?php
namespace HeritagePress\Tests\Fixtures;

class DatabaseFixtures {
    private $wpdb;
    private $table_prefix;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix . 'heritage_press_';
    }

    /**
     * Create sample data for testing
     */
    public function create() {
        $this->create_places();
        $this->create_individuals();
        $this->create_families();
        $this->create_events();
        $this->create_sources();
        $this->create_citations();
    }

    private function create_places() {
        $places = [
            [
                'uuid' => 'place-1',
                'file_id' => 'test-file',
                'name' => 'London, England',
                'latitude' => 51.5074,
                'longitude' => -0.1278
            ],
            [
                'uuid' => 'place-2',
                'file_id' => 'test-file',
                'name' => 'New York, USA',
                'latitude' => 40.7128,
                'longitude' => -74.0060
            ]
        ];

        foreach ($places as $place) {
            $this->wpdb->insert($this->table_prefix . 'places', $place);
        }
    }

    private function create_individuals() {
        $individuals = [
            [
                'uuid' => 'ind-1',
                'file_id' => 'test-file',
                'given_names' => 'John',
                'surname' => 'Smith',
                'birth_date' => '1900-01-01',
                'birth_place_id' => 1,
                'gender' => 'M'
            ],
            [
                'uuid' => 'ind-2',
                'file_id' => 'test-file',
                'given_names' => 'Mary',
                'surname' => 'Jones',
                'birth_date' => '1905-06-15',
                'birth_place_id' => 2,
                'gender' => 'F'
            ],
            [
                'uuid' => 'ind-3',
                'file_id' => 'test-file',
                'given_names' => 'William',
                'surname' => 'Smith',
                'birth_date' => '1930-12-25',
                'birth_place_id' => 1,
                'gender' => 'M'
            ]
        ];

        foreach ($individuals as $individual) {
            $this->wpdb->insert($this->table_prefix . 'individuals', $individual);
        }
    }

    private function create_families() {
        $families = [
            [
                'uuid' => 'fam-1',
                'file_id' => 'test-file',
                'husband_id' => 1,
                'wife_id' => 2,
                'marriage_date' => '1925-06-01',
                'marriage_place_id' => 1
            ]
        ];

        foreach ($families as $family) {
            $this->wpdb->insert($this->table_prefix . 'families', $family);
        }

        // Add child relationship
        $this->wpdb->insert($this->table_prefix . 'family_children', [
            'family_id' => 1,
            'child_id' => 3,
            'relationship_type' => 'birth'
        ]);
    }

    private function create_events() {
        $events = [
            [
                'uuid' => 'evt-1',
                'file_id' => 'test-file',
                'individual_id' => 1,
                'type' => 'BIRTH',
                'date' => '1900-01-01',
                'place_id' => 1
            ],
            [
                'uuid' => 'evt-2',
                'file_id' => 'test-file',
                'family_id' => 1,
                'type' => 'MARRIAGE',
                'date' => '1925-06-01',
                'place_id' => 1
            ]
        ];

        foreach ($events as $event) {
            $this->wpdb->insert($this->table_prefix . 'events', $event);
        }
    }

    private function create_sources() {
        $sources = [
            [
                'uuid' => 'src-1',
                'file_id' => 'test-file',
                'title' => 'Birth Certificate',
                'type' => 'CERTIFICATE',
                'date' => '1900-01-01'
            ],
            [
                'uuid' => 'src-2',
                'file_id' => 'test-file',
                'title' => 'Marriage Register',
                'type' => 'REGISTER',
                'date' => '1925-06-01'
            ]
        ];

        foreach ($sources as $source) {
            $this->wpdb->insert($this->table_prefix . 'sources', $source);
        }
    }

    private function create_citations() {
        $citations = [
            [
                'uuid' => 'cit-1',
                'source_id' => 1,
                'individual_id' => 1,
                'event_id' => 1,
                'citation_text' => 'Birth certificate details',
                'quality_score' => 3
            ],
            [
                'uuid' => 'cit-2',
                'source_id' => 2,
                'family_id' => 1,
                'event_id' => 2,
                'citation_text' => 'Marriage register entry',
                'quality_score' => 3
            ]
        ];

        foreach ($citations as $citation) {
            $this->wpdb->insert($this->table_prefix . 'citations', $citation);
        }
    }

    /**
     * Clean up test data
     */
    public function cleanup() {
        $tables = [
            'citations',
            'sources',
            'events',
            'family_children',
            'families',
            'individuals',
            'places'
        ];

        foreach ($tables as $table) {
            $this->wpdb->query("TRUNCATE TABLE {$this->table_prefix}{$table}");
        }
    }
}
