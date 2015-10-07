<?php

namespace TreeHouse\RecommendationBundle\Recommendation;

interface EngineInterface
{
    /**
     * Request recommendations.
     *
     * @param int $objectId
     * @param int $limit
     *
     * @return int[] recommended objectsIds
     */
    public function recommend($objectId, $limit = 10);

    /**
     * Request popularity.
     *
     * @param string $category
     * @param int    $limit
     *
     * @return int[] recommended objectsIds
     */
    public function popularity($category, $limit = 10);
}
