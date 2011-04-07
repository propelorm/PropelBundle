<?php

/*
 * This file is part of the Propel package.
 *
 * (c) William DURAND <william.durand1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\PropelBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The PropelDataCollector collector class collects information.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class PropelDataCollector extends DataCollector
{
    /**
     * Propel logger
     *
     * @var Propel\PropelBundle\Logger\PropelLogger
     */
    private $logger;
    /**
     * Connection name
     *
     * @var string
     */
    private $connectionName;

    /**
     * Constructor
     *
     * @param \Propel\PropelBundle\Logger\PropelLogger $logger  A PropelLogger
     * @param string $connectionName    A connection name
     */
    public function __construct(\Propel\PropelBundle\Logger\PropelLogger $logger, $connectionName)
    {
        $this->logger = $logger;
        $this->connectionName = $connectionName;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array(
            'queries'        => $this->logger->getQueries(),
            'connectionName' => $this->connectionName,
        );
    }

    /**
     * Returns the collector name.
     *
     * @return string   The collector name.
     */
    public function getName()
    {
        return 'propel';
    }

    /**
     * Returns queries.
     *
     * @return array    Queries
     */
    public function getQueries()
    {
        return $this->data['queries'];
    }

    /**
     * Returns the query count.
     *
     * @return integer  The query count
     */
    public function getQueryCount()
    {
        return count($this->data['queries']);
    }

    /**
     * Returns the connection name.
     *
     * @return string   The connection name
     */
    public function getConnectionName()
    {
        return $this->data['connectionName'];
    }
}
