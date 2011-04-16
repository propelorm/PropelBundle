<?php

namespace Propel\PropelBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
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
        $configuration = new Configuration($container->getParameter('kernel.debug'));
        $config = $processor->processConfiguration($configuration, $configs);

        if (!$container->hasParameter('propel.path')) {
            if (!isset($config['path'])) {
                throw new \InvalidArgumentException('The "path" parameter is mandatory.');
            } else {
                $container->setParameter('propel.path', $config['path']);
            }
        }

        if (!$container->hasParameter('propel.phing_path')) {
            if (!isset($config['phing_path'])) {
                throw new \InvalidArgumentException('The "phing_path" parameter is mandatory.');
            } else {
                $container->setParameter('propel.phing_path', $config['phing_path']);
            }
        }

        if (isset($config['charset'])) {
            $charset = $config['charset'];
        } else {
            $charset = 'UTF8';
        }

        $container->setParameter('propel.charset', $charset);

        if (isset($config['logging']) && $config['logging']) {
            $logging = $config['logging'];
        } else {
            $logging = false;
        }

        $container->setParameter('propel.logging', $logging);

        if (!empty($config['dbal'])) {
            $this->dbalLoad($config['dbal'], $container);
        } else {
            throw new \InvalidArgumentException('No "dbal" configuration found.');
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
        if (!$container->hasDefinition('propel')) {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
            $loader->load('propel.xml');
        }

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

            foreach (array('dsn', 'user', 'password', 'classname', 'options', 'attributes', 'settings') as $att) {
                if (isset($conf[$att])) {
                    $c['datasources'][$name]['connection'][$att] = $conf[$att];
                }
            }

            $c['datasources'][$name]['connection']['settings']['charset'] = array('value' => $container->getParameter('propel.charset'));
        }

        $container->getDefinition('propel.configuration')->setArguments(array($c));
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
     * Returns the namespace to be used for this extension (XML namespace).
     *
     * @return string The XML namespace
     */
    public function getNamespace()
    {
        return 'http://www.symfony-project.org/schema/dic/propel';
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
