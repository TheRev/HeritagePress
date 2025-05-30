<?php
namespace HeritagePress\GEDCOM;

/**
 * GEDCOM Converter Class
 * Converts GEDCOM 5.5.1 files to GEDCOM 7.0 format
 */
class GedcomConverter {
    private $data;
    
    /**
     * Constructor
     *
     * @param array $data The parsed GEDCOM 5.5.1 data
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Convert GEDCOM 5.5.1 data to GEDCOM 7.0 format
     *
     * @return array Converted data in GEDCOM 7.0 format
     */
    public function convert() {
        $converted = [
            'header' => $this->convertHeader(),
            'individuals' => [],
            'families' => [],
            'sources' => [],
            'repositories' => [],
            'media' => [],
            'notes' => [],
            'places' => []
        ];

        // Convert records
        foreach ($this->data as $type => $records) {
            switch ($type) {
                case 'INDI':
                    foreach ($records as $record) {
                        $converted['individuals'][] = $this->convertIndividual($record);
                    }
                    break;
                case 'FAM':
                    foreach ($records as $record) {
                        $converted['families'][] = $this->convertFamily($record);
                    }
                    break;
                case 'SOUR':
                    foreach ($records as $record) {
                        $converted['sources'][] = $this->convertSource($record);
                    }
                    break;
                case 'REPO':
                    foreach ($records as $record) {
                        $converted['repositories'][] = $this->convertRepository($record);
                    }
                    break;
                case 'OBJE':
                    foreach ($records as $record) {
                        $converted['media'][] = $this->convertMedia($record);
                    }
                    break;
                case 'NOTE':
                    foreach ($records as $record) {
                        $converted['notes'][] = $this->convertNote($record);
                    }
                    break;
            }
        }

        return $converted;
    }

    private function convertHeader() {
        $header = $this->data['HEAD'] ?? [];
        $header['data'][] = [
            'tag' => 'GEDC',
            'children' => [
                ['tag' => 'VERS', 'value' => '7.0']
            ]
        ];
        return $header;
    }

    private function convertIndividual($record) {
        // Base structure stays mostly the same
        $converted = [
            'type' => 'INDI',
            'id' => $record['id'],
            'data' => []
        ];

        foreach ($record['data'] as $item) {
            switch ($item['tag']) {
                case 'NAME':
                    // Convert older name format to new structured format
                    $nameParts = $this->parseNameParts($item['value']);
                    $converted['data'][] = [
                        'tag' => 'NAME',
                        'value' => $item['value'],
                        'children' => [
                            ['tag' => 'GIVN', 'value' => $nameParts['given']],
                            ['tag' => 'SURN', 'value' => $nameParts['surname']]
                        ]
                    ];
                    break;
                case 'SEX':
                    // Convert to new enumerated value
                    $converted['data'][] = [
                        'tag' => 'SEX',
                        'value' => in_array($item['value'], ['M', 'F', 'U', 'N']) ? $item['value'] : 'U'
                    ];
                    break;
                case 'PLAC':
                    // Convert to new place structure
                    $converted['data'][] = $this->convertPlace($item);
                    break;
                default:
                    // Most other tags can be copied as-is
                    $converted['data'][] = $item;
            }
        }

        return $converted;
    }

    private function convertFamily($record) {
        // Base structure stays mostly the same
        $converted = [
            'type' => 'FAM',
            'id' => $record['id'],
            'data' => []
        ];

        foreach ($record['data'] as $item) {
            switch ($item['tag']) {
                case 'MARR':
                    // Convert marriage event to new format
                    $converted['data'][] = [
                        'tag' => 'MARR',
                        'value' => 'Y',
                        'children' => $item['children'] ?? []
                    ];
                    break;
                case 'PLAC':
                    $converted['data'][] = $this->convertPlace($item);
                    break;
                default:
                    $converted['data'][] = $item;
            }
        }

        return $converted;
    }

    private function convertSource($record) {
        return [
            'type' => 'SOUR',
            'id' => $record['id'],
            'data' => $record['data']
        ];
    }

    private function convertRepository($record) {
        return [
            'type' => 'REPO',
            'id' => $record['id'],
            'data' => $record['data']
        ];
    }

    private function convertMedia($record) {
        $converted = [
            'type' => 'OBJE',
            'id' => $record['id'],
            'data' => []
        ];

        foreach ($record['data'] as $item) {
            if ($item['tag'] === 'FILE') {
                // Convert to new media format
                $converted['data'][] = [
                    'tag' => 'FILE',
                    'value' => $item['value'],
                    'children' => [
                        ['tag' => 'FORM', 'value' => pathinfo($item['value'], PATHINFO_EXTENSION)]
                    ]
                ];
            } else {
                $converted['data'][] = $item;
            }
        }

        return $converted;
    }

    private function convertNote($record) {
        return [
            'type' => 'NOTE',
            'id' => $record['id'],
            'data' => $record['data']
        ];
    }

    private function convertPlace($item) {
        // Convert old place format to new hierarchical format
        $parts = explode(',', $item['value']);
        $parts = array_map('trim', $parts);
        
        $place = [
            'tag' => 'PLAC',
            'value' => $item['value'],
            'children' => [
                ['tag' => 'FORM', 'value' => implode(', ', array_map(function($p) { return '[' . $p . ']'; }, $parts))]
            ]
        ];

        // Add coordinates if they exist
        if (isset($item['children'])) {
            foreach ($item['children'] as $child) {
                if (in_array($child['tag'], ['LATI', 'LONG'])) {
                    $place['children'][] = $child;
                }
            }
        }

        return $place;
    }

    private function parseNameParts($name) {
        // Parse name into given name and surname
        preg_match('/(.+?)\s*\/?(.+?)?\/?/', $name, $matches);
        return [
            'given' => $matches[1] ?? '',
            'surname' => $matches[2] ?? ''
        ];
    }
}
