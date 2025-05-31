<?php
/**
 * Family Tree Generator Class
 * 
 * Generates family tree structures from relationship data for visualization
 * and navigation purposes.
 *
 * @package HeritagePress\Core
 */

namespace HeritagePress\Core;

use HeritagePress\Models\Individual_Model;
use HeritagePress\Models\Family_Model;
use HeritagePress\Repositories\Individual_Repository;
use HeritagePress\Repositories\Family_Repository;
use HeritagePress\Repositories\Family_Relationship_Repository;

class Family_Tree_Generator {

    private $individual_repository;
    private $family_repository;
    private $relationship_repository;
    private $tree_cache = [];
    private $generation_limit = 4; // Default number of generations to include
    
    /**
     * Constructor
     * 
     * @param Individual_Repository $individual_repository Repository for individuals
     * @param Family_Repository $family_repository Repository for families
     * @param Family_Relationship_Repository $relationship_repository Repository for relationships
     */
    public function __construct(
        Individual_Repository $individual_repository,
        Family_Repository $family_repository,
        Family_Relationship_Repository $relationship_repository
    ) {
        $this->individual_repository = $individual_repository;
        $this->family_repository = $family_repository;
        $this->relationship_repository = $relationship_repository;
    }
    
    /**
     * Set the generation limit for tree generation
     * 
     * @param int $limit Number of generations to include
     * @return self For method chaining
     */
    public function setGenerationLimit(int $limit): self {
        $this->generation_limit = max(1, $limit); // Ensure at least 1 generation
        return $this;
    }
    
    /**
     * Generate an ancestor tree for an individual
     * 
     * @param int $individual_id The individual ID to generate the tree for
     * @param string $file_id The file/tree ID the individual belongs to
     * @param int $generations Number of generations to include (overrides generation_limit)
     * @return array The tree structure as a nested array
     */
    public function generateAncestorTree(int $individual_id, string $file_id, int $generations = null): array {
        $generations = $generations ?? $this->generation_limit;
        $cache_key = "ancestor_tree_{$individual_id}_{$file_id}_{$generations}";
        
        if (isset($this->tree_cache[$cache_key])) {
            return $this->tree_cache[$cache_key];
        }
        
        $individual = $this->individual_repository->get_by_id($individual_id);
        if (!$individual) {
            return [];
        }
        
        $tree = $this->buildAncestorNode($individual, $file_id, $generations, 0);
        $this->tree_cache[$cache_key] = $tree;
        
        return $tree;
    }
    
    /**
     * Generate a descendant tree for an individual
     * 
     * @param int $individual_id The individual ID to generate the tree for
     * @param string $file_id The file/tree ID the individual belongs to
     * @param int $generations Number of generations to include (overrides generation_limit)
     * @return array The tree structure as a nested array
     */
    public function generateDescendantTree(int $individual_id, string $file_id, int $generations = null): array {
        $generations = $generations ?? $this->generation_limit;
        $cache_key = "descendant_tree_{$individual_id}_{$file_id}_{$generations}";
        
        if (isset($this->tree_cache[$cache_key])) {
            return $this->tree_cache[$cache_key];
        }
        
        $individual = $this->individual_repository->get_by_id($individual_id);
        if (!$individual) {
            return [];
        }
        
        $tree = $this->buildDescendantNode($individual, $file_id, $generations, 0);
        $this->tree_cache[$cache_key] = $tree;
        
        return $tree;
    }
    
    /**
     * Generate a hourglass tree (ancestors and descendants) for an individual
     * 
     * @param int $individual_id The individual ID to generate the tree for
     * @param string $file_id The file/tree ID the individual belongs to
     * @param int $ancestor_generations Number of ancestor generations to include
     * @param int $descendant_generations Number of descendant generations to include
     * @return array The tree structure as a nested array
     */
    public function generateHourglassTree(
        int $individual_id, 
        string $file_id, 
        int $ancestor_generations = null,
        int $descendant_generations = null
    ): array {
        $ancestor_generations = $ancestor_generations ?? $this->generation_limit;
        $descendant_generations = $descendant_generations ?? $this->generation_limit;
        $cache_key = "hourglass_tree_{$individual_id}_{$file_id}_{$ancestor_generations}_{$descendant_generations}";
        
        if (isset($this->tree_cache[$cache_key])) {
            return $this->tree_cache[$cache_key];
        }
        
        $individual = $this->individual_repository->get_by_id($individual_id);
        if (!$individual) {
            return [];
        }
        
        // Create the base node
        $node = $this->createPersonNode($individual);
        
        // Add ancestors (if any generations requested)
        if ($ancestor_generations > 0) {
            $ancestors = $this->buildAncestorsArray($individual, $file_id, $ancestor_generations, 0);
            $node['ancestors'] = $ancestors;
        }
        
        // Add descendants (if any generations requested)
        if ($descendant_generations > 0) {
            $descendants = $this->buildDescendantsArray($individual, $file_id, $descendant_generations, 0);
            $node['descendants'] = $descendants;
        }
        
        $this->tree_cache[$cache_key] = $node;
        return $node;
    }
    
