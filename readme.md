# Kirby PHP Mustache

![GitHub release](https://img.shields.io/github/release/bnomei/kirby-php-mustache.svg?maxAge=1800) ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-2.3%2B-red.svg)

Kirby CMS Kirby CMS Plugin to use logicless templates with PHP Mustache.

This plugin is free but if you use it in a commercial project please consider to [make a donation 🍻](https://www.paypal.me/bnomei/2).

## Requirements

- [**Kirby**](https://getkirby.com/) 2.3+

## Included Dependencies (via Composer)

- [Mustache.php](https://github.com/bobthecow/mustache.php) v2.12.0

## Installation

### [Kirby CLI](https://github.com/getkirby/cli)

```
kirby plugin:install bnomei/kirby-php-mustache
```

### Git Submodule

```
$ git submodule add https://github.com/bnomei/kirby-php-mustache.git site/plugins/kirby-php-mustache
```

### Copy and Paste

1. [Download](https://github.com/bnomei/kirby-php-mustache/archive/master.zip) the contents of this repository as ZIP-file.
2. Rename the extracted folder to `kirby-php-mustache` and copy it into the `site/plugins/` directory in your Kirby project.

## Good and Bad

Mustache is logicless. Given you prepare your data well using [Kirby Page Controller](https://getkirby.com/docs/developer-guide/advanced/controllers) and/or [Page Models](https://getkirby.com/docs/developer-guide/advanced/models) its quiet simple to use. It has caching but it is not as fast as raw PHP or [Blade](https://github.com/pedroborges/kirby-blade-template). Using [Mustache Partials](https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags#partials) it can be used for [Atomic Design](http://bradfrost.com/blog/post/atomic-web-design/) and might be an easier alternative to more advanced solutions like Kirby [Patters Plugin](https://github.com/getkirby-plugins/patterns-plugin) and [Modules Plugin](https://github.com/getkirby-plugins/modules-plugin). Mustache is similar to [Handlebars](http://handlebarsjs.com/).

## Basic Usage

Templates written in Mustache can be parsed with [PHP on your server](https://mustache.github.io/) or even with [Javascript by the client](https://github.com/janl/mustache.js). HTML Classes in examples are named using [BEM](http://getbem.com/introduction/) but that is just my personal favourite. Please note that this plugin can make the `$page` and `$site` objects available via settings.

**site/config/config.php**
```php
c::set('plugin.mustache.helpers.page', true);
// $page object in mustache
// will merge $page->content()->toArray() with additional data
```

**site/templates/example.mustache**
```html
<div class="c-example">
  <header class="c-example__title"><h1>{{ page.title }}</h1></header>
  <div class="c-example__text">{{ page.text }}</div>
</div>
```

**content/example/example.txt**
```yml
Title: Templating
----
Text: is so much fun
```

**site/templates/example.php**
```php
  c::set('plugin.mustache.helpers.page', true);
  echo $page->mustache(); // or...
  // mustache('example', $page->content()->toArray());
```

**html output**
```html
<div class="c-example">
  <header class="c-example__title"><h1>Templating</h1></header>
  <div class="c-example__text">is so much fun</div>
</div>
```

## Kirby Tag Mustache

You can also render Mustache using the Kirby Tag.

```
(mustache: example)
or with data from json and dumping
(mustache: example data: example.json dump: false)
```

## Advanced Usage

Using the `$page->mustache()` once might not be enough for complex layouts. Consider using the global helper function `mustache(...)` instead passing the name of template and data-array. As well as using other [Mustache Tags](https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags) in your Mustache template.

**dishes.mustache**
```html
{{# missing }}
<header>Never shown</header>
{{/ missing }}
<ul class="c-dishes">
  {{# dishes }}
    {{> dish }}
  {{/ dishes }}
</ul>
```

**dish.mustache**
```html
<li class="c-dishes__item">{{ name }}</li>
```

**any php template**
```php
mustache('dishes', [
  'dishes' => [
    ['name' => 'Agemono'],
    ['name' => 'Yakimono'],
    ['name' => 'Nabemono'],
  ],
  // 'missing' => false,
]);
```

**dishes.json**
```json
{
  "template": "dishes",
  "data": {
    "missing": false,
    "dishes": [
      {"name": "Agemono"},
      {"name": "Yakimono"},
      {"name": "Nabemono"}
    ]
  }
}
```

## Dumping and Returning

The Kirby Page-Method `$page->mustache(...)` as well as the Kirby Tag and snippet all allow dumping and returning.

For dumping you need to include the css and js (and jQuery/Zepto) this plugin provides to make it look pretty.

- example.mustache : mustache template code
- example.json : (optional) json formated data-array
- example.css : (optional) css of component

**your header/footer snippet**
```
echo css('assets/plugins/kirby-php-mustache/main.css');
// after jQuery/Zepto
echo js('assets/plugins/kirby-php-mustache/main.js');
```

**in any template**
```php
$returning = true;
$dumping = true;
$route = kirby()->site()->url().'/kirby-php-mustache/templates';
foreach(json_decode(file_get_contents($route), true) as $template => $name) {
  $html = mustache($template, $template.'.json', $dumping, $returning);
  echo $html;
}
```

## Not using Files for Templates

By default this plugin assumes you store your templates in files. But you can also use a closure instead of a template filename with the `mustache(...)` helper function. This will trigger the Mustache Engine StringLoader.

```php
$dynTemplate = "{{# a }} {{ b }} Scream,{{/ a }} for Ice Cream!";
mustache(function() use ($dynTemplate) {
  return (string) $dynTemplate;
}, ['a' => [
  ['b'=>'I'], ['b'=>'You'], ['b'=>'We all']]
]);
// I Scream, You Scream, We all Scream, for Ice Cream!
```

## Setting

You can set these in your `site/config/config.php`.

### plugin.mustache.extension
- default: 'mustache'

### plugin.mustache.templates_dir
- default: `kirby()->roots()->templates()`
- if using Atomic Design consider using `kirby()->roots()->site().DS.'uipattern'` instead

### plugin.mustache.templates_partials_dir
- default: `c::get(plugin.mustache.templates_dir)`
- if using Atomic Design consider using `kirby()->roots()->site().DS.'uipattern'.DS.'partials'` instead

### plugin.mustache.cache
- default: `!c::get('debug', false)`

### plugin.mustache.cache_dir
- default: `kirby()->roots()->cache() . DS . 'mustache'`

### plugin.mustache.helpers.globals
- default: `false`

### plugin.mustache.helpers.page
- default: `false`

### plugin.mustache.css.class
- default: 'kirby-php-mustache'

### plugin.mustache.tab.*
- used to override the labels of tabs when dumping

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby-php-mustache/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.
