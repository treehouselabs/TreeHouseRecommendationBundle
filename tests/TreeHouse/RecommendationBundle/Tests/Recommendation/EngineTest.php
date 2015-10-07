<?php

namespace TreeHouse\RecommendationBundle\Tests\Recommendation;

use Psr\Log\LoggerInterface;
use TreeHouse\Cache\Cache;
use TreeHouse\Cache\CacheInterface;
use TreeHouse\Cache\Driver\ArrayDriver;
use TreeHouse\Cache\Serializer\PhpSerializer;
use TreeHouse\RecommendationBundle\Exception\EngineException;
use TreeHouse\RecommendationBundle\Recommendation\Engine;
use TreeHouse\RecommendationBundle\Recommendation\Engine\ClientInterface;
use TreeHouse\RecommendationBundle\Recommendation\EngineInterface;

class EngineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed()
    {
        $client = $this->getClientMock();
        $cache = $this->createCache();
        $engine = new Engine($client, $cache);

        $this->assertInstanceOf(EngineInterface::class, $engine);
    }

    /**
     * @test
     * @dataProvider getMethods
     *
     * @param string $method
     */
    public function it_can_recommend_objects($method)
    {
        $client = $this->getClientMock();
        $cache = $this->createCache();
        $engine = new Engine($client, $cache);

        $objectId = 1234;
        $limit = 5;
        $ids = [123, 456, 789, 345, 678];

        $client
            ->expects($this->once())
            ->method($method)
            ->with($objectId, $limit)
            ->willReturn($ids)
        ;

        $result = $engine->$method($objectId, $limit);
        $this->assertEquals($ids, $result);

        // second call should not trigger client hit
        $result = $engine->$method($objectId, $limit);
        $this->assertEquals($ids, $result);
    }

    /**
     * @test
     * @dataProvider getMethods
     *
     * @param string $method
     */
    public function it_can_catch_exception_recommending($method)
    {
        $client = $this->getClientMock();
        $cache = $this->createCache();
        $logger = $this->getLoggerMock();

        $engine = new Engine($client, $cache, $logger);

        $message = 'foobar';

        $logger
            ->expects($this->once())
            ->method('error')
            ->with($message, $this->isType('array'))
        ;

        $client
            ->expects($this->once())
            ->method($method)
            ->willThrowException(new EngineException($message))
        ;

        $result = $engine->$method(1234);

        $this->assertEquals([], $result);
    }

    /**
     * @test
     * @dataProvider getMethods
     * @expectedException \TreeHouse\RecommendationBundle\Exception\EngineException
     *
     * @param string $method
     */
    public function it_can_throw_exception_when_recommending($method)
    {
        $client = $this->getClientMock();
        $cache = $this->createCache();
        $logger = $this->getLoggerMock();

        $engine = new Engine($client, $cache, $logger);
        $engine->setThrowExceptions(true);

        $message = 'foobar';

        $logger
            ->expects($this->once())
            ->method('error')
            ->with($message, $this->isType('array'))
        ;

        $client
            ->expects($this->once())
            ->method($method)
            ->willThrowException(new EngineException($message))
        ;

        $engine->$method(1234);
    }

    /**
     * @test
     * @dataProvider getMethods
     *
     * @param string $method
     */
    public function it_can_change_ttl_for_recommendations($method)
    {
        $client = $this->getClientMock();
        $cache = $this->getCacheMock();
        $engine = new Engine($client, $cache);
        $engine->setTtl($ttl = 3600);

        $objectId = 1234;
        $limit = 5;
        $ids = [123, 456, 789, 345, 678];

        $client
            ->expects($this->once())
            ->method($method)
            ->with($objectId, $limit)
            ->willReturn($ids)
        ;

        $cache
            ->expects($this->once())
            ->method('get')
            ->willReturn(false)
        ;

        $cache
            ->expects($this->once())
            ->method('set')
            ->with($this->isType('string'), $ids, $ttl)
        ;

        $engine->$method($objectId, $limit);
    }

    /**
     * @test
     * @dataProvider getMethods
     * @expectedException \TreeHouse\RecommendationBundle\Exception\EngineException
     *
     * @param string $method
     */
    public function it_can_throw_exception_when_result_is_unexpected($method)
    {
        $client = $this->getClientMock();
        $cache = $this->createCache();

        $engine = new Engine($client, $cache);
        $engine->setThrowExceptions(true);

        $client
            ->expects($this->once())
            ->method($method)
            ->willReturn(null)
        ;

        $engine->$method(1234);
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return [
            ['recommend'],
            ['popularity'],
        ];
    }

    /**
     * @return Cache
     */
    private function createCache()
    {
        return new Cache(new ArrayDriver(), new PhpSerializer());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ClientInterface
     */
    private function getClientMock()
    {
        return $this->getMockForAbstractClass(ClientInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function getLoggerMock()
    {
        return $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheInterface
     */
    private function getCacheMock()
    {
        return $this->getMockBuilder(CacheInterface::class)->setMethods(['get', 'set'])->getMockForAbstractClass();
    }
}
