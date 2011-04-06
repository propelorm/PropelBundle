<?php

/*
 * This file is part of the FOSPropel package.
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

    private $connectionName;

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
            'connectionName' => $this->connectionName,
        );
    }

    public function getName()
    {
        return 'propel';
    }

    public function getConnectionName()
    {
        return $this->data['connectionName'];
    }
}
