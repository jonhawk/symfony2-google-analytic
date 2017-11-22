<?php

namespace Arcana\AnalyticBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('arcana_analytic');

        $rootNode->children()
            ->scalarNode('is_enabled')->defaultValue(true)->end()
            ->scalarNode('domain')->defaultValue('www.foxycall.com')->end()
            ->scalarNode('domain_code')->defaultValue('UA-41702597-1')->end()
            ->scalarNode('usermail')->defaultValue('foxycaller@gmail.com')->end()
            ->scalarNode('userpass')->defaultValue('bernard3021')->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
