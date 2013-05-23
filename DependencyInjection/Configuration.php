<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
* This class contains the configuration information for the bundle
*
* This information is solely responsible for how the different configuration
* sections are normalized, and merged.
*
* @author William DURAND <william.durand1@gmail.com>
*/
class Configuration implements ConfigurationInterface
{
    private $debug;

    /**
     * Constructor
     *
     * @param Boolean $debug Wether to use the debug mode
     */
    public function  __construct($debug)
    {
        $this->debug = (Boolean) $debug;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('propel');

        $this->addGeneralSection($rootNode);
        $this->addDbalSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds 'general' configuration.
     *
     * propel:
     *     path:        xxxxxxx
     *     path_phing:  xxxxxxx
     *     logging:     %kernel.debug%
     *     build_properties:
     *         xxxx.xxxx:   xxxxxx
     *         ...
     *     behaviors:
     *         fooable: My\FooableBehavior
     *         barable: src.barable.BarableBehavior
     */
    private function addGeneralSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('path')->end()
                ->scalarNode('phing_path')->end()
                ->scalarNode('logging')->defaultValue($this->debug)->end()
                ->arrayNode('build_properties')
                    ->useAttributeAsKey('key')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('behaviors')
                    ->useAttributeAsKey('key')
                    ->prototype('scalar')->end()
                ->end()
        ;
    }

    /**
     * Adds 'dbal' configuration.
     *
     * propel:
     *     dbal:
     *         driver:      mysql
     *         user:        root
     *         password:    null
     *         dsn:         xxxxxxxx
     *         options:     {}
     *         attributes:  {}
     *         settings:    {}
     *         default_connection:  xxxxxx
     */
   private function addDbalSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
            ->arrayNode('dbal')
                ->beforeNormalization()
                    ->ifNull()
                    ->then(function($v) { return array ('connections' => array('default' => array())); })
                ->end()
                ->children()
                    ->scalarNode('default_connection')->defaultValue('default')->end()
                    ->scalarNode('driver')
                        ->beforeNormalization()
                            ->always()
                            ->then(function($v) { return str_replace('pdo_', '', $v); })
                        ->end()
                        ->defaultValue('mysql')
                    ->end()
                    ->scalarNode('user')->defaultValue('root')->end()
                    ->scalarNode('password')->defaultValue('')->end()
                    ->scalarNode('dsn')
                        ->beforeNormalization()
                            ->always()
                            ->then(function($v) { return str_replace('pdo_', '', $v); })
                        ->end()
                        ->defaultValue('')
                    ->end()
                    ->scalarNode('classname')->defaultValue($this->debug ? 'DebugPDO' : 'PropelPDO')->end()
                ->end()
                ->fixXmlConfig('option')
                ->children()
                    ->arrayNode('options')
                    ->useAttributeAsKey('key')
                    ->prototype('scalar')->end()
                    ->end()
                ->end()
                ->fixXmlConfig('attribute')
                ->children()
                    ->arrayNode('attributes')
                    ->useAttributeAsKey('key')
                    ->prototype('scalar')->end()
                    ->end()
                ->end()
                ->fixXmlConfig('setting')
                ->children()
                    ->arrayNode('settings')
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->useAttributeAsKey('key')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->fixXmlConfig('connection')
                ->append($this->getDbalConnectionsNode())
            ->end()
        ;
    }

    /**
     * Returns a tree configuration for this part of configuration:
     *
     * connections:
     *     default:
     *         driver:      mysql
     *         user:        root
     *         password:    null
     *         dsn:         xxxxxxxx
     *         classname:   PropelPDO
     *         options:     {}
     *         attributes:  {}
     *         settings:    {}
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    private function getDbalConnectionsNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('connections');

        $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('driver')
                        ->beforeNormalization()
                            ->always()
                            ->then(function($v) { return str_replace('pdo_', '', $v); })
                        ->end()
                        ->defaultValue('mysql')
                    ->end()
                    ->scalarNode('user')->defaultValue('root')->end()
                    ->scalarNode('password')->defaultValue('')->end()
                    ->scalarNode('dsn')
                        ->beforeNormalization()
                            ->always()
                            ->then(function($v) { return str_replace('pdo_', '', $v); })
                        ->end()
                        ->defaultValue('')
                    ->end()
                    ->scalarNode('classname')->defaultValue($this->debug ? 'DebugPDO' : 'PropelPDO')->end()
                    ->arrayNode('slaves')
                        ->useAttributeAsKey('name')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('driver')
                                    ->beforeNormalization()
                                        ->always()
                                        ->then(function($v) { return str_replace('pdo_', '', $v); })
                                    ->end()
                                    ->defaultValue('mysql')
                                ->end()
                                ->scalarNode('user')->defaultValue('root')->end()
                                ->scalarNode('password')->defaultValue('')->end()
                                ->scalarNode('dsn')
                                    ->beforeNormalization()
                                        ->always()
                                        ->then(function($v) { return str_replace('pdo_', '', $v); })
                                    ->end()
                                    ->defaultValue('')
                                ->end()
                                ->scalarNode('classname')->defaultValue($this->debug ? 'DebugPDO' : 'PropelPDO')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->fixXmlConfig('option')
                    ->children()
                        ->arrayNode('options')
                        ->useAttributeAsKey('key')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
                ->fixXmlConfig('model_path')
                    ->children()
                        ->arrayNode('model_paths')
                        ->defaultValue(array('src/', 'vendor/'))
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
                ->fixXmlConfig('attribute')
                    ->children()
                        ->arrayNode('attributes')
                        ->useAttributeAsKey('key')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
                ->fixXmlConfig('setting')
                    ->children()
                        ->arrayNode('settings')
                        ->useAttributeAsKey('key')
                        ->prototype('array')
                            ->useAttributeAsKey('key')
                            ->prototype('scalar')
                        ->end()
                    ->end()
                ->end()
        ;

        return $node;
    }
}
