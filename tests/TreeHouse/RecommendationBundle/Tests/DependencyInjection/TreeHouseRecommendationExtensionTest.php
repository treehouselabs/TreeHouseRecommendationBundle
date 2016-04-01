<?php

namespace TreeHouse\RecommendationBundle\Tests\DependencyInjection;

use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Http\Message\StreamFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use TreeHouse\RecommendationBundle\DependencyInjection\TreeHouseRecommendationExtension;
use TreeHouse\RecommendationBundle\Recommendation\Engine\ClientInterface;
use TreeHouse\RecommendationBundle\Recommendation\Engine\RandomNumberClientMock;
use TreeHouse\RecommendationBundle\Recommendation\EngineInterface;

class TreeHouseRecommendationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_creates_necessary_services()
    {
        $endpoint = 'https://api.example.org';
        $timeout = 2;

        $container = $this->getContainer(<<<YAML
services:
  cache_driver:
    class: TreeHouse\Cache\Driver\ArrayDriver

  cache_serializer:
    class: TreeHouse\Cache\Serializer\PhpSerializer

  cache_service:
    class: TreeHouse\Cache\Cache
    arguments:
      - '@cache_driver'
      - '@cache_serializer'

tree_house_recommendation:
  cache: cache_service
  engine:
    type: otrslso
    site_id: 1
    endpoint: $endpoint
    timeout: $timeout
YAML
        );

        // assert the HTTP client
        $this->assertTrue(
            $container->hasDefinition('tree_house.recommendation.engine.http_client'),
            'The extension should have created a HTTP client to perform requests with'
        );

        $client = $container->getDefinition('tree_house.recommendation.engine.http_client');
        $this->assertTrue(
            is_a($client->getClass(), HttpClient::class, true),
            sprintf('HTTP client must be an instance of %s', HttpClient::class)
        );

        $messageFactory = $client->getArgument(0);
        $streamFactory = $client->getArgument(1);
        $options = $client->getArgument(2);

        $this->assertTrue(
            is_a($messageFactory->getClass(), MessageFactory::class, true),
            sprintf('First argument for HTTP client must be an instance of %s', MessageFactory::class)
        );
        $this->assertTrue(
            is_a($streamFactory->getClass(), StreamFactory::class, true),
            sprintf('First argument for HTTP client must be an instance of %s', StreamFactory::class)
        );
        $this->assertEquals([CURLOPT_TIMEOUT => $timeout], $options);

        // assert the engine client
        $this->assertTrue(
            $container->hasDefinition('tree_house.recommendation.engine.client'),
            'The extension should have created a recommendation engine client'
        );

        $client = $container->getDefinition('tree_house.recommendation.engine.client');
        $this->assertTrue(
            is_a($client->getClass(), ClientInterface::class, true),
            sprintf('Engine client must be an instance of %s', ClientInterface::class)
        );
        $this->assertEquals(
            'tree_house.recommendation.engine.http_client',
            (string) $client->getArgument(0),
            'The recommendation engine client should receive the HTTP client'
        );

        // assert the engine
        $this->assertTrue(
            $container->hasDefinition('tree_house.recommendation.engine'),
            'The extension should have an engine service'
        );

        $engine = $container->getDefinition('tree_house.recommendation.engine');

        $this->assertTrue(
            is_a($engine->getClass(), EngineInterface::class, true),
            sprintf('Engine must be an instance of %s', EngineInterface::class)
        );
        $this->assertEquals('tree_house.recommendation.engine.client', (string) $engine->getArgument(0));
        $this->assertEquals('cache_service', (string) $engine->getArgument(1));
        $this->assertTrue($engine->hasTag('monolog.logger'));

        // assert the Twig extension
        $this->assertTrue(
            $container->hasDefinition('tree_house.recommendation.twig.extension'),
            'The extension should register a Twig extension'
        );
        $twig = $container->getDefinition('tree_house.recommendation.twig.extension');
        $this->assertEquals(1, $twig->getArgument(0));
        $this->assertTrue($twig->hasTag('twig.extension'));
    }

    /**
     * @test
     */
    public function test_minimal_config()
    {
        $container = $this->getContainer(<<<YAML
services:
  cache_driver:
    class: TreeHouse\Cache\Driver\ArrayDriver

  cache_serializer:
    class: TreeHouse\Cache\Serializer\PhpSerializer

  cache_service:
    class: TreeHouse\Cache\Cache
    arguments:
      - '@cache_driver'
      - '@cache_serializer'

tree_house_recommendation:
  cache: cache_service
  engine:
    site_id: 1
YAML
        );

        // assert the HTTP client
        $this->assertTrue(
            $container->hasDefinition('tree_house.recommendation.engine.http_client'),
            'The extension should have created a HTTP client to perform requests with'
        );

        // assert the engine client
        $this->assertTrue(
            $container->hasDefinition('tree_house.recommendation.engine.client'),
            'The extension should have created a recommendation engine client'
        );

        $client = $container->getDefinition('tree_house.recommendation.engine.client');
        $this->assertEquals(
            'tree_house.recommendation.engine.http_client',
            (string) $client->getArgument(0),
            'The recommendation engine client should receive the HTTP client'
        );

        // assert the engine
        $this->assertTrue(
            $container->hasDefinition('tree_house.recommendation.engine'),
            'The extension should have an engine service'
        );

        $engine = $container->getDefinition('tree_house.recommendation.engine');

        $this->assertEquals('tree_house.recommendation.engine.client', (string) $engine->getArgument(0));
        $this->assertEquals('cache_service', (string) $engine->getArgument(1));
        $this->assertTrue($engine->hasTag('monolog.logger'));
    }

    /**
     * @test
     */
    public function test_client_mock_config()
    {
        $container = $this->getContainer(<<<YAML
services:
  cache_driver:
    class: TreeHouse\Cache\Driver\ArrayDriver

  cache_serializer:
    class: TreeHouse\Cache\Serializer\PhpSerializer

  cache_service:
    class: TreeHouse\Cache\Cache
    arguments:
      - '@cache_driver'
      - '@cache_serializer'

  tree_house.recommendation.engine.client:
    class: TreeHouse\RecommendationBundle\Recommendation\Engine\RandomNumberClientMock

tree_house_recommendation:
  cache: cache_service
  engine:
    site_id: 1
YAML
        );

        $client = $container->getDefinition('tree_house.recommendation.engine.client');
        $this->assertEquals(RandomNumberClientMock::class, $client->getClass());
    }

    /**
     * @param string $config
     * @param array  $parameters
     * @param bool   $debug
     *
     * @return ContainerBuilder
     */
    private function getContainer($config, $parameters = [], $debug = false)
    {
        // write the config to a tmp file
        if ((false === $file = tempnam(sys_get_temp_dir(), 'config')) || (false === file_put_contents($file, $config))) {
            throw new \RuntimeException('Could not write config to a temp file');
        }

        $parameters = array_merge(
            $parameters,
            ['kernel.debug' => $debug]
        );

        $container = new ContainerBuilder(new ParameterBag($parameters));
        $container->registerExtension(new TreeHouseRecommendationExtension());
        $container->getCompilerPassConfig()->setRemovingPasses([]);

        $locator = new FileLocator(dirname($file));
        $loader = new YamlFileLoader($container, $locator);
        $loader->load($file);

        $container->compile();

        unlink($file);

        return $container;
    }
}
