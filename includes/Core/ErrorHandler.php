<?php
/**
 * Error Handler Class
 *
 * Centralized error handling and logging for HeritagePress.
 * Updated to use ErrorHandlerService for better organization.
 *
 * @package HeritagePress
 * @subpackage Core
 * @since 1.0.0
 */

namespace HeritagePress\Core;

use HeritagePress\Services\ErrorHandlerService;

/**
 * Error Handler Class
 */
class ErrorHandler
{
    /**
     * Error handler service
     *
     * @var ErrorHandlerService
     */
    private $service;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->service = new ErrorHandlerService();
    }

    /**
     * Handle exception
     *
     * @param \Exception $exception Exception to handle
     * @param string $context Additional context
     */
    public function handle(\Exception $exception, $context = '')
    {
        $this->service->handle_exception($exception, $context);
    }

    /**
     * Log a debug message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function debug($message, $context = [])
    {
        $this->service->debug($message, $context);
    }

    /**
     * Log an info message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function info($message, $context = [])
    {
        $this->service->info($message, $context);
    }

    /**
     * Log a warning message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function warning($message, $context = [])
    {
        $this->service->warning($message, $context);
    }

    /**
     * Log an error message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function error($message, $context = [])
    {
        $this->service->error($message, $context);
    }

    /**
     * Log a critical message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function critical($message, $context = [])
    {
        $this->service->critical($message, $context);
    }

    /**
     * Format exception for user display
     *
     * @param \Exception $exception Exception to format
     * @return string Formatted exception message
     */
    public function format_exception(\Exception $exception)
    {
        return $this->service->format_exception($exception);
    }

    /**
     * Get error handler service
     *
     * @return ErrorHandlerService
     */
    public function get_service()
    {
        return $this->service;
    }
}
