<?php

namespace TreeHouse\RecommendationBundle\Recommendation\Engine;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Http\Client\Exception\RequestException;
use Http\Client\HttpClient;
use Psr\Http\Message\ResponseInterface;
use TreeHouse\RecommendationBundle\Exception\EngineException;

/**
 * Client to talk to the Otrslso recommendation engine.
 */
class OtrslsoClient implements ClientInterface
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var int
     */
    private $siteId;

    /**
     * @param HttpClient $guzzle
     * @param string     $endpoint
     * @param int        $siteId
     */
    public function __construct(HttpClient $guzzle, $endpoint, $siteId)
    {
        $this->httpClient = $guzzle;
        $this->endpoint = $endpoint;
        $this->siteId = $siteId;
    }

    /**
     * Request recommendations.
     *
     * @param int $objectId
     * @param int $limit
     *
     * @return int[] recommended objectsIds
     */
    public function recommend($objectId, $limit = 10)
    {
        $response = $this->request('recommend', $this->getQuery(['i' => $objectId]));

        return $this->parseResponse($response, $limit);
    }

    /**
     * Request popularity.
     *
     * @param string $category
     * @param int    $limit
     *
     * @return int[] recommended objectsIds
     */
    public function popularity($category, $limit = 10)
    {
        $response = $this->request('popularity', $this->getQuery(['cat' => $category]));

        return $this->parseResponse($response, $limit);
    }

    /**
     * @param string $path
     * @param array  $query
     *
     * @throws EngineException
     *
     * @return ResponseInterface
     */
    protected function request($path, array $query = [])
    {
        $uri = (new Uri($this->endpoint))->withPath($path)->withQuery(http_build_query($query));
        $request = new Request('GET', $uri);

        try {
            return $this->httpClient->sendRequest($request);
        } catch (RequestException $e) {
            // something went wrong with the request, probably a timeout.
            throw new EngineException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param array $query
     *
     * @return array
     */
    protected function getQuery(array $query)
    {
        return array_merge(
            $query,
            ['c' => $this->siteId]
        );
    }

    /**
     * Parses a response.
     *
     * @param ResponseInterface $response Response from the engine client
     * @param int               $limit    The number of objects to return, set
     *                                    to null to return all
     *
     * @return int[] list of objectIds
     */
    protected function parseResponse(ResponseInterface $response, $limit = 10)
    {
        try {
            $body = $response->getBody()->getContents();
            $recommendations = $this->decode($body);

            if (null !== $limit && (sizeof($recommendations) > $limit)) {
                $recommendations = array_slice($recommendations, 0, $limit);
            }

            return array_map('intval', array_column($recommendations, 0));
        } catch (\RuntimeException $e) {
            throw new EngineException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $str
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    private function decode($str)
    {
        $decoded = json_decode($str, true);

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $decoded;

            case JSON_ERROR_DEPTH:
                throw new \RuntimeException('Could not decode JSON, maximum stack depth exceeded.');

            case JSON_ERROR_STATE_MISMATCH:
                throw new \RuntimeException('Could not decode JSON, underflow or the nodes mismatch.');

            case JSON_ERROR_CTRL_CHAR:
                throw new \RuntimeException('Could not decode JSON, unexpected control character found.');

            case JSON_ERROR_SYNTAX:
                throw new \RuntimeException('Could not decode JSON, syntax error - malformed JSON.');

            case JSON_ERROR_UTF8:
                throw new \RuntimeException('Could not decode JSON, malformed UTF-8 characters (incorrectly encoded?)');

            default:
                throw new \RuntimeException('Could not decode JSON.');
        }
    }
}
