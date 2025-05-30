<?php
namespace HeritagePress\Core;

use HeritagePress\Repositories\Individual_Repository;
use HeritagePress\Repositories\Family_Repository;
use HeritagePress\Core\Audit_Log_Observer;

class Container {
    private static $instance = null;
    private $services = [];

    private function __construct() {
        // It's often better to register services after construction,
        // e.g., via a dedicated bootstrap method or by the code that initializes the container.
        // For this example, I'll add a public method to register core services.
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Registers core services like observers and repositories.
     * This method can be called during plugin initialization.
     */
    public function register_core_services() {
        // Register AuditLogObserver as a singleton
        $this->singleton(AuditLogObserver::class, function() {
            return new AuditLogObserver();
        });

        // Register Individual_Repository with its AuditLogObserver dependency
        $this->register(Individual_Repository::class, function() {
            return new Individual_Repository(
                $this->get(AuditLogObserver::class)
            );
        });

        // Register Family_Repository with its AuditLogObserver dependency
        $this->register(Family_Repository::class, function() {
            return new Family_Repository(
                $this->get(AuditLogObserver::class)
            );
        });

        // Register other repositories and services here in the future
    }

    public function register($key, $concrete) {
        $this->services[$key] = $concrete;
    }

    public function singleton($key, $factory) {
        $this->services[$key] = function() use ($factory) {
            static $instance = null;
            if ($instance === null) {
                $instance = $factory();
            }
            return $instance;
        };
    }

    public function get($key) {
        if (!isset($this->services[$key])) {
            throw new \Exception("Service not found: $key");
        }

        $concrete = $this->services[$key];

        if ($concrete instanceof \Closure) {
            return $concrete();
        }

        return $concrete;
    }

    public function has($key) {
        return isset($this->services[$key]);
    }
}
