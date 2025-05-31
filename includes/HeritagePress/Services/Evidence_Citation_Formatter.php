<?php
/**
 * Evidence Citation Formatter Service
 *
 * Formats citations according to Elizabeth Shown Mills' Evidence Explained style.
 *
 * @package HeritagePress
 */

namespace HeritagePress\Services;

use HeritagePress\Models\Source;
use HeritagePress\Models\Citation;

class Evidence_Citation_Formatter {

    /**
     * Format citation according to Evidence Explained style
     */
    public function format($source, $citation = null) {
        if (!$source) {
            return '';
        }

        // Ensure source type is a string and not null
        $source_type = is_string($source->type) ? strtolower($source->type) : 'generic';
        $method_name = 'format_' . $source_type;
        
        if (method_exists($this, $method_name)) {
            return $this->$method_name($source, $citation);
        }
        
        return $this->format_generic($source, $citation);
    }

    /**
     * Format birth record citation
     */
    private function format_birth_record($source, $citation) {
        $formatted = '';
        
        if ($source->repository) {
            $formatted .= $source->repository;
            if ($source->collection) {
                $formatted .= ', ' . $source->collection;
            }
            $formatted .= '; ';
        }
        
        $formatted .= $source->title ?: 'Birth record';
        
        if ($citation) {
            if ($citation->page_number) {
                $formatted .= ', p. ' . $citation->page_number;
            }
            if ($citation->entry_number) {
                $formatted .= ', entry ' . $citation->entry_number;
            }
            if ($citation->volume_number) {
                $formatted .= ', vol. ' . $citation->volume_number;
            }
        }
        
        if ($source->date_range_start && $source->date_range_end) {
            $formatted .= ' (' . $source->date_range_start . '–' . $source->date_range_end . ')';
        } elseif ($source->date_created) {
            $formatted .= ' (' . $source->date_created . ')';
        }
        
        if ($source->place) {
            $formatted .= '; ' . $source->place;
        }
        
        if ($citation && $citation->source_quality_assessment) {
            $assessment = json_decode($citation->source_quality_assessment, true);
            if (isset($assessment['source_originality']) && $assessment['source_originality'] === 'DERIVATIVE') {
                $formatted .= ' [derivative source]';
            }
        }
        
        return $formatted . '.';
    }

    /**
     * Format death record citation
     */
    private function format_death_record($source, $citation) {
        return $this->format_vital_record($source, $citation, 'Death');
    }

    /**
     * Format marriage record citation
     */
    private function format_marriage_record($source, $citation) {
        return $this->format_vital_record($source, $citation, 'Marriage');
    }

    /**
     * Format generic vital record
     */
    private function format_vital_record($source, $citation, $record_type) {
        $formatted = '';
        
        if ($source->repository) {
            $formatted .= $source->repository;
            if ($source->collection) {
                $formatted .= ', ' . $source->collection;
            }
            $formatted .= '; ';
        }
        
        $formatted .= $record_type . ' ';
        if ($source->title) {
            $formatted .= $source->title;
        } else {
            $formatted .= 'record';
        }
        
        if ($citation) {
            $details = [];
            if ($citation->volume_number) $details[] = 'vol. ' . $citation->volume_number;
            if ($citation->page_number) $details[] = 'p. ' . $citation->page_number;
            if ($citation->entry_number) $details[] = 'entry ' . $citation->entry_number;
            if ($citation->line_number) $details[] = 'line ' . $citation->line_number;
            
            if (!empty($details)) {
                $formatted .= ', ' . implode(', ', $details);
            }
        }
        
        if ($source->date_created) {
            $formatted .= ' (' . $source->date_created . ')';
        } elseif ($source->date_range_start) {
            $formatted .= ' (' . $source->date_range_start;
            if ($source->date_range_end) {
                $formatted .= '–' . $source->date_range_end;
            }
            $formatted .= ')';
        }
        
        if ($source->place) {
            $formatted .= '; ' . $source->place;
        }
        
        return $formatted . '.';
    }

