<?php

namespace Propel\PropelBundle\Logger;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * PropelLogger.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author William DURAND <william.durand1@gmail.com>
 */
class PropelLogger implements \BasicLogger
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
    public function log($message, $severity = 6)
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
        if (null !== $this->logger) {
            $this->logger->alert($message);
        }
    }

    /**
     * A convenience function for logging a critical event.
     *
     * @param mixed $message the message to log.
     */
    public function crit($message)
    {
        if (null !== $this->logger) {
            $this->logger->crit($message);
        }
    }

    /**
     * A convenience function for logging an error event.
     *
     * @param mixed $message the message to log.
     */
    public function err($message)
    {
        if (null !== $this->logger) {
            $this->logger->err($message);
        }
    }

    /**
     * A convenience function for logging a warning event.
     *
     * @param mixed $message the message to log.
     */
    public function warning($message)
    {
        if (null !== $this->logger) {
            $this->logger->warning($message);
        }
    }

    /**
     * A convenience function for logging an critical event.
     *
     * @param mixed $message the message to log.
     */
    public function notice($message)
    {
        if (null !== $this->logger) {
            $this->logger->notice($message);
        }
    }

    /**
     * A convenience function for logging an critical event.
     *
     * @param mixed $message the message to log.
     */
    public function info($message)
    {
        if (null !== $this->logger) {
            $this->logger->info($message);
        }
    }

    /**
     * A convenience function for logging a debug event.
     *
     * @param mixed $message the message to log.
     */
    public function debug($message)
    {
        $this->queries[] = $message;

        if (null !== $this->logger) {
            $this->logger->debug($message);
        }
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
