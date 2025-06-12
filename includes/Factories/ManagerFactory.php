<?php
/**
 * Manager Factory
 *
 * Factory class for creating admin manager instances.
 * Keeps the MenuManager lean by handling manager instantiation.
 *
 * @package HeritagePress
 * @subpackage Factories
 * @since 1.0.0
 */

namespace HeritagePress\Factories;

use HeritagePress\Core\ServiceContainer;
use HeritagePress\Core\ErrorHandler;

/**
 * Factory for creating admin manager instances
 *
 * Centralizes manager creation logic and keeps files small.
 */
class ManagerFactory
{
    /**
     * Service container
     *
     * @var ServiceContainer
     */
    private $container;

    /**
     * Manager namespace
     *
     * @var string
     */
    private $namespace = 'HeritagePress\\Admin\\';

    /**
     * Constructor
     *
     * @param ServiceContainer $container
     */
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }    /**
         * Create a manager instance
         *
         * @param string $manager_class Manager class name
         * @return object|null Manager instance or null on error
         */
    public function create($manager_class)
    {
        try {
            $full_class_name = $this->namespace . $manager_class;

            if (!class_exists($full_class_name)) {
                error_log("Manager class not found: {$full_class_name}");
                return null;
            }

            // Check if constructor accepts ServiceContainer parameter
            $reflection = new \ReflectionClass($full_class_name);
            $constructor = $reflection->getConstructor();

            if ($constructor && $constructor->getNumberOfParameters() > 0) {
                $first_param = $constructor->getParameters()[0];

                // If first parameter expects ServiceContainer, pass it
                if (
                    $first_param->getType() &&
                    $first_param->getType()->getName() === ServiceContainer::class
                ) {
                    return new $full_class_name($this->container);
                }
            }

            // Default: instantiate without parameters for backward compatibility
            return new $full_class_name();

        } catch (\Exception $e) {
            error_log('ManagerFactory error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get manager for menu slug
     *
     * @param string $menu_slug Menu slug
     * @return object|null Manager instance or null if not found
     */
    public function getManagerForSlug($menu_slug)
    {
        $manager_mapping = [
            'heritagepress-trees' => 'TreesManager',
            'heritagepress-individuals' => 'IndividualsManager',
            'heritagepress-import-export' => 'ImportExportManager',
            'heritagepress-tools' => 'TableManager'
        ];

        $manager_class = $manager_mapping[$menu_slug] ?? null;

        if (!$manager_class) {
            return null;
        }

        return $this->create($manager_class);
    }

    /**
     * Check if manager exists
     *
     * @param string $manager_class Manager class name
     * @return bool
     */
    public function exists($manager_class)
    {
        $full_class_name = $this->namespace . $manager_class;
        return class_exists($full_class_name);
    }

    /**
     * Get all available managers
     *
     * @return array List of available manager classes
     */
    public function getAvailableManagers()
    {
        return [
            'TreesManager',
            'IndividualsManager',
            'ImportExportManager',
            'TableManager'
        ];
    }
}