    /**
     * Format census record citation
     */
    private function format_census($source, $citation) {
        $formatted = '';
        
        if (preg_match('/(\d{4})/', $source->title ?? '', $matches)) {
            $year = $matches[1];
            $formatted .= $year . ' U.S. census';
        } else {
            $formatted .= $source->title ?: 'Census record';
        }
        
        if ($source->place) {
            $formatted .= ', ' . $source->place;
        }
        
        if ($citation) {
            $details = [];
            if ($citation->enumeration_district) {
                $details[] = 'enumeration district ' . $citation->enumeration_district;
            }
            if ($citation->page_number) {
                $details[] = 'p. ' . $citation->page_number;
            }
            if ($citation->line_number) {
                $details[] = 'line ' . $citation->line_number;
            }
            if ($citation->dwelling_number) {
                $details[] = 'dwelling ' . $citation->dwelling_number;
            }
            if ($citation->family_number) {
                $details[] = 'family ' . $citation->family_number;
            }
            
            if (!empty($details)) {
                $formatted .= ', ' . implode(', ', $details);
            }
        }
        
        if ($source->repository) {
            $formatted .= '; ' . $source->repository;
            if ($source->collection) {
                $formatted .= ', ' . $source->collection;
            }
        }
        
        return $formatted . '.';
    }

    /**
     * Format will/probate citation
     */
    private function format_will($source, $citation) {
        $formatted = '';
        
        if ($source->title) {
            $formatted .= $source->title;
        } else {
            $formatted .= 'Will';
        }
        
        if ($source->date_created) {
            $formatted .= ' (' . $source->date_created . ')';
        }
        
        if ($source->repository) {
            $formatted .= '; ' . $source->repository;
            if ($source->place) {
                $formatted .= ', ' . $source->place;
            }
        } elseif ($source->place) {
            $formatted .= '; ' . $source->place;
        }
        
        if ($citation) {
            $details = [];
            if ($citation->book_number) $details[] = 'book ' . $citation->book_number;
            if ($citation->page_number) $details[] = 'p. ' . $citation->page_number;
            if ($citation->case_number) $details[] = 'case ' . $citation->case_number;
            
            if (!empty($details)) {
                $formatted .= ', ' . implode(', ', $details);
            }
        }
        
        return $formatted . '.';
    }

    /**
     * Format newspaper citation
     */
    private function format_newspaper($source, $citation) {
        $formatted = '';
        
        if ($citation && $citation->article_title) {
            $formatted .= '"' . $citation->article_title . '," ';
        }
        
        $formatted .= $source->title ?: 'Newspaper';
        
        if ($source->place) {
            $formatted .= ' (' . $source->place . ')';
        }
        
        if ($source->date_created) {
            $formatted .= ', ' . $source->date_created;
        }
        
        if ($citation) {
            $details = [];
            if ($citation->page_number) $details[] = 'p. ' . $citation->page_number;
            if ($citation->column_number) $details[] = 'col. ' . $citation->column_number;
            
            if (!empty($details)) {
                $formatted .= ', ' . implode(', ', $details);
            }
        }
        
        return $formatted . '.';
    }

    /**
     * Format land record citation
     */
    private function format_land($source, $citation) {
        $formatted = '';
        
        if ($source->title) {
            $formatted .= $source->title;
        } else {
            $formatted .= 'Land record';
        }
        
        if ($source->date_created) {
            $formatted .= ' (' . $source->date_created . ')';
        }
        
        if ($citation) {
            $details = [];
            if ($citation->book_number) $details[] = 'book ' . $citation->book_number;
            if ($citation->page_number) $details[] = 'p. ' . $citation->page_number;
            if ($citation->deed_number) $details[] = 'deed ' . $citation->deed_number;
            
            if (!empty($details)) {
                $formatted .= ', ' . implode(', ', $details);
            }
        }
        
        if ($source->repository) {
            $formatted .= '; ' . $source->repository;
            if ($source->place) {
                $formatted .= ', ' . $source->place;
            }
        } elseif ($source->place) {
            $formatted .= '; ' . $source->place;
        }
        
        return $formatted . '.';
    }

