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

        // Composer
        if (file_exists($propelPath = $container->getParameter('kernel.root_dir') . '/../vendor/propel/propel1')) {
            $container->setParameter('propel.path', $propelPath);
        }
        if (file_exists($phingPath = $container->getParameter('kernel.root_dir') . '/../vendor/phing/phing/classes')) {
            $container->setParameter('propel.phing_path', $phingPath);
        }

        if (!$container->hasParameter('propel.path')) {
            if (!isset($config['path'])) {
                throw new \InvalidArgumentException('PropelBundle expects a "path" parameter that must contain the absolute path to the Propel ORM vendor library. The "path" parameter must be defined under the "propel" root node in your configuration.');
            } else {
                $container->setParameter('propel.path', $config['path']);
            }
        }

        if (!$container->hasParameter('propel.phing_path')) {
            if (!isset($config['phing_path'])) {
                throw new \InvalidArgumentException('PropelBundle expects a "phing_path" parameter that must contain the absolute path to the Phing vendor library. The "phing_path" parameter must be defined under the "propel" root node in your configuration.');
            } else {
                $container->setParameter('propel.phing_path', $config['phing_path']);
            }
        }

        if (isset($config['logging']) && $config['logging']) {
            $logging = $config['logging'];
        } else {
            $logging = false;
        }

        $container->setParameter('propel.logging', $logging);

        // Load services
        if (!$container->hasDefinition('propel')) {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
            $loader->load('propel.xml');
            $loader->load('converters.xml');
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

        $container->getDefinition('propel.build_properties')->setArguments(array($buildProperties));

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
            $c['datasources'][$name]['adapter'] = $conf['driver'];
            if (!empty($conf['slaves'])) {
                $c['datasources'][$name]['slaves']['connection'] = $conf['slaves'];
            }

            foreach (array('dsn', 'user', 'password', 'classname', 'options', 'attributes', 'settings', 'model_paths') as $att) {
                if (isset($conf[$att])) {
                    $c['datasources'][$name]['connection'][$att] = $conf[$att];
                }
            }
        }

        // Alias the default connection if not defined
        if (!isset($c['datasources']['default'])) {
            $c['datasources']['default'] = $c['datasources'][$connectionName];
        }

        $container->getDefinition('propel.configuration')->setArguments(array($c));
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
