<?php
namespace HeritagePress\Models;

/**
 * DNA test model
 */
class DnaTest extends Model
{
    /**
     * Database table name
     *
     * @var string
     */
    protected $table = 'dna_tests';

    /**
     * Get all DNA tests for an individual
     *
     * @param int $individual_id Individual ID
     * @return array Array of DNA test records
     */
    public function getIndividualTests($individual_id)
    {
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE individual_id = %d ORDER BY test_date",
                $individual_id
            )
        );
    }

    /**
     * Get all matches for a DNA test
     *
     * @param int $test_id Test ID
     * @return array Array of DNA match records
     */
    public function getMatches($test_id)
    {
        $matches_table = $this->db->prefix . 'hp_dna_matches';
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT m.*, COUNT(s.id) as segment_count 
                FROM {$matches_table} m 
                LEFT JOIN " . $this->db->prefix . "hp_dna_segments s ON s.match_id = m.id 
                WHERE m.test_id = %d 
                GROUP BY m.id 
                ORDER BY m.shared_cm DESC",
                $test_id
            )
        );
    }

    /**
     * Add a DNA match
     *
     * @param int   $test_id Test ID
     * @param array $data    Match data
     * @return int|false The match ID if successful, false on failure
     */
    public function addMatch($test_id, $data)
    {
        $matches_table = $this->db->prefix . 'hp_dna_matches';
        $segments_table = $this->db->prefix . 'hp_dna_segments';

        $match_data = array_merge($data, ['test_id' => $test_id]);
        $segments = isset($match_data['segments']) ? $match_data['segments'] : [];
        unset($match_data['segments']);

        $this->db->begin_transaction();

        try {
            // Insert match record
            $result = $this->db->insert($matches_table, $match_data);
            if (!$result) {
                throw new \Exception('Failed to insert match record');
            }

            $match_id = $this->db->insert_id;

            // Insert segment records
            foreach ($segments as $segment) {
                $segment['match_id'] = $match_id;
                $result = $this->db->insert($segments_table, $segment);
                if (!$result) {
                    throw new \Exception('Failed to insert segment record');
                }
            }

            $this->db->commit();
            return $match_id;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Get segments for a DNA match
     *
     * @param int $match_id Match ID
     * @return array Array of DNA segment records
     */
    public function getMatchSegments($match_id)
    {
        $segments_table = $this->db->prefix . 'hp_dna_segments';
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$segments_table} 
                WHERE match_id = %d 
                ORDER BY chromosome, start_position",
                $match_id
            )
        );
    }
}
