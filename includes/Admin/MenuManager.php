<?php
/**
 * Menu Manager for HeritagePress
 *
 * Handles menu registration and management using dependency injection
 * and configuration-driven approach.
 *
 * @package HeritagePress
 * @subpackage Admin
 * @since 1.0.0
 */

namespace HeritagePress\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use HeritagePress\Config\MenuConfig;
use HeritagePress\Factories\ManagerFactory;
use HeritagePress\Core\ServiceContainer;
use HeritagePress\Core\ErrorHandler;

/**
 * Menu Manager Class
 *
 * Manages WordPress admin menu registration with dependency injection.
 */
class MenuManager
{
    /**
     * Service container
     *
     * @var ServiceContainer
     */
    private $container;

    /**
     * Manager factory
     *
     * @var ManagerFactory
     */
    private $manager_factory;

    /**
     * Error handler
     *
     * @var ErrorHandler
     */
    private $error_handler;

    /**
     * Constructor
     *
     * @param ServiceContainer $container Service container instance
     */
    public function __construct(ServiceContainer $container = null)
    {
        $this->container = $container ?: new ServiceContainer();
        $this->manager_factory = new ManagerFactory($this->container);
        $this->error_handler = new ErrorHandler();

        $this->init();
    }

    /**
     * Initialize menu manager
     */
    private function init()
    {
        add_action('admin_menu', [$this, 'register_menus']);
    }

    /**
     * Register WordPress admin menus
     */
    public function register_menus()
    {
        // $this->error_handler->debug('MenuManager: Registering menus');

        try {
            $this->register_main_menu();
            $this->register_submenus();
        } catch (\Exception $e) {
            $this->error_handler->error('Menu registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Register main menu page
     */
    private function register_main_menu()
    {
        $config = MenuConfig::getMainMenu();

        add_menu_page(
            $config['page_title'],
            $config['menu_title'],
            $config['capability'],
            $config['menu_slug'],
            [$this, 'render_main_page'],
            $config['icon_url'],
            $config['position']
        );
    }    /**
         * Register submenu pages
         */
    private function register_submenus()
    {
        $submenus = MenuConfig::getOrderedSubmenus();

        foreach ($submenus as $key => $submenu) {
            add_submenu_page(
                $submenu['parent_slug'],
                $submenu['page_title'],
                $submenu['menu_title'],
                $submenu['capability'],
                $submenu['menu_slug'],
                [$this, 'render_submenu_page']
            );
        }
    }

    /**
     * Render main HeritagePress page
     */
    public function render_main_page()
    {
        try {
            $this->render_page_content('main', [
                'title' => __('HeritagePress Dashboard', 'heritagepress'),
                'content' => $this->get_dashboard_content()
            ]);
        } catch (\Exception $e) {
            $this->error_handler->error('Failed to render main page: ' . $e->getMessage());
            $this->render_error_page($e);
        }
    }    /**
         * Render submenu page using manager factory
         */
    public function render_submenu_page()
    {
        try {
            $current_page = $this->get_current_page_slug();
            $manager_class = MenuConfig::getManagerForSlug($current_page);

            if (!$manager_class) {
                throw new \Exception("No manager found for page: {$current_page}");
            }

            $manager = $this->manager_factory->create($manager_class);

            if ($manager && method_exists($manager, 'render_page')) {
                $manager->render_page();
            } else {
                $this->render_fallback_page($current_page);
            }
        } catch (\Exception $e) {
            $this->error_handler->error('Failed to render submenu page: ' . $e->getMessage());
            $this->render_error_page($e);
        }
    }

    /**
     * Get current page slug
     *
     * @return string
     */
    private function get_current_page_slug()
    {
        return isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    }

    /**
     * Render page content with common wrapper
     *
     * @param string $type Page type
     * @param array $data Page data
     */
    private function render_page_content($type, $data)
    {
        echo '<div class="wrap heritagepress-admin-page">';
        echo '<h1>' . esc_html($data['title']) . '</h1>';
        echo '<div class="heritagepress-content">';
        echo $data['content'];
        echo '</div>';
        echo '</div>';
    }

    /**
     * Get dashboard content
     *
     * @return string
     */
    private function get_dashboard_content()
    {
        ob_start();
        ?>
        <div class="heritagepress-dashboard">
            <div class="welcome-panel">
                <div class="welcome-panel-content">
                    <h2><?php _e('Welcome to HeritagePress', 'heritagepress'); ?></h2>
                    <p class="about-description">
                        <?php _e('Manage your family history and genealogy data with HeritagePress.', 'heritagepress'); ?>
                    </p>
                    <div class="welcome-panel-column-container">
                        <div class="welcome-panel-column">
                            <h3><?php _e('Get Started', 'heritagepress'); ?></h3>
                            <a class="button button-primary button-hero"
                                href="<?php echo admin_url('admin.php?page=heritagepress-trees'); ?>">
                                <?php _e('Manage Trees', 'heritagepress'); ?>
                            </a>
                        </div>
                        <div class="welcome-panel-column">
                            <h3><?php _e('Import Data', 'heritagepress'); ?></h3> <a class="button button-secondary"
                                href="<?php echo admin_url('admin.php?page=heritagepress-import-export'); ?>">
                                <?php _e('Import GEDCOM', 'heritagepress'); ?>
                            </a>
                        </div>
                        <div class="welcome-panel-column">
                            <h3><?php _e('Manage Tools', 'heritagepress'); ?></h3>
                            <a class="button button-secondary"
                                href="<?php echo admin_url('admin.php?page=heritagepress-tools'); ?>">
                                <?php _e('Database Tools', 'heritagepress'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render fallback page for missing managers
     *
     * @param string $page_slug Page slug
     */
    private function render_fallback_page($page_slug)
    {
        $this->render_page_content('fallback', [
            'title' => __('Page Not Available', 'heritagepress'),
            'content' => sprintf(
                '<p>%s</p><p><a href="%s">%s</a></p>',
                sprintf(__('The page "%s" is not yet available.', 'heritagepress'), esc_html($page_slug)),
                admin_url('admin.php?page=heritagepress'),
                __('Return to Dashboard', 'heritagepress')
            )
        ]);
    }

    /**
     * Render error page
     *
     * @param \Exception $exception Exception to display
     */
    private function render_error_page(\Exception $exception)
    {
        $this->render_page_content('error', [
            'title' => __('Error', 'heritagepress'),
            'content' => sprintf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html($exception->getMessage())
            )
        ]);
    }
}