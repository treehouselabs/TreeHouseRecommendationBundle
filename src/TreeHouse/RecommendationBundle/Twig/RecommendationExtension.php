<?php

namespace TreeHouse\RecommendationBundle\Twig;

class RecommendationExtension extends \Twig_Extension
{
    /**
     * @var int
     */
    private $siteId;

    /**
     * @param int $siteId
     */
    public function __construct($siteId)
    {
        $this->siteId = $siteId;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'tree_house_recommendation';
    }

    /**
     * @inheritdoc
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('recommendation_script_url', [$this, 'getScriptUrl']),
            new \Twig_SimpleFunction('recommendation_script_start', [$this, 'getScriptStart'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('recommendation_script_end', [$this, 'getScriptEnd'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('recommendation_track', [$this, 'trackRecommendation'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @return string
     */
    public function getScriptUrl()
    {
        return 'https://api.otrslso.com/re.js';
    }

    /**
     * @return string
     */
    public function getScriptStart()
    {
        return sprintf('<script>var _reaq = [];</script>');
    }

    /**
     * @param bool $async
     * @param bool $defer
     *
     * @return string
     */
    public function getScriptEnd($async = true, $defer = true)
    {
        $attributes = array_keys(array_filter(compact('async', 'defer')));

        return sprintf(
            '<script src="%s"%s></script>',
            $this->getScriptUrl(),
            rtrim(' ' . implode(' ', $attributes))
        );
    }

    /**
     * @param int   $id
     * @param array $categories
     *
     * @return string
     */
    public function trackRecommendation($id, array $categories = [])
    {
        $data = [
            $this->siteId,
            $id,
        ];

        if (!empty($categories)) {
            $data[] = $categories;
        }

        return sprintf('_reaq.push(%s);', json_encode($data));
    }
}
