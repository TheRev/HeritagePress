<?php
/**
 * Error Handler Service
 *
 * Standardized error handling across all components.
 * Provides consistent error logging and user feedback.
 *
 * @package HeritagePress
 * @subpackage Services
 * @since 1.0.0
 */

namespace HeritagePress\Services;

/**
 * Error Handler Service Class
 */
class ErrorHandlerService
{
    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    /**
     * Whether debug mode is enabled
     *
     * @var bool
     */
    private $debug_mode;

    /**
     * Log file path
     *
     * @var string
     */
    private $log_file;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        $this->log_file = WP_CONTENT_DIR . '/debug.log';
    }

    /**
     * Handle exception
     *
     * @param \Exception $exception Exception to handle
     * @param string $context Additional context
     */
    public function handle_exception(\Exception $exception, $context = '')
    {
        $message = sprintf(
            'HeritagePress Exception: %s in %s:%d%s',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $context ? ' [Context: ' . $context . ']' : ''
        );

        $this->log(self::LEVEL_ERROR, $message);

        if ($this->debug_mode) {
            $this->log(self::LEVEL_DEBUG, $exception->getTraceAsString());
        }
    }

    /**
     * Log error message
     *
     * @param string $level Log level
     * @param string $message Message to log
     * @param array $context Additional context data
     */
    public function log($level, $message, $context = [])
    {
        $timestamp = current_time('Y-m-d H:i:s');
        $context_str = !empty($context) ? ' ' . json_encode($context) : '';

        $log_entry = sprintf(
            '[%s] [%s] HeritagePress: %s%s',
            $timestamp,
            strtoupper($level),
            $message,
            $context_str
        );

        // Use WordPress error_log function
        error_log($log_entry);

        // If debug mode is off and this is a debug message, don't log it
        if (!$this->debug_mode && $level === self::LEVEL_DEBUG) {
            return;
        }
    }

    /**
     * Log debug message
     *
     * @param string $message Debug message
     * @param array $context Additional context
     */
    public function debug($message, $context = [])
    {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log info message
     *
     * @param string $message Info message
     * @param array $context Additional context
     */
    public function info($message, $context = [])
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message Warning message
     * @param array $context Additional context
     */
    public function warning($message, $context = [])
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log error message
     *
     * @param string $message Error message
     * @param array $context Additional context
     */
    public function error($message, $context = [])
    {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log critical message
     *
     * @param string $message Critical message
     * @param array $context Additional context
     */
    public function critical($message, $context = [])
    {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Format exception for display
     *
     * @param \Exception $exception Exception to format
     * @return string Formatted exception message
     */
    public function format_exception(\Exception $exception)
    {
        if ($this->debug_mode) {
            return sprintf(
                'Error: %s (File: %s, Line: %d)',
                $exception->getMessage(),
                basename($exception->getFile()),
                $exception->getLine()
            );
        }

        return 'An error occurred. Please try again or contact support.';
    }

    /**
     * Get debug mode status
     *
     * @return bool
     */
    public function is_debug_mode()
    {
        return $this->debug_mode;
    }
}
