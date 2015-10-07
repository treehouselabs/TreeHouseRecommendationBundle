<?php

namespace TreeHouse\RecommendationBundle\Tests\Twig;

use TreeHouse\RecommendationBundle\Twig\RecommendationExtension;

class RecommendationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed()
    {
        $ext = new RecommendationExtension(1);

        $this->assertInstanceOf(\Twig_Extension::class, $ext);
    }

    /**
     * @test
     */
    public function it_has_a_name()
    {
        $ext = new RecommendationExtension(1);

        $this->assertInternalType('string', $ext->getName());
        $this->assertNotEmpty($ext->getName());
    }

    /**
     * @test
     */
    public function it_has_all_expected_functions()
    {
        $ext = new RecommendationExtension(1);

        $this->assertExtensionHasFunction($ext, 'recommendation_script_start');
        $this->assertExtensionHasFunction($ext, 'recommendation_script_url');
        $this->assertExtensionHasFunction($ext, 'recommendation_track');
    }

    /**
     * @test
     */
    public function it_can_return_the_script_url()
    {
        $ext = new RecommendationExtension(1);

        $this->assertInternalType('string', $ext->getScriptUrl());
        $this->assertNotFalse(filter_var($ext->getScriptUrl(), FILTER_VALIDATE_URL));
    }

    /**
     * @test
     */
    public function it_can_return_the_script_start()
    {
        $ext = new RecommendationExtension(1);

        $this->assertEquals('<script>var _reaq = [];</script>', $ext->getScriptStart());
    }

    /**
     * @test
     */
    public function it_can_return_the_script_end()
    {
        $ext = new RecommendationExtension(1);

        $url = $ext->getScriptUrl();

        $this->assertEquals(
            sprintf('<script src="%s" async defer></script>', $url),
            $ext->getScriptEnd()
        );

        $this->assertEquals(
            sprintf('<script src="%s" async></script>', $url),
            $ext->getScriptEnd(true, false)
        );

        $this->assertEquals(
            sprintf('<script src="%s"></script>', $url),
            $ext->getScriptEnd(false, false)
        );
    }

    /**
     * @test
     */
    public function it_can_track_recommendations()
    {
        $ext = new RecommendationExtension(1);

        $script = $ext->trackRecommendation(1234);
        $this->assertEquals('_reaq.push([1,1234]);', $script);

        $script = $ext->trackRecommendation(1234, ['foo']);
        $this->assertEquals('_reaq.push([1,1234,["foo"]]);', $script);

        $script = $ext->trackRecommendation(1234, ['foo', 'bar']);
        $this->assertEquals('_reaq.push([1,1234,["foo","bar"]]);', $script);
    }

    /**
     * @param \Twig_ExtensionInterface $extension
     * @param string                   $name
     */
    private function assertExtensionHasFunction(\Twig_ExtensionInterface $extension, $name)
    {
        $names = array_map(
            function (\Twig_SimpleFunction $function) {
                return $function->getName();
            },
            $extension->getFunctions()
        );

        $this->assertContains($name, $names);
    }
}
