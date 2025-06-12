<?php
/**
 * Service Container for HeritagePress
 *
 * Provides dependency injection and service management for the plugin.
 * This container manages the creation and lifecycle of all plugin services.
 *
 * @package HeritagePress
 * @subpackage Core
 * @since 1.0.0
 */

namespace HeritagePress\Core;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Service Container Class
 */
class ServiceContainer
{
    /**
     * Service definitions
     *
     * @var array
     */
    private $services = [];

    /**
     * Service instances (singletons)
     *
     * @var array
     */
    private $instances = [];

    /**
     * Service parameters/configuration
     *
     * @var array
     */
    private $parameters = [];

    /**
     * Register a service factory
     *
     * @param string $name Service name
     * @param callable $factory Factory function
     * @param bool $singleton Whether to create as singleton
     */
    public function register($name, $factory, $singleton = true)
    {
        $this->services[$name] = [
            'factory' => $factory,
            'singleton' => $singleton
        ];
    }

    /**
     * Get a service instance
     *
     * @param string $name Service name
     * @return mixed Service instance
     * @throws \Exception If service not found
     */
    public function get($name)
    {
        if (!isset($this->services[$name])) {
            throw new \Exception("Service '{$name}' not found in container");
        }

        $service = $this->services[$name];

        // Return existing singleton instance if available
        if ($service['singleton'] && isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        // Create new instance
        $instance = call_user_func($service['factory'], $this);

        // Store singleton instance
        if ($service['singleton']) {
            $this->instances[$name] = $instance;
        }

        return $instance;
    }

    /**
     * Check if service is registered
     *
     * @param string $name Service name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->services[$name]);
    }

    /**
     * Set a parameter value
     *
     * @param string $name Parameter name
     * @param mixed $value Parameter value
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Get a parameter value
     *
     * @param string $name Parameter name
     * @param mixed $default Default value if parameter not found
     * @return mixed Parameter value
     */
    public function getParameter($name, $default = null)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
    }

    /**
     * Get all registered service names
     *
     * @return array
     */
    public function getServiceNames()
    {
        return array_keys($this->services);
    }

    /**
     * Remove a service registration
     *
     * @param string $name Service name
     */
    public function remove($name)
    {
        unset($this->services[$name], $this->instances[$name]);
    }

    /**
     * Clear all services and instances
     */
    public function clear()
    {
        $this->services = [];
        $this->instances = [];
        $this->parameters = [];
    }
}
