<?php

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
     *     charset:     "UTF8"
     *     logging:     %kernel.debug%
     */
    private function addGeneralSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('path')->end()
                ->scalarNode('phing_path')->end()
                ->scalarNode('charset')->defaultValue('UTF8')->end()
                ->scalarNode('logging')->defaultValue($this->debug)->end()
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
                    ->scalarNode('driver')->defaultValue('mysql')->end()
                    ->scalarNode('user')->defaultValue('root')->end()
                    ->scalarNode('password')->defaultValue('')->end()
                    ->scalarNode('dsn')->defaultValue('')->end()
                    ->scalarNode('classname')->defaultValue($this->debug ? 'DebugPDO' : 'PropelPDO')->end()
                    ->scalarNode('default_connection')->defaultValue('default')->end()
                ->end()
                ->fixXmlConfig('option')
                ->children()
                    ->arrayNode('options')
                    ->useAttributeAsKey('key')
                    ->prototype('scalar')->end()
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
                ->scalarNode('driver')->defaultValue('mysql')->end()
                ->scalarNode('user')->defaultValue('root')->end()
                ->scalarNode('password')->defaultNull()->end()
                ->scalarNode('dsn')->defaultValue('')->end()
                ->scalarNode('classname')->defaultValue($this->debug ? 'DebugPDO' : 'PropelPDO')->end()
            ->end()
            ->fixXmlConfig('options')
                ->children()
                    ->arrayNode('options')
                    ->useAttributeAsKey('key')
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
