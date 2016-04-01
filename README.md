# Recommendation bundle

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]

Symfony bundle that implements a recommendation engine in your project.

For now only the Otrslso recommendation engine is supported, which is in
private beta at the moment.

## Installation

```sh
composer require treehouselabs/recommendation-bundle
```


## Usage

In your Symfony configuration:

```yaml
tree_house_recommendation:
  cache: id_to_tree_house_cache_service
  engine:
    type: otrslso # the default, and only supported engine at this point
    timeout: 5 # optional
    site_id: 1

```

The only required parts here are the site id and cache service id. Everything
else works out of the box. The bundle registers a recommendation engine client
service:

```php
$engine = $container->get('tree_house.recommendation.engine');
$ids = $engine->recommend($objectId); // returns an array of related object id's
```

Getting popular results in a category:

```php
$engine = $container->get('tree_house.recommendation.engine');
$ids = $engine->popularity($category); // returns an array of recommended object id's
```

### Caching

The recommendation calls are cached for 5 minutes. You can change this to your
own preference:

```php
// cache for an hour
$engine->setTtl(3600);
```

### Exception handling

Most of the time you want the engine to deliver quick results, where it does
not matter a great deal if an error occurs (eg. when the service is down/slow).
To accomodate this, request exceptions are caught, logged and forgotten. When
this occurs, an empty array is returned (and not cached of course), so your
page can just show an empty recommendation list.

This behaviour is enabled by default, you can reverse it like this:

```php
$engine->setThrowExceptions(true);
```

### Twig extension

The bundle also registers a Twig extension, which you can use to include the
tracker script, and track objects:

```twig
{{ recommendation_script_start() }}

// track stuff here:
<script>
  {{ recommendation_track(1234) }}
  {{ recommendation_track(5678) }}
</script>

// don't forget this!
{{ recommendation_script_end() }}
```

renders this:

```html
<script>var _reaq = [];</script>
<script>
  _reaq.push([1, 1234]);
  _reaq.push([1, 5678]);
</script>
<script src=“https://url.to.tracker.script.js” async defer></script>
```

The external script uses the [async and defer attributes][async] by default, so
it does not really matter where you put this in the HTML.

**Don't forget** the `{{ recommendation_script_end() }}` call or the tracking
script won't be loaded, and nothing will be tracked.


[async]: https://www.igvita.com/2014/05/20/script-injected-async-scripts-considered-harmful/


### Using a mock during development/testing

If you use the tracker during development and/or testing, you risk tainting the
actual recommendations that are used in production. To prevent this, you can
use the `RandomNumberClientMock` that comes with this bundle. Just override the
engine's client service to do this:

```yaml
tree_house.recommendation.engine.client:
  class: TreeHouse\RecommendationBundle\Recommendation\Engine\RandomNumberClientMock
```

Of course you can override this to better suit your specific needs (like a mock
that returns random entities, for example).


## Testing

``` bash
composer test
```


## Security

If you discover any security related issues, please email peter@treehouse.nl instead of using the issue tracker.


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


## Credits

- [Peter Kruithof][link-author]
- [All Contributors][link-contributors]


[ico-version]: https://img.shields.io/packagist/v/treehouselabs/recommendation-bundle.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/treehouselabs/recommendation-bundle/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/treehouselabs/recommendation-bundle.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/treehouselabs/recommendation-bundle.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/treehouselabs/recommendation-bundle.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/treehouselabs/recommendation-bundle
[link-travis]: https://travis-ci.org/treehouselabs/recommendation-bundle
[link-scrutinizer]: https://scrutinizer-ci.com/g/treehouselabs/recommendation-bundle/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/treehouselabs/recommendation-bundle
[link-downloads]: https://packagist.org/packages/treehouselabs/recommendation-bundle
[link-author]: https://github.com/pkruithof
[link-contributors]: ../../contributors
