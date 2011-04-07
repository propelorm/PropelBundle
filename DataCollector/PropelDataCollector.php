<?php

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
            'queries'        => $this->buildQueries(),
            'querycount'     => \Propel::getConnection($this->connectionName)->getQueryCount(),
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
     * Creates an array of Build objects.
     *
     * @return array  An array of Build objects
     */
    private function buildQueries()
    {
        $queries = array();

        $config    = \Propel::getConfiguration(\PropelConfiguration::TYPE_OBJECT);
        $outerGlue = $config->getParameter('debugpdo.logging.outerglue', ' | ');
        $innerGlue = $config->getParameter('debugpdo.logging.innerglue', ': ');

        foreach($this->logger->getQueries() as $q)
        {
            $parts     = explode($outerGlue, $q);

            $times     = explode($innerGlue, $parts[0]);
            $memories  = explode($innerGlue, $parts[1]);

            $sql       = trim($parts[2]);
            $time      = trim($times[1]);
            $memory    = trim($memories[1]);

            $queries[] = new Query($sql, $time, $memory);
        }

        return $queries;
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
        return count($this->data['querycount']);
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
