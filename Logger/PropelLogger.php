<?php

namespace Propel\PropelBundle\Logger;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * PropelLogger.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author William DURAND <william.durand1@gmail.com>
 */
class PropelLogger
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var array
     */
    protected $queries;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger A LoggerInterface instance
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger  = $logger;
        $this->queries = array();
    }

    /**
     * Log message.
     *
     * @param string $message  The message to log
     * @param int    $severity The numeric severity
     */
    public function log($message, $severity = 100)
    {
        if (null !== $this->logger) {
            $this->logger->log($message, $severity);
        }
    }

    /**
     * A convenience function for logging an alert event.
     *
     * @param mixed $message the message to log.
     */
    public function alert($message)
    {
        $this->log($message, 400);
    }

    /**
     * A convenience function for logging a critical event.
     *
     * @param mixed $message the message to log.
     */
    public function crit($message)
    {
        $this->log($message, 400);
    }

    /**
     * A convenience function for logging an error event.
     *
     * @param mixed $message the message to log.
     */
    public function err($message)
    {
        $this->log($message, 400);
    }

    /**
     * A convenience function for logging a warning event.
     *
     * @param mixed $message the message to log.
     */
    public function warning($message)
    {
        $this->log($message, 300);
    }

    /**
     * A convenience function for logging an critical event.
     *
     * @param mixed $message the message to log.
     */
    public function notice($message)
    {
        $this->log($message, 200);
    }

    /**
     * A convenience function for logging an critical event.
     *
     * @param mixed $message the message to log.
     */
    public function info($message)
    {
        $this->log($message, 200);
    }

    /**
     * A convenience function for logging a debug event.
     *
     * @param mixed $message the message to log.
     */
    public function debug($message)
    {
        $this->queries[] = $message;
        $this->log($message, 100);
    }

    /**
     * Returns queries.
     *
     * @return array    Queries
     */
    public function getQueries()
    {
        return $this->queries;
    }
}
