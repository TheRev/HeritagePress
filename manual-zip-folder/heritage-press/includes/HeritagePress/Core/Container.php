<?php
namespace HeritagePress\Core;

class Container {
    private static $instance = null;
    private $services = [];

    private function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
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
