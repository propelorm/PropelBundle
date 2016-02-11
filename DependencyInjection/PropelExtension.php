<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;

/**
 * PropelExtension loads the PropelBundle configuration.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class PropelExtension extends Extension
{
    /**
     * Loads the Propel configuration.
     *
     * @param array            $configs   An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = $this->getConfiguration($configs, $container);
        $config = $processor->processConfiguration($configuration, $configs);

        if (1 === count($config['database']['connections'])) {
            $defaultConnection = array_keys($config['database']['connections'])[0];
            if (!isset($config['runtime']['defaultConnection'])) {
                $config['runtime']['defaultConnection'] = $defaultConnection;
            }
            if (!isset($config['generator']['defaultConnection'])) {
                $config['generator']['defaultConnection'] = $defaultConnection;
            }
        }
        
        $container->setParameter('propel.logging', $config['runtime']['logging']);
        $container->setParameter('propel.configuration', $config);

        // Load services
        if (!$container->hasDefinition('propel')) {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
            $loader->load('propel.xml');
            $loader->load('converters.xml');
            $loader->load('security.xml');
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/schema';
    }

    /**
     * Returns the recommended alias to use in XML.
     *
     * This alias is also the mandatory prefix to use when using YAML.
     *
     * @return string The alias
     */
    public function getAlias()
    {
        return 'propel';
    }
}
