<?php

namespace TreeHouse\RecommendationBundle\Tests\Recommendation\Engine;

use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\RequestException;
use Http\Client\HttpClient;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Http\Mock\Client as MockClient;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TreeHouse\RecommendationBundle\Recommendation\Engine\ClientInterface;
use TreeHouse\RecommendationBundle\Recommendation\Engine\OtrslsoClient;

class OtrslsoClientTest extends \PHPUnit_Framework_TestCase
{
    const ENDPOINT = 'http://localhost';
    const SITE_ID = 1;

    /**
     * @var MockClient
     */
    private $mockClient;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->mockClient = new MockClient(new GuzzleMessageFactory());
    }

    /**
     * @test
     */
    public function it_can_be_constructed()
    {
        $client = new OtrslsoClient($this->mockClient, self::ENDPOINT, self::SITE_ID);

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

        $this->mockClient->addResponse($this->mockResponse(200, $result));
        $client = new OtrslsoClient($this->mockClient, self::ENDPOINT, self::SITE_ID);

        $recommended = $client->$method($objectId = 1234);

        $this->assertCount(1, $this->mockClient->getRequests(), 'A request should have been made');
        $this->assertEquals($ids, $recommended);

        /** @var RequestInterface $request */
        $request = $this->mockClient->getRequests()[0];
        $path = $request->getUri()->getPath();
        parse_str($request->getUri()->getQuery(), $query);

        $this->assertEquals($method, $path);
        $this->assertEquals(
            [
                'c' => self::SITE_ID,
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

        $this->mockClient->addResponse($this->mockResponse(200, $result));
        $client = new OtrslsoClient($this->mockClient, self::ENDPOINT, self::SITE_ID);

        $recommended = $client->$method(1234, 3);

        $this->assertEquals([123, 456, 789], $recommended);
    }

    /**
     * @test
     * @expectedException \TreeHouse\RecommendationBundle\Exception\EngineException
     */
    public function it_throws_exception_on_http_exception()
    {
        $exception = $this->prophesize(RequestException::class)->reveal();

        /** @var HttpClient|ObjectProphecy $httpClient */
        $httpClient = $this->prophesize(HttpClient::class);
        $httpClient->sendRequest(new TypeToken(RequestInterface::class))->willThrow($exception);

        $client = new OtrslsoClient($httpClient->reveal(), self::ENDPOINT, self::SITE_ID);
        $client->recommend(1234);
    }

    /**
     * @test
     * @expectedException \TreeHouse\RecommendationBundle\Exception\EngineException
     */
    public function it_throws_exception_on_invalid_response()
    {
        $result = '{some_invalid_json}';

        $this->mockClient->addResponse($this->mockResponse(200, $result));
        $client = new OtrslsoClient($this->mockClient, self::ENDPOINT, self::SITE_ID);

        $client->recommend(1234);
    }

    /**
     * @param int   $code
     * @param mixed $data
     * @param array $headers
     *
     * @return ResponseInterface
     */
    private function mockResponse($code = 200, $data = null, $headers = [])
    {
        return new Response($code, $headers, $data);
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
