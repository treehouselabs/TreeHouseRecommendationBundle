<?php

namespace TreeHouse\RecommendationBundle\Tests\Recommendation\Engine;

use TreeHouse\RecommendationBundle\Recommendation\Engine\ClientInterface;
use TreeHouse\RecommendationBundle\Recommendation\Engine\RandomNumberClientMock;

class RandomNumberClientMockTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed()
    {
        $mock = new RandomNumberClientMock();

        $this->assertInstanceOf(ClientInterface::class, $mock);
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
     * @test
     * @dataProvider getMethods
     *
     * @param string $method
     */
    public function it_can_recommend_objects($method)
    {
        $mock = new RandomNumberClientMock();
        $recommendations = $mock->$method($objectId = 1234, $limit = 5);

        $this->assertCount($limit, $recommendations);
        $this->assertInternalType('int', reset($recommendations));
    }

    /**
     * @test
     */
    public function it_can_use_a_specific_range()
    {
        $mock = new RandomNumberClientMock(3, 3);
        $recommendations = $mock->recommend(1234, 1);

        $this->assertEquals(3, reset($recommendations));
    }
}
