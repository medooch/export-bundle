<?php

namespace Medooch\Bundles\ExportBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('export');

        $rootNode->children()
            ->arrayNode('entities')
                ->useAttributeAsKey('name')->prototype('array')
                    ->children()
                        ->scalarNode('class')
                            ->validate()
                                ->ifNull()
                                ->thenInvalid('The class name can\'t be empty %s')
                            ->end()
                        ->end()
                        ->arrayNode('query')
                            ->children()
                                ->arrayNode('join')
                                    ->prototype('scalar')->end()
                                ->end()
                                ->arrayNode('select')
                                    ->prototype('scalar')->end()
                                ->end()
                                ->arrayNode('where')
                                    ->prototype('scalar')->end()
                                ->end()
                                ->arrayNode('parameters')
                                    ->prototype('scalar')->end()
                                ->end()
                                ->arrayNode('groupBy')
                                    ->prototype('scalar')->end()
                                ->end()
                                ->arrayNode('orderBy')
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
