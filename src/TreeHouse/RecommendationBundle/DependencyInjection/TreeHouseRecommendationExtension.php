<?php

namespace TreeHouse\RecommendationBundle\DependencyInjection;

use GuzzleHttp\Client;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use TreeHouse\RecommendationBundle\Recommendation\Engine\OtrslsoClient;

class TreeHouseRecommendationExtension extends Extension
{
    /**
     * @inheritdoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        // store configuration parameters in the container
        $this->setParameters($container, $config, ['tree_house.recommendation']);

        $clientId = $this->loadEngineClient($config['engine'], $container);

        $this->loadEngineConfiguration($config, $container, $clientId);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     * @param array            $prefixes
     */
    private function setParameters(ContainerBuilder $container, array $config, array $prefixes)
    {
        foreach ($config as $key => $value) {
            $newPrefixes = array_merge($prefixes, [$key]);

            if (is_array($value) && !is_numeric(key($value))) {
                $this->setParameters($container, $value, $newPrefixes);
                continue;
            }

            $name = implode('.', $newPrefixes);
            $container->setParameter($name, $value);
        }
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     *
     * @return string
     */
    private function loadEngineClient(array $config, ContainerBuilder $container)
    {
        $guzzle = new Definition(Client::class);
        $guzzle->setPublic(false);
        $guzzle->setArguments([
            [
                'base_uri' => $config['endpoint'],
                'timeout' => $config['timeout'],
            ],
        ]);

        $guzzleClientId = 'tree_house.recommendation.engine.guzzle_client';
        $container->setDefinition($guzzleClientId, $guzzle);

        $engineClientId = 'tree_house.recommendation.engine.client';
        $definition = $container->getDefinition($engineClientId);
        $definition->setClass(OtrslsoClient::class);
        $definition->setArguments([
            new Reference($guzzleClientId),
            $config['site_id'],
        ]);

        return $engineClientId;
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     * @param string           $clientId
     */
    private function loadEngineConfiguration(array $config, ContainerBuilder $container, $clientId)
    {
        $engine = $container->getDefinition('tree_house.recommendation.engine');
        $engine->replaceArgument(0, new Reference($clientId));
        $engine->replaceArgument(1, new Reference($config['cache']));
    }
}
