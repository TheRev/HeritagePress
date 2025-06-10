<?php
namespace HeritagePress\Admin;

/**
 * Handles page rendering for admin pages
 */
class PageRenderer
{
    /** @var string Plugin root path */
    private $plugin_path;

    /** @var DatabaseOperations Database operations instance */
    protected $db_ops;

    /** @var FormHandler Form handler instance */
    protected $form_handler;

    /**
     * @param string $plugin_path Plugin root path
     * @param DatabaseOperations $db_ops Database operations instance
     * @param FormHandler $form_handler Form handler instance
     */
    public function __construct($plugin_path, $db_ops, FormHandler $form_handler)
    {
        $this->plugin_path = $plugin_path;
        $this->db_ops = $db_ops;
        $this->form_handler = $form_handler;
    }

    /**
     * Render main dashboard page
     */
    public function render_main_page()
    {
        $data = [
            'recent_individuals' => $this->db_ops->get_recent_individuals(5),
            'recent_families' => $this->db_ops->get_recent_families(5),
            'recent_sources' => $this->db_ops->get_recent_sources(5),
            'recent_media' => $this->db_ops->get_recent_media(5)
        ];
        include $this->plugin_path . 'includes/Admin/templates/main.php';
    }

    /**
     * Render individuals page
     */
    public function render_individuals_page()
    {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';
        $individual_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $data = [
            'individual' => null,
            'names' => [],
            'events' => [],
            'event_types' => $this->db_ops->get_event_types()
        ];

        switch ($tab) {
            case 'edit':
                if ($individual_id) {
                    $data['individual'] = $this->db_ops->get_individual($individual_id);
                    $data['names'] = $this->db_ops->get_individual_names($individual_id);
                    $data['events'] = $this->db_ops->get_individual_events($individual_id);
                }
                include $this->plugin_path . 'includes/Admin/templates/individual-edit.php';
                break;
            default:
                $data['individuals'] = $this->db_ops->get_individuals();
                include $this->plugin_path . 'includes/Admin/templates/individual-list.php';
                break;
        }
    }

    /**
     * Render families page
     */
    public function render_families_page()
    {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';
        $family_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $data = [
            'family' => null,
            'members' => [],
            'events' => [],
            'event_types' => $this->db_ops->get_event_types()
        ];

        switch ($tab) {
            case 'edit':
                if ($family_id) {
                    $data['family'] = $this->db_ops->get_family($family_id);
                    $data['members'] = $this->db_ops->get_family_members($family_id);
                    $data['events'] = $this->db_ops->get_family_events($family_id);
                }
                include $this->plugin_path . 'includes/Admin/templates/family-edit.php';
                break;
            default:
                $data['families'] = $this->db_ops->get_families();
                include $this->plugin_path . 'includes/Admin/templates/family-list.php';
                break;
        }
    }

    /**
     * Render sources page
     */
    public function render_sources_page()
    {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';
        $source_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $data = [
            'source' => null,
            'citations' => [],
            'source_types' => $this->db_ops->get_source_types()
        ];

        switch ($tab) {
            case 'edit':
                if ($source_id) {
                    $data['source'] = $this->db_ops->get_source($source_id);
                    $data['citations'] = $this->db_ops->get_source_citations($source_id);
                }
                include $this->plugin_path . 'includes/Admin/templates/source-edit.php';
                break;
            default:
                $data['sources'] = $this->db_ops->get_sources();
                include $this->plugin_path . 'includes/Admin/templates/source-list.php';
                break;
        }
    }

    /**
     * Render media page
     */
    public function render_media_page()
    {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';
        $media_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $data = [
            'media' => null,
            'references' => []
        ];

        switch ($tab) {
            case 'edit':
                if ($media_id) {
                    $data['media'] = $this->db_ops->get_media($media_id);
                    $data['references'] = $this->db_ops->get_media_references($media_id);
                }
                include $this->plugin_path . 'includes/Admin/templates/media-edit.php';
                break;
            default:
                $data['media_items'] = $this->db_ops->get_media_items();
                include $this->plugin_path . 'includes/Admin/templates/media-list.php';
                break;
        }
    }

    /**
     * Render DNA tests page 
     */
    public function render_dna_page()
    {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';
        $test_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $data = [
            'test' => null,
            'matches' => []
        ];

        switch ($tab) {
            case 'edit':
                if ($test_id) {
                    $data['test'] = $this->db_ops->get_dna_test($test_id);
                    $data['matches'] = $this->db_ops->get_dna_matches($test_id);
                }
                include $this->plugin_path . 'includes/Admin/templates/dna-edit.php';
                break;
            default:
                $data['tests'] = $this->db_ops->get_dna_tests();
                include $this->plugin_path . 'includes/Admin/templates/dna-list.php';
                break;
        }
    }

    /**
     * Render GEDCOM page
     */
    public function render_gedcom_page()
    {
        include $this->plugin_path . 'includes/Admin/templates/gedcom.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page()
    {
        include $this->plugin_path . 'includes/Admin/templates/settings.php';
    }
}
