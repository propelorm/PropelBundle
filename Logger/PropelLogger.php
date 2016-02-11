<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class PropelLogger implements LoggerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $queries = array();

    /**
     * @var Stopwatch
     */
    protected $stopwatch;

    use LoggerTrait;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger    A LoggerInterface instance
     * @param Stopwatch       $stopwatch A Stopwatch instance
     */
    public function __construct(LoggerInterface $logger = null, Stopwatch $stopwatch = null)
    {
        $this->logger    = $logger;
        $this->stopwatch = $stopwatch;
        $this->isPrepared = false;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param  mixed  $level
     * @param  string $message
     * @param  array  $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        if (null === $this->logger) {
            return;
        }

        $add = true;
        $stackTrace = $this->getStackTrace();

        if (null !== $this->stopwatch) {
            $trace = debug_backtrace();
            $method = $trace[3]['function'];

            $watch = 'Propel Query '.(count($this->queries)+1);
            if ('prepare' === $method) {
                $this->isPrepared = true;
                $this->stopwatch->start($watch, 'propel');

                $add = false;
            } elseif ($this->isPrepared) {
                $this->isPrepared = false;
                $event = $this->stopwatch->stop($watch);
            }
        }

        // $trace[2] has no 'object' key if an exception is thrown while executing a query
        if ($add && isset($event) && isset($trace[2]['object'])) {
            $connection = $trace[2]['object'];

            $this->queries[] = array(
                'sql'           => $message,
                'connection'    => $connection->getName(),
                'time'          => $event->getDuration() / 1000,
                'memory'        => $event->getMemory(),
                'stackTrace'    => $stackTrace,
            );
        }

        $this->logger->log($level, $message, $context);
    }

    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * Returns the current stack trace.
     *
     * @return array
     */
    private function getStackTrace()
    {
        $e = new \Exception();
        $trace = explode("\n", $e->getTraceAsString());
        $trace = array_reverse($trace);
        array_shift($trace); // remove {main}
        array_pop($trace); // remove call to this method

        foreach ($trace as $i => &$value) {
            $value = $i + 1 . ')' . substr($value, strpos($value, ' '));
            $value = preg_replace('/\((\d+)\)/', ':$1', $value, 1);
        }

        return $trace;
    }
}