    /**
     * Format military record citation
     */
    private function format_military($source, $citation) {
        $formatted = '';
        
        if ($source->title) {
            $formatted .= $source->title;
        } else {
            $formatted .= 'Military record';
        }
        
        if ($citation) {
            $details = [];
            if ($citation->unit) $details[] = $citation->unit;
            if ($citation->rank) $details[] = $citation->rank;
            if ($citation->service_number) $details[] = 'service no. ' . $citation->service_number;
            
            if (!empty($details)) {
                $formatted .= ', ' . implode(', ', $details);
            }
        }
        
        if ($source->date_range_start && $source->date_range_end) {
            $formatted .= ' (' . $source->date_range_start . '–' . $source->date_range_end . ')';
        } elseif ($source->date_created) {
            $formatted .= ' (' . $source->date_created . ')';
        }
        
        if ($source->repository) {
            $formatted .= '; ' . $source->repository;
            if ($source->collection) {
                $formatted .= ', ' . $source->collection;
            }
        }
        
        if ($citation && $citation->file_number) {
            $formatted .= ', file ' . $citation->file_number;
        }
        
        return $formatted . '.';
    }

    /**
     * Format immigration record citation
     */
    private function format_immigration($source, $citation) {
        $formatted = '';
        
        $formatted .= $source->title ?: 'Immigration record';
        
        if ($citation) {
            $details = [];
            if ($citation->ship_name) $details[] = 'ship ' . $citation->ship_name;
            if ($citation->departure_port) $details[] = 'from ' . $citation->departure_port;
            if ($citation->arrival_port) $details[] = 'to ' . $citation->arrival_port;
            
            if (!empty($details)) {
                $formatted .= ', ' . implode(', ', $details);
            }
        }
        
        if ($source->date_created) {
            $formatted .= ' (' . $source->date_created . ')';
        }
        
        if ($citation) {
            $list_details = [];
            if ($citation->page_number) $list_details[] = 'p. ' . $citation->page_number;
            if ($citation->line_number) $list_details[] = 'line ' . $citation->line_number;
            if ($citation->manifest_number) $list_details[] = 'manifest ' . $citation->manifest_number;
            
            if (!empty($list_details)) {
                $formatted .= ', ' . implode(', ', $list_details);
            }
        }
        
        if ($source->repository) {
            $formatted .= '; ' . $source->repository;
            if ($source->collection) {
                $formatted .= ', ' . $source->collection;
            }
        }
        
        return $formatted . '.';
    }

    /**
     * Format book/published source citation
     */
    private function format_book($source, $citation) {
        $formatted = '';
        
        if ($source->author) {
            $formatted .= $source->author . ', ';
        }
        
        if ($source->title) {
            $formatted .= '_' . $source->title . '_';
        }
        
        $pub_info = [];
        if ($source->place) $pub_info[] = $source->place;
        if ($source->publisher) $pub_info[] = $source->publisher;
        if ($source->date_created) $pub_info[] = $source->date_created;
        
        if (!empty($pub_info)) {
            // Ensure correct formatting for publication info, typically (Place: Publisher, Year)
            $place_publisher = [];
            if ($source->place) $place_publisher[] = $source->place;
            if ($source->publisher) $place_publisher[] = $source->publisher;
            
            $formatted_pub_info = implode(': ', $place_publisher);
            if ($source->date_created) {
                $formatted_pub_info .= (', ' . $source->date_created);
            }
            $formatted .= ' (' . $formatted_pub_info . ')';
        }
        
        if ($citation && $citation->page_number) {
            $formatted .= ', ' . $citation->page_number;
        }
        
        return $formatted . '.';
    }

    /**
     * Format generic source citation
     */
    private function format_generic($source, $citation) {
        $formatted = '';
        
        if ($source->author) {
            $formatted .= $source->author . ', ';
        }
        
        if ($source->title) {
            $formatted .= '"' . $source->title . '"';
        }
        
        if ($source->date_created) {
            $formatted .= ' (' . $source->date_created . ')';
        }
        
        if ($source->repository) {
            $formatted .= '; ' . $source->repository;
            if ($source->collection) {
                $formatted .= ', ' . $source->collection;
            }
        }
        
        if ($source->place) {
            $formatted .= ', ' . $source->place;
        }
        
        if ($citation && $citation->page_number) {
            $formatted .= ', p. ' . $citation->page_number; // Corrected to add 'p. '
        }
        
        return $formatted . '.';
    }
}