    /**
     * Build an ancestor node recursively
     * 
     * @param Individual_Model $individual The individual to build the node for
     * @param string $file_id The file/tree ID
     * @param int $max_generations Maximum generations to include
     * @param int $current_generation Current generation level
     * @return array The ancestor node
     */
    private function buildAncestorNode(Individual_Model $individual, string $file_id, int $max_generations, int $current_generation): array {
        $node = $this->createPersonNode($individual);
        
        // Stop recursion if we've reached the generation limit
        if ($current_generation >= $max_generations) {
            return $node;
        }
        
        // Get parents and add them to the tree
        $parents = $individual->getParents();
        $father = null;
        $mother = null;
        
        foreach ($parents as $parent) {
            if ($parent->sex === 'M') {
                $father = $parent;
            } elseif ($parent->sex === 'F') {
                $mother = $parent;
            }
        }
        
        if ($father) {
            $node['father'] = $this->buildAncestorNode($father, $file_id, $max_generations, $current_generation + 1);
        }
        
        if ($mother) {
            $node['mother'] = $this->buildAncestorNode($mother, $file_id, $max_generations, $current_generation + 1);
        }
        
        return $node;
    }
    
    /**
     * Build ancestors as a flat array for the hourglass chart
     * 
     * @param Individual_Model $individual The individual
     * @param string $file_id The file/tree ID
     * @param int $max_generations Maximum generations to include
     * @param int $current_generation Current generation level
     * @return array The ancestors array
     */
    private function buildAncestorsArray(Individual_Model $individual, string $file_id, int $max_generations, int $current_generation): array {
        // Stop recursion if we've reached the generation limit
        if ($current_generation >= $max_generations) {
            return [];
        }
        
        $ancestors = [];
        $parents = $individual->getParents();
        
        foreach ($parents as $parent) {
            $parent_node = $this->createPersonNode($parent);
            $parent_node['relationship'] = $parent->sex === 'M' ? 'father' : 'mother';
            $parent_node['generation'] = $current_generation + 1;
            
            // Add this parent to the ancestors array
            $ancestors[] = $parent_node;
            
            // Recursively get this parent's ancestors
            $parent_ancestors = $this->buildAncestorsArray($parent, $file_id, $max_generations, $current_generation + 1);
            $ancestors = array_merge($ancestors, $parent_ancestors);
        }
        
        return $ancestors;
    }
    
    /**
     * Build a descendant node recursively
     * 
     * @param Individual_Model $individual The individual to build the node for
     * @param string $file_id The file/tree ID
     * @param int $max_generations Maximum generations to include
     * @param int $current_generation Current generation level
     * @return array The descendant node
     */
    private function buildDescendantNode(Individual_Model $individual, string $file_id, int $max_generations, int $current_generation): array {
        $node = $this->createPersonNode($individual);
        
        // Stop recursion if we've reached the generation limit
        if ($current_generation >= $max_generations) {
            return $node;
        }
        
        // Find families where this individual is a parent
        $families = $individual->getFamiliesAsParent();
        if (!empty($families)) {
            $node['families'] = [];
            
            foreach ($families as $family) {
                $family_node = [
                    'id' => $family->id,
                    'uuid' => $family->uuid,
                    'marriage_date' => $family->marriage_date,
                ];
                
                // Add spouse
                $spouse = null;
                if ($individual->sex === 'M') {
                    $spouse = $family->getWife();
                } elseif ($individual->sex === 'F') {
                    $spouse = $family->getHusband();
                }
                
                if ($spouse) {
                    $family_node['spouse'] = $this->createPersonNode($spouse);
                }
                
                // Add children
                $children = $family->getChildren();
                if (!empty($children)) {
                    $family_node['children'] = [];
                    
                    foreach ($children as $child) {
                        $child_node = $this->buildDescendantNode($child, $file_id, $max_generations, $current_generation + 1);
                        $family_node['children'][] = $child_node;
                    }
                }
                
                $node['families'][] = $family_node;
            }
        }
        
        return $node;
    }
    
