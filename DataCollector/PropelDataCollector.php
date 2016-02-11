<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\DataCollector;

use Propel\Bundle\PropelBundle\Logger\PropelLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * The PropelDataCollector collector class collects information.
 *
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class PropelDataCollector extends DataCollector
{
    protected $logger;

    public function __construct(PropelLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array(
            'queries'       => $this->buildQueries(),
            'querycount'    => $this->countQueries(),
        );
    }

    /**
     * Returns the collector name.
     *
     * @return string The collector name.
     */
    public function getName()
    {
        return 'propel';
    }

    /**
     * Returns queries.
     *
     * @return array Queries
     */
    public function getQueries()
    {
        return $this->data['queries'];
    }

    /**
     * Returns the query count.
     *
     * @return int The query count
     */
    public function getQueryCount()
    {
        return $this->data['querycount'];
    }

    /**
     * Returns the total time of queries.
     *
     * @return float The total time of queries
     */
    public function getTime()
    {
        $time = 0;
        foreach ($this->data['queries'] as $query) {
            $time += (float) $query['time'];
        }

        return $time;
    }

    /**
     * Creates an array of Build objects.
     *
     * @return array An array of Build objects
     */
    private function buildQueries()
    {
        return $this->logger->getQueries();
    }

    /**
     * Count queries.
     *
     * @return int The number of queries.
     */
    private function countQueries()
    {
        return count($this->logger->getQueries());
    }
}
