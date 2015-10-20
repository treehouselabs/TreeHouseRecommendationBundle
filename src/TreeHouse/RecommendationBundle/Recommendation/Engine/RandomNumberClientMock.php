<?php

namespace TreeHouse\RecommendationBundle\Recommendation\Engine;

class RandomNumberClientMock implements ClientInterface
{
    /**
     * @var int[]
     */
    private $values;

    /**
     * @param int $from
     * @param int $to
     */
    public function __construct($from = 1, $to = 100000)
    {
        $this->values = range($from, $to);
    }

    /**
     * @inheritdoc
     */
    public function recommend($objectId, $limit = 10)
    {
        return $this->pick($limit);
    }

    /**
     * @inheritdoc
     */
    public function popularity($category, $limit = 10)
    {
        return $this->pick($limit);
    }

    /**
     * @param int $limit
     *
     * @return int[]
     */
    protected function pick($limit)
    {
        $keys = (array) array_rand($this->values, $limit);

        return array_map(
            function ($key) {
                return $this->values[$key];
            },
            $keys
        );
    }
}