    /**
     * Build descendants as a flat array for the hourglass chart
     * 
     * @param Individual_Model $individual The individual
     * @param string $file_id The file/tree ID
     * @param int $max_generations Maximum generations to include
     * @param int $current_generation Current generation level
     * @return array The descendants array
     */
    private function buildDescendantsArray(Individual_Model $individual, string $file_id, int $max_generations, int $current_generation): array {
        // Stop recursion if we've reached the generation limit
        if ($current_generation >= $max_generations) {
            return [];
        }
        
        $descendants = [];
        $children = $individual->getChildren();
        
        foreach ($children as $child) {
            $child_node = $this->createPersonNode($child);
            $child_node['relationship'] = 'child';
            $child_node['generation'] = $current_generation + 1;
            
            // Add this child to the descendants array
            $descendants[] = $child_node;
            
            // Recursively get this child's descendants
            $child_descendants = $this->buildDescendantsArray($child, $file_id, $max_generations, $current_generation + 1);
            $descendants = array_merge($descendants, $child_descendants);
        }
        
        return $descendants;
    }
    
    /**
     * Create a person node with common details
     * 
     * @param Individual_Model $individual The individual
     * @return array Person node array
     */
    private function createPersonNode(Individual_Model $individual): array {
        return [
            'id' => $individual->id,
            'uuid' => $individual->uuid,
            'name' => trim($individual->full_name()),
            'given_names' => $individual->given_names,
            'surname' => $individual->surname,
            'sex' => $individual->sex,
            'birth_date' => $individual->birth_date,
            'death_date' => $individual->death_date,
            'living' => $individual->is_living()
        ];
    }
    
