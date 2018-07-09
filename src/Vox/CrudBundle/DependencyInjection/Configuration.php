<?php

namespace Vox\CrudBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('vox_crud');

        $rootNode->children()
            ->arrayNode('routes')
            ->useAttributeAsKey('name')
                ->arrayPrototype()
                ->children()
                    ->scalarNode('type')->end()
                    ->scalarNode('class')->end()
                    ->scalarNode('contextObject')->end()
                    ->scalarNode('controllerClass')->end()
                    ->scalarNode('strategy')->end()
                    ->scalarNode('formTemplate')->end()
                    ->scalarNode('listTemplate')->end()
                    ->scalarNode('viewTemplate')->end()
                    ->arrayNode('operations')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('queriable_fields')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('filters')
                        ->prototype('scalar')->end()
                    ->end()
        ;

        return $treeBuilder;
    }
}
