<?php
/**
 * Base Admin Manager Class
 *
 * Abstract base class for all admin managers in HeritagePress.
 * Provides common functionality and enforces consistent structure.
 *
 * @package HeritagePress
 * @subpackage Admin
 * @since 1.0.0
 */

namespace HeritagePress\Admin;

use HeritagePress\Core\ServiceContainer;

/**
 * Abstract Base Admin Manager
 */
abstract class BaseAdminManager
{
    /**
     * Service container instance
     *
     * @var ServiceContainer
     */
    protected $container;

    /**
     * Page slug for this manager
     *
     * @var string
     */
    protected $page_slug;

    /**
     * Required capability for this manager
     *
     * @var string
     */
    protected $capability = 'manage_options';

    /**
     * Constructor
     *
     * @param ServiceContainer $container Service container instance
     */
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
        $this->init();
    }

    /**
     * Initialize the manager
     * Override in subclasses for specific initialization
     */
    protected function init()
    {
        // Default implementation - can be overridden
    }

    /**
     * Handle page request
     * Must be implemented by subclasses
     */
    abstract public function handleRequest();

    /**
     * Render the admin page
     * Must be implemented by subclasses
     */
    abstract public function render();

    /**
     * Get the page slug
     *
     * @return string
     */
    public function getPageSlug(): string
    {
        return $this->page_slug;
    }

    /**
     * Get the required capability
     *
     * @return string
     */
    public function getCapability(): string
    {
        return $this->capability;
    }

    /**
     * Check if current user has required capability
     *
     * @return bool
     */
    protected function canAccess(): bool
    {
        return current_user_can($this->capability);
    }

    /**
     * Enqueue scripts for this manager
     * Override in subclasses to add specific scripts
     */
    public function enqueueScripts()
    {
        // Default implementation - can be overridden
    }

    /**
     * Enqueue styles for this manager
     * Override in subclasses to add specific styles
     */
    public function enqueueStyles()
    {
        // Default implementation - can be overridden
    }

    /**
     * Handle AJAX requests
     * Override in subclasses to handle AJAX
     *
     * @param string $action AJAX action
     */
    public function handleAjax($action)
    {
        // Default implementation - can be overridden
    }

    /**
     * Validate nonce for form submissions
     *
     * @param string $action Nonce action
     * @return bool
     */
    protected function validateNonce($action): bool
    {
        $nonce = $_POST['_wpnonce'] ?? $_GET['_wpnonce'] ?? '';
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Add admin notice
     *
     * @param string $message Notice message
     * @param string $type Notice type (success, error, warning, info)
     */
    protected function addNotice($message, $type = 'info')
    {
        add_action('admin_notices', function () use ($message, $type) {
            echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible">';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</div>';
        });
    }

    /**
     * Sanitize input data
     *
     * @param array $data Input data
     * @param array $rules Sanitization rules
     * @return array Sanitized data
     */
    protected function sanitizeInput($data, $rules): array
    {
        $sanitized = [];

        foreach ($rules as $field => $rule) {
            if (!isset($data[$field])) {
                continue;
            }

            $value = $data[$field];

            switch ($rule) {
                case 'text':
                    $sanitized[$field] = sanitize_text_field($value);
                    break;
                case 'textarea':
                    $sanitized[$field] = sanitize_textarea_field($value);
                    break;
                case 'email':
                    $sanitized[$field] = sanitize_email($value);
                    break;
                case 'url':
                    $sanitized[$field] = esc_url_raw($value);
                    break;
                case 'int':
                    $sanitized[$field] = intval($value);
                    break;
                case 'float':
                    $sanitized[$field] = floatval($value);
                    break;
                case 'boolean':
                    $sanitized[$field] = (bool) $value;
                    break;
                case 'slug':
                    $sanitized[$field] = sanitize_title($value);
                    break;
                default:
                    $sanitized[$field] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * Render template
     *
     * @param string $template Template name
     * @param array $variables Variables to pass to template
     */
    protected function renderTemplate($template, $variables = [])
    {
        $template_path = HERITAGEPRESS_PLUGIN_DIR . 'templates/admin/' . $template . '.php';

        if (!file_exists($template_path)) {
            wp_die(sprintf(__('Template not found: %s', 'heritagepress'), $template));
        }

        // Extract variables to make them available in template
        extract($variables);

        include $template_path;
    }

    /**
     * Get service from container
     *
     * @param string $service Service name
     * @return mixed Service instance
     */
    protected function getService($service)
    {
        return $this->container->get($service);
    }
}