    /**
     * Convert a tree to JSON format
     * 
     * @param array $tree The tree structure
     * @return string JSON encoded tree
     */
    public function treeToJson(array $tree): string {
        return json_encode($tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Generate a GEDCOM formatted tree
     * 
     * @param int $individual_id The individual ID
     * @param string $file_id The file/tree ID
     * @param int $generations Number of generations to include
     * @return string GEDCOM content
     */
    public function treeToGedcom(int $individual_id, string $file_id, int $generations = null): string {
        // This method would convert the family tree structure to a GEDCOM format
        // Implementing this method would require GEDCOM export functionality
        // For now, we're just returning a placeholder
        return "GEDCOM export not implemented yet";
    }
    
    /**
     * Generate an HTML visualization of the tree
     * 
     * @param array $tree The tree structure
     * @return string HTML representation of the tree
     */
    public function treeToHtml(array $tree): string {
        // This method would generate a basic HTML visualization of the tree structure
        // This is simplified and would need to be expanded in a real implementation
        $html = '';
        
        // Check if it's an hourglass tree
        if (isset($tree['ancestors']) || isset($tree['descendants'])) {
            $html .= $this->renderHourglassTreeHtml($tree);
        } 
        // Check if it's an ancestor tree
        elseif (isset($tree['father']) || isset($tree['mother'])) {
            $html .= $this->renderAncestorTreeHtml($tree);
        } 
        // Otherwise treat as descendant tree
        else {
            $html .= $this->renderDescendantTreeHtml($tree);
        }
        
        return $html;
    }
    
    /**
     * Render an ancestor tree as HTML
     * 
     * @param array $node The tree node to render
     * @param int $level Current level in the tree
     * @return string HTML representation
     */
    private function renderAncestorTreeHtml(array $node, int $level = 0): string {
        $indent = str_repeat('  ', $level);
        $html = $indent . '<div class="ancestor-node" data-id="' . esc_attr($node['id']) . '">';
        $html .= '<div class="person">' . esc_html($node['name']) . ' (' . esc_html($node['birth_date'] ?? '?') . ' - ' . esc_html($node['death_date'] ?? '') . ')</div>';
        
        if (isset($node['father']) || isset($node['mother'])) {
            $html .= $indent . '  <div class="parents">';
            
            if (isset($node['father'])) {
                $html .= $this->renderAncestorTreeHtml($node['father'], $level + 1);
            }
            
            if (isset($node['mother'])) {
                $html .= $this->renderAncestorTreeHtml($node['mother'], $level + 1);
            }
            
            $html .= $indent . '  </div>';
        }
        
        $html .= $indent . '</div>';
        return $html;
    }
    
    /**
     * Render a descendant tree as HTML
     * 
     * @param array $node The tree node to render
     * @param int $level Current level in the tree
     * @return string HTML representation
     */
    private function renderDescendantTreeHtml(array $node, int $level = 0): string {
        $indent = str_repeat('  ', $level);
        $html = $indent . '<div class="descendant-node" data-id="' . esc_attr($node['id']) . '">';
        $html .= '<div class="person">' . esc_html($node['name']) . ' (' . esc_html($node['birth_date'] ?? '?') . ' - ' . esc_html($node['death_date'] ?? '') . ')</div>';
        
        if (isset($node['families']) && !empty($node['families'])) {
            $html .= $indent . '  <div class="families">';
            
            foreach ($node['families'] as $family) {
                $html .= $indent . '    <div class="family" data-id="' . esc_attr($family['id']) . '">';
                
                if (isset($family['spouse'])) {
                    $html .= $indent . '      <div class="spouse">' . 
                             'Spouse: ' . esc_html($family['spouse']['name']) . 
                             ' (' . esc_html($family['spouse']['birth_date'] ?? '?') . ' - ' . 
                             esc_html($family['spouse']['death_date'] ?? '') . ')</div>';
                }
                
                if (isset($family['marriage_date'])) {
                    $html .= $indent . '      <div class="marriage">Marriage: ' . esc_html($family['marriage_date']) . '</div>';
                }
                
                if (isset($family['children']) && !empty($family['children'])) {
                    $html .= $indent . '      <div class="children">';
                    
                    foreach ($family['children'] as $child) {
                        $html .= $this->renderDescendantTreeHtml($child, $level + 2);
                    }
                    
                    $html .= $indent . '      </div>';
                }
                
                $html .= $indent . '    </div>';
            }
            
            $html .= $indent . '  </div>';
        }
        
        $html .= $indent . '</div>';
        return $html;
    }
    
    /**
     * Render an hourglass tree as HTML
     * 
     * @param array $tree The hourglass tree structure
     * @return string HTML representation
     */
    private function renderHourglassTreeHtml(array $tree): string {
        $html = '<div class="hourglass-tree">';
        
        // Center person
        $html .= '<div class="center-person" data-id="' . esc_attr($tree['id']) . '">';
        $html .= esc_html($tree['name']) . ' (' . esc_html($tree['birth_date'] ?? '?') . ' - ' . esc_html($tree['death_date'] ?? '') . ')';
        $html .= '</div>';
        
        // Ancestors (top half of hourglass)
        if (isset($tree['ancestors']) && !empty($tree['ancestors'])) {
            $html .= '<div class="ancestors">';
            $html .= '<h3>Ancestors</h3>';
            
            // Group ancestors by generation
            $ancestors_by_gen = [];
            foreach ($tree['ancestors'] as $ancestor) {
                $gen = $ancestor['generation'];
                if (!isset($ancestors_by_gen[$gen])) {
                    $ancestors_by_gen[$gen] = [];
                }
                $ancestors_by_gen[$gen][] = $ancestor;
            }
            
            // Output each generation
            ksort($ancestors_by_gen);
            foreach ($ancestors_by_gen as $gen => $ancestors) {
                $html .= '<div class="generation" data-level="' . esc_attr($gen) . '">';
                foreach ($ancestors as $ancestor) {
                    $html .= '<div class="ancestor" data-id="' . esc_attr($ancestor['id']) . '">';
                    $html .= '<span class="relationship">' . esc_html($ancestor['relationship']) . '</span>: ';
                    $html .= esc_html($ancestor['name']) . ' (' . esc_html($ancestor['birth_date'] ?? '?') . ' - ' . esc_html($ancestor['death_date'] ?? '') . ')';
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
            
            $html .= '</div>'; // End ancestors
        }
        
        // Descendants (bottom half of hourglass)
        if (isset($tree['descendants']) && !empty($tree['descendants'])) {
            $html .= '<div class="descendants">';
            $html .= '<h3>Descendants</h3>';
            
            // Group descendants by generation
            $descendants_by_gen = [];
            foreach ($tree['descendants'] as $descendant) {
                $gen = $descendant['generation'];
                if (!isset($descendants_by_gen[$gen])) {
                    $descendants_by_gen[$gen] = [];
                }
                $descendants_by_gen[$gen][] = $descendant;
            }
            
            // Output each generation
            ksort($descendants_by_gen);
            foreach ($descendants_by_gen as $gen => $descendants) {
                $html .= '<div class="generation" data-level="' . esc_attr($gen) . '">';
                foreach ($descendants as $descendant) {
                    $html .= '<div class="descendant" data-id="' . esc_attr($descendant['id']) . '">';
                    $html .= '<span class="relationship">' . esc_html($descendant['relationship']) . '</span>: ';
                    $html .= esc_html($descendant['name']) . ' (' . esc_html($descendant['birth_date'] ?? '?') . ' - ' . esc_html($descendant['death_date'] ?? '') . ')';
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
            
            $html .= '</div>'; // End descendants
        }
        
        $html .= '</div>'; // End hourglass-tree
        return $html;
    }
}
