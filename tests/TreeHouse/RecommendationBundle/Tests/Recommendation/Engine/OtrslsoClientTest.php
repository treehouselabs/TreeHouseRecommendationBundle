<?php

namespace TreeHouse\RecommendationBundle\Tests\Recommendation\Engine;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use TreeHouse\RecommendationBundle\Recommendation\Engine\ClientInterface;
use TreeHouse\RecommendationBundle\Recommendation\Engine\OtrslsoClient;

class OtrslsoClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed()
    {
        $client = new OtrslsoClient(new Client(), 1);

        $this->assertInstanceOf(ClientInterface::class, $client);
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return [
            ['recommend', 'i'],
            ['popularity', 'cat'],
        ];
    }

    /**
     * @test
     * @dataProvider getMethods
     *
     * @param string $method
     * @param string $queryKey
     */
    public function it_can_recommend_objects($method, $queryKey)
    {
        $ids = [123, 456, 789, 345, 678];
        $result = $this->createApiResult($ids);

        $container = [];
        $stack = HandlerStack::create($this->mockResponse(200, $result));
        $stack->push(Middleware::history($container));

        $siteId = 1;
        $guzzle = new Client(['handler' => $stack]);
        $client = new OtrslsoClient($guzzle, $siteId);

        $recommended = $client->$method($objectId = 1234);

        $this->assertCount(1, $container, 'A request should have been made');
        $this->assertEquals($ids, $recommended);

        /** @var RequestInterface $request */
        $request = $container[0]['request'];
        $path = $request->getUri()->getPath();
        parse_str($request->getUri()->getQuery(), $query);

        $this->assertEquals($method, $path);
        $this->assertEquals(
            [
                'c' => $siteId,
                $queryKey => $objectId,
            ],
            $query
        );
    }

    /**
     * @test
     * @dataProvider getMethods
     *
     * @param string $method
     */
    public function it_can_recommend_objects_with_limit($method)
    {
        $ids = [123, 456, 789, 345, 678];
        $result = $this->createApiResult($ids);

        $stack = HandlerStack::create($this->mockResponse(200, $result));
        $guzzle = new Client(['handler' => $stack]);
        $client = new OtrslsoClient($guzzle, 1);

        $recommended = $client->$method(1234, 3);

        $this->assertEquals([123, 456, 789], $recommended);
    }

    /**
     * @test
     * @expectedException \TreeHouse\RecommendationBundle\Exception\EngineException
     */
    public function it_throws_exception_on_guzzle_exception()
    {
        $guzzle = $this->getMockBuilder(Client::class)->setMethods(['request'])->getMock();
        $guzzle
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new TransferException())
        ;

        $client = new OtrslsoClient($guzzle, 1);
        $client->recommend(1234);
    }

    /**
     * @test
     * @expectedException \TreeHouse\RecommendationBundle\Exception\EngineException
     */
    public function it_throws_exception_on_invalid_response()
    {
        $result = '{some_invalid_json}';

        $stack = HandlerStack::create($this->mockResponse(200, $result));
        $guzzle = new Client(['handler' => $stack]);
        $client = new OtrslsoClient($guzzle, 1);

        $client->recommend(1234);
    }

    /**
     * @param int   $code
     * @param mixed $data
     * @param array $headers
     *
     * @return MockHandler
     */
    private function mockResponse($code = 200, $data = null, $headers = [])
    {
        $mock = new MockHandler([
            new Response($code, $headers, $data),
        ]);

        return $mock;
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    private function createApiResult(array $ids)
    {
        return json_encode(
            array_map(
                function ($id) {
                    return [(string) $id, rand(1, 10)];
                },
                $ids
            )
        );
    }
}
