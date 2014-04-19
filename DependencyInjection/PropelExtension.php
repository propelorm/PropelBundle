<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\DependencyInjection;

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

        $logging = isset($config['logging']) && $config['logging'];

        $container->setParameter('propel.logging', $logging);
        $container->setParameter('propel.configuration', array());

        // Load services
        if (!$container->hasDefinition('propel')) {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
            $loader->load('propel.xml');
            $loader->load('converters.xml');
            $loader->load('security.xml');
        }

        // build properties
        if (isset($config['build_properties']) && is_array($config['build_properties'])) {
            $buildProperties = $config['build_properties'];
        } else {
            $buildProperties = array();
        }

        // behaviors
        if (isset($config['behaviors']) && is_array($config['behaviors'])) {
            foreach ($config['behaviors'] as $name => $class) {
                $buildProperties[sprintf('propel.behavior.%s.class', $name)] = $class;
            }
        }

        $container->setParameter('propel.build_properties', $buildProperties);

        if (!empty($config['dbal'])) {
            $this->dbalLoad($config['dbal'], $container);
        }
    }

    /**
     * Loads the DBAL configuration.
     *
     * @param array            $config    An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function dbalLoad(array $config, ContainerBuilder $container)
    {
        if (empty($config['default_connection'])) {
            $keys = array_keys($config['connections']);
            $config['default_connection'] = reset($keys);
        }

        $connectionName = $config['default_connection'];
        $container->setParameter('propel.dbal.default_connection', $connectionName);

        if (0 === count($config['connections'])) {
            $config['connections'] = array($connectionName => $config);
        }

        $c = array();
        foreach ($config['connections'] as $name => $conf) {
            $c[$name]['adapter'] = $conf['driver'];
            if (!empty($conf['slaves'])) {
                $c[$name]['slaves']['connection'] = $conf['slaves'];
            }

            foreach (array('dsn', 'user', 'password', 'classname', 'options', 'attributes', 'settings', 'model_paths') as $att) {
                if (array_key_exists($att, $conf)) {
                    $c[$name]['connection'][$att] = $conf[$att];
                }
            }
        }

        // Alias the default connection if not defined
        if (!isset($c['default'])) {
            $c['default'] = $c[$connectionName];
        }

        $container->setParameter('propel.configuration', $c);
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
