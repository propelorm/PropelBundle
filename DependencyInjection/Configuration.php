<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\DependencyInjection;

use Propel\Common\Config\PropelConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * This class contains the configuration information for the bundle
 */
class Configuration extends PropelConfiguration
{
    private $debug;

    public function __construct($debug = true)
    {
        $this->debug = $debug;
    }

    protected function addDatabaseSection(ArrayNodeDefinition $node)
    {
        $validAdapters = array('mysql', 'pgsql', 'sqlite', 'mssql', 'sqlsrv', 'oracle');

        $node
            ->children()
                ->arrayNode('database')
                    ->isRequired()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('connections')
                            ->isRequired()
                            ->validate()
                            ->always()
                                ->then(function($connections) {
                                    foreach ($connections as $name => $connection) {
                                        if (strpos($name, '.') !== false) {
                                            throw new \InvalidArgumentException('Dots are not allowed in connection names');
                                        }
                                    }

                                    return $connections;
                                })
                            ->end()
                            ->requiresAtLeastOneElement()
                            ->normalizeKeys(false)
                            ->useAttributeAsKey('id')
                            ->prototype('array')
                            ->fixXmlConfig('slave')
                                ->children()
                                    ->scalarNode('classname')->defaultValue($this->debug ? '\Propel\Runtime\Connection\DebugPDO' : '\Propel\Runtime\Connection\ConnectionWrapper')->end()
                                    ->scalarNode('adapter')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function ($v) { return str_replace('pdo_', '', strtolower($v)); })
                                        ->end()
                                        ->validate()
                                            ->ifNotInArray($validAdapters)
                                            ->thenInvalid('The adapter %s is not supported. Please choose one of ' . implode(', ', $validAdapters))
                                        ->end()
                                    ->end()
                                    ->scalarNode('dsn')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function ($v) { return str_replace('pdo_', '', $v); })
                                        ->end()
                                    ->end()
                                    ->scalarNode('user')->isRequired()->end()
                                    ->scalarNode('password')->isRequired()->treatNullLike('')->end()
                                    ->arrayNode('options')
                                    	->addDefaultsIfNotSet()
                                        ->children()
                                            ->booleanNode('ATTR_PERSISTENT')->defaultFalse()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('attributes')
                                    	->addDefaultsIfNotSet()
                                        ->children()
                                            ->booleanNode('ATTR_EMULATE_PREPARES')->defaultFalse()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('settings')
                                    ->fixXmlConfig('query', 'queries')
                                        ->children()
                                            ->scalarNode('charset')->defaultValue('utf8')->end()
                                            ->arrayNode('queries')
                                                ->prototype('scalar')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('slaves')
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('dsn')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('adapters')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('mysql')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('tableType')->defaultValue('InnoDB')->treatNullLike('InnoDB')->end()
                                        ->scalarNode('tableEngineKeyword')->defaultValue('ENGINE')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('sqlite')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('foreignKey')->end()
                                        ->scalarNode('tableAlteringWorkaround')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('oracle')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('autoincrementSequencePattern')->defaultValue('${table}_SEQ')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end() //adapters
                    ->end()
                ->end() //database
            ->end()
        ;
    }
}
