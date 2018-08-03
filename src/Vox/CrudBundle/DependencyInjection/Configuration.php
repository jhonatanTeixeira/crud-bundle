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
                    ->scalarNode('filters_type')->end()
                    ->arrayNode('list_options')
                    ->children()
                        ->arrayNode('actions')
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('list_fields')
                            ->scalarPrototype()->end()
                        ->end()
                        ->scalarNode('title')->end()
                        ->scalarNode('filter_type')->end()
                        ->booleanNode('use_simple_filter')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('has_new_button')
                            ->defaultTrue()
                        ->end()
                        ->arrayNode('simple_filter_fields')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
        ;

        return $treeBuilder;
    }
}
