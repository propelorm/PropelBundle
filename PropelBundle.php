<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle;

use Propel\Runtime\Propel;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Connection\ConnectionManagerMasterSlave;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * PropelBundle
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class PropelBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->configureConnections();

        if ($this->container->getParameter('propel.logging')) {
            $this->configureLogging();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
    }

    protected function configureConnections()
    {
        $connections_config = $this->container->getParameter('propel.configuration');
        $default_datasource = $this->container->getParameter('propel.dbal.default_connection');

        $serviceContainer = Propel::getServiceContainer();
        $serviceContainer->setDefaultDatasource($default_datasource);

        foreach ($connections_config as $name => $config) {
            if (isset($config['slaves'])) {
                $manager = new ConnectionManagerMasterSlave();

                // configure the master (write) connection
                $manager->setWriteConfiguration($config['connection']);
                // configure the slave (read) connections
                $manager->setReadConfiguration($config['slaves']);
            } else {
                $manager = new ConnectionManagerSingle();
                $manager->setConfiguration($config['connection']);
            }

            $serviceContainer->setAdapterClass($name, $config['adapter']);
            $serviceContainer->setConnectionManager($name, $manager);
        }
    }

    protected function configureLogging()
    {
        $serviceContainer = Propel::getServiceContainer();
        $serviceContainer->setLogger('defaultLogger', $this->container->get('propel.logger'));

        foreach ($serviceContainer->getConnectionManagers() as $manager) {
            $connection = $manager->getReadConnection($serviceContainer->getAdapter($manager->getName()));
            $connection->setLogMethods(array_merge($connection->getLogMethods(), array('prepare')));

            $connection = $manager->getWriteConnection();
            $connection->setLogMethods(array_merge($connection->getLogMethods(), array('prepare')));
        }
    }
}
