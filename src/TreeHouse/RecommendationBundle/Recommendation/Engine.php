<?php

namespace TreeHouse\RecommendationBundle\Recommendation;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TreeHouse\Cache\CacheInterface;
use TreeHouse\RecommendationBundle\Exception\EngineException;
use TreeHouse\RecommendationBundle\Recommendation\Engine\ClientInterface;

class Engine implements EngineInterface
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var int
     */
    protected $ttl = 300;

    /**
     * @var bool
     */
    protected $throwExceptions = false;

    /**
     * @param ClientInterface $client
     * @param CacheInterface  $cache
     * @param LoggerInterface $logger
     */
    public function __construct(ClientInterface $client, CacheInterface $cache, LoggerInterface $logger = null)
    {
        $this->client = $client;
        $this->cache = $cache;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Changes the time-to-live for recommendation results.
     * Set it to 0 to cache indefinitely, thus never expire the results.
     * Although this is not recommended (no pun intended), since results may
     * change frequently, depending on page visits.
     *
     * @param int $ttl
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
    }

    /**
     * @param bool $throwExceptions
     */
    public function setThrowExceptions($throwExceptions)
    {
        $this->throwExceptions = (boolean) $throwExceptions;
    }

    /**
     * @inheritdoc
     */
    public function recommend($objectId, $limit = 10)
    {
        try {
            return $this->fetch(
                $this->getCacheKey('recommend', $objectId, $limit),
                function () use ($objectId, $limit) {
                    return $this->client->recommend($objectId, $limit);
                }
            );
        } catch (EngineException $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    'method' => __METHOD__,
                    'object_id' => $objectId,
                ]
            );

            if ($this->throwExceptions) {
                throw $e;
            }

            return [];
        }
    }

    /**
     * @inheritdoc
     */
    public function popularity($category, $limit = 10)
    {
        try {
            return $this->fetch(
                $this->getCacheKey('popularity', $category, $limit),
                function () use ($category, $limit) {
                    return $this->client->popularity($category, $limit);
                }
            );
        } catch (EngineException $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    'method' => __METHOD__,
                    'category' => $category,
                ]
            );

            if ($this->throwExceptions) {
                throw $e;
            }

            return [];
        }
    }

    /**
     * @param string   $key
     * @param callable $getter
     *
     * @throws EngineException When anything other than an array was returned
     *                         from either the cache or the recommendation engine
     *
     * @return array
     */
    protected function fetch($key, callable $getter)
    {
        $result = $this->cache->get($key);

        if (!is_array($result)) {
            $result = call_user_func($getter);

            // only cache if result is an array
            if (is_array($result)) {
                $this->cache->set($key, $result, $this->ttl);
            }
        }

        if (!is_array($result)) {
            throw new EngineException(sprintf('Expected array, but got: %s', json_encode($result)));
        }

        return $result;
    }

    /**
     * @param string $type
     * @param string $object
     * @param int    $limit
     *
     * @return string
     */
    protected function getCacheKey($type, $object, $limit)
    {
        return sprintf('%s_%s_%d', $type, $object, $limit);
    }
}
