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
            $manager = new ConnectionManagerSingle();
            $manager->setConfiguration($config['connection']);

            $serviceContainer->setAdapterClass($name, $config['adapter']);
            $serviceContainer->setConnectionManager($name, $manager);
        }
    }
}
