<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\DependencyInjection;

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

    protected function addRuntimeSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('runtime')
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('connection')
                    ->children()
                        ->scalarNode('defaultConnection')->end()
                        ->arrayNode('connections')
                            ->prototype('scalar')->end()
                        ->end()
                        ->booleanNode('logging')->defaultValue($this->debug)->end()
                        ->arrayNode('log')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('type')->end()
                                    ->scalarNode('path')->end()
                                    ->enumNode('level')->values(array(100, 200, 250, 300, 400, 500, 550, 600))->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('profiler')
                            ->children()
                                ->scalarNode('classname')->defaultValue('\Propel\Runtime\Util\Profiler')->end()
                                ->floatNode('slowTreshold')->defaultValue(0.1)->end()
                                ->arrayNode('details')
                                    ->children()
                                        ->arrayNode('time')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                                ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('memory')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                                ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('memDelta')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                                ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('memPeak')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                                ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->scalarNode('innerGlue')->defaultValue(':')->end()
                                ->scalarNode('outerGlue')->defaultValue('|')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end() //runtime
            ->end();
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
                            ->fixXmlConfig('model_path')
                                ->children()
                                    ->scalarNode('classname')->defaultValue($this->debug ? '\Propel\Runtime\Connection\DebugPDO' : '\Propel\Runtime\Connection\ConnectionWrapper')->end()
                                    ->scalarNode('adapter')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function ($v) { return preg_replace('/^pdo_/', '', strtolower($v)); })
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
                                            ->then(function ($v) { return preg_replace('/^pdo_/', '', $v); })
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
                                    ->arrayNode('model_paths')
                                        ->defaultValue(['src', 'vendor'])
                                        ->prototype('scalar')->end()
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
                                                ->scalarNode('dsn')
                                                    ->beforeNormalization()
                                                    ->ifString()
                                                    ->then(function ($v) { return preg_replace('/^pdo_/', '', $v); })
                                                    ->end()
                                                ->end()
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
