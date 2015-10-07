<?php

namespace TreeHouse\RecommendationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tree_house_recommendation')->children();

        $engines = ['otrslso'];

        $engineNode = $rootNode
            ->arrayNode('engine')
            ->addDefaultsIfNotSet()
        ;

        $engineNode
            ->children()
            // TODO replace with enum type once we support more engines
            ->scalarNode('type')
            ->defaultValue('otrslso')
            ->validate()
                ->ifNotInArray($engines)
                ->thenInvalid(
                    sprintf('Only the following engines are supported: %s. You provided %%s', implode(', ', $engines))
                )
        ;

        $this->addOtrslsoConfiguration($engineNode);

        $rootNode
            ->scalarNode('cache')
            ->info('Service id of the cache to use')
            ->isRequired()
        ;

        return $treeBuilder;
    }

    /**
     * @todo replace with dynamic config once we support multiple engines
     *
     * @param ArrayNodeDefinition $node
     */
    private function addOtrslsoConfiguration(ArrayNodeDefinition $node)
    {
        $children = $node->children();

        $children
            ->integerNode('site_id')
            ->info('The site id for Otrslso')
            ->isRequired()
        ;

        $children
            ->scalarNode('endpoint')
            ->info('The endpoint for the Otrslso API')
            ->defaultValue('https://api.otrslso.com')
        ;

        $children
            ->integerNode('timeout')
            ->info('Timeout for requests. Should be set to a low value to prevent long wait times when the service is not responsive')
            ->defaultValue(1)
        ;
    }
}
