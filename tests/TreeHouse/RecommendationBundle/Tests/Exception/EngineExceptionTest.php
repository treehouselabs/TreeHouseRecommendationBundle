<?php

namespace TreeHouse\RecommendationBundle\Tests\Exception;

use TreeHouse\RecommendationBundle\Exception\EngineException;

class EngineExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed()
    {
        $message = 'Engine error';
        $exception = new EngineException($message);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
    }
}
