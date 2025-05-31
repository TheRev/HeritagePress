<?php
namespace HeritagePress\Core;

class GedcomEvents {
    private static $events = [];
    private static $filters = [];
    private static $current_event = null;

    // Standard event names
    const BEFORE_PARSE = 'gedcom_before_parse';
    const AFTER_PARSE = 'gedcom_after_parse';
    const BEFORE_CONVERT = 'gedcom_before_convert';
    const AFTER_CONVERT = 'gedcom_after_convert';
    const BEFORE_SAVE = 'gedcom_before_save';
    const AFTER_SAVE = 'gedcom_after_save';
    const ERROR = 'gedcom_error';
    const WARNING = 'gedcom_warning';
    const CORRECTION = 'gedcom_correction';
    const PLACE_STANDARDIZED = 'gedcom_place_standardized';
    const MEDIA_PROCESSED = 'gedcom_media_processed';

    /**
     * Register event listener
     */
    public static function on($event, $callback, $priority = 10) {
        if (!isset(self::$events[$event])) {
            self::$events[$event] = [];
        }
        if (!isset(self::$events[$event][$priority])) {
            self::$events[$event][$priority] = [];
        }
        self::$events[$event][$priority][] = $callback;
        
        // Sort by priority after adding new callback
        ksort(self::$events[$event]);
    }

    /**
     * Add a filter
     */
    public static function addFilter($name, $callback, $priority = 10) {
        if (!isset(self::$filters[$name])) {
            self::$filters[$name] = [];
        }
        if (!isset(self::$filters[$name][$priority])) {
            self::$filters[$name][$priority] = [];
        }
        self::$filters[$name][$priority][] = $callback;
        
        // Sort by priority after adding new filter
        ksort(self::$filters[$name]);
    }

    /**
     * Apply filters to a value
     */
    public static function applyFilters($name, $value, $context = []) {
        if (!isset(self::$filters[$name])) {
            return $value;
        }

        foreach (self::$filters[$name] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $value = call_user_func($callback, $value, $context);
            }
        }

        return $value;
    }

    /**
     * Trigger event
     */
    public static function trigger($event, $data = null) {
        // Save current event for nested event handling
        $previous_event = self::$current_event;
        self::$current_event = $event;

        try {
            if (!isset(self::$events[$event])) {
                return;
            }

            foreach (self::$events[$event] as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    $result = call_user_func($callback, $data, [
                        'event' => $event,
                        'priority' => $priority,
                        'timestamp' => time()
                    ]);

                    // Allow event handlers to stop propagation
                    if ($result === false) {
                        break 2;
                    }
                }
            }
        } finally {
            // Restore previous event
            self::$current_event = $previous_event;
        }
    }

    /**
     * Remove event listener
     */
    public static function off($event, $callback = null) {
        if ($callback === null) {
            unset(self::$events[$event]);
        } else {
            if (isset(self::$events[$event])) {
                foreach (self::$events[$event] as $priority => $callbacks) {
                    $key = array_search($callback, $callbacks);
                    if ($key !== false) {
                        unset(self::$events[$event][$priority][$key]);
                        // Remove priority level if empty
                        if (empty(self::$events[$event][$priority])) {
                            unset(self::$events[$event][$priority]);
                        }
                    }
                }
                // Remove event if no callbacks left
                if (empty(self::$events[$event])) {
                    unset(self::$events[$event]);
                }
            }
        }
    }

    /**
     * Remove filter
     */
    public static function removeFilter($name, $callback = null) {
        if ($callback === null) {
            unset(self::$filters[$name]);
        } else {
            if (isset(self::$filters[$name])) {
                foreach (self::$filters[$name] as $priority => $callbacks) {
                    $key = array_search($callback, $callbacks);
                    if ($key !== false) {
                        unset(self::$filters[$name][$priority][$key]);
                        // Remove priority level if empty
                        if (empty(self::$filters[$name][$priority])) {
                            unset(self::$filters[$name][$priority]);
                        }
                    }
                }
                // Remove filter if no callbacks left
                if (empty(self::$filters[$name])) {
                    unset(self::$filters[$name]);
                }
            }
        }
    }

    /**
     * Check if an event has listeners
     */
    public static function hasListeners($event) {
        return isset(self::$events[$event]) && !empty(self::$events[$event]);
    }

    /**
     * Get current event being processed
     */
    public static function getCurrentEvent() {
        return self::$current_event;
    }

    /**
     * Get all registered events
     */
    public static function getRegisteredEvents() {
        return array_keys(self::$events);
    }

    /**
     * Get all registered filters
     */
    public static function getRegisteredFilters() {
        return array_keys(self::$filters);
    }

    /**
     * Clear all events and filters
     */
    public static function reset() {
        self::$events = [];
        self::$filters = [];
        self::$current_event = null;
    }
}
