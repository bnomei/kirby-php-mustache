<?php

require_once __DIR__ . '/vendor/autoload.php';

/****************************************
  KirbyPHPMustache
 ***************************************/

class KirbyPHPMustache
{
    private static $settings = [];    // plugin
    private static $config = [];      // mustache engine
    private static $helpers = [];     // mustache engine global vars
    private static $singleton = null;
    private static $singletonString = null;

    private static function is_closure($t)
    {
        return is_object($t) && ($t instanceof Closure);
    }

    private static function instance($stringLoader = false)
    {
        if ($stringLoader && self::$singletonString) {
            return self::$singletonString;
        } elseif (!$stringLoader && self::$singleton) {
            return self::$singleton;
        }

        ///////////////////////////////////////
        // PLUGIN SETTINGS
        //

        self::$settings = array();
        self::$settings['extension']      = c::get('plugin.mustache.extension', 'mustache');

        self::$settings['templates_dir']  = c::get('plugin.mustache.templates_dir', kirby()->roots()->templates());
        $td = self::$settings['templates_dir'];
        if (self::is_closure($td)) {
            self::$settings['templates_dir'] = $td();
        }
        dir::make(self::$settings['templates_dir']);
        self::$settings['templates_partials_dir']  = c::get('plugin.mustache.templates_partials_dir', self::$settings['templates_dir']);
        $tpd = self::$settings['templates_partials_dir'];
        if (self::is_closure($tpd)) {
            self::$settings['templates_partials_dir'] = $tpd();
        }
        dir::make(self::$settings['templates_partials_dir']);

        self::$settings['cache']          = c::get('plugin.mustache.cache', !c::get('debug', false));
        self::$settings['cache_dir']      = c::get('plugin.mustache.cache_dir', kirby()->roots()->cache() . DS . 'mustache');
        $cd = self::$settings['cache_dir'];
        if (self::is_closure($cd)) {
            self::$settings['cache_dir'] = $cd();
        }
        if (self::$settings['cache']) {
            dir::make($cache_dir);
        }

        ///////////////////////////////////////
        // MUSTACHE ENGINE CONFIG
        //

        self::$helpers = [
            'page'	=> null,
    ];

        if (c::get('plugin.mustache.helpers.globals', false)) {
            self::$helpers = array_merge(self::$helpers, [
            'pages' => kirby()->site()->children(),
            'site' 	=> kirby()->site(),
            'kirby' 	=> kirby(),
            'snippet'	=> function ($name, $data, $return = true) {
                return snippet($name, $data, $return);
            },
        'print_r' => function ($data) {
            return print_r($data, true);
        }
        ]);
        }

        self::$config = array();
        self::$config['helpers'] =  c::get('plugin.mustache.helpers', self::$helpers);
        self::$config['escape'] = c::get(
            'plugin.mustache.escape',
            function ($value) {
                return $value; // do not escape! see https://github.com/bobthecow/mustache.php/wiki
            }
        );

        // String Loader
        self::$singletonString = new Mustache_Engine(self::$config);

        // File Loader
        self::$config['loader'] = new Mustache_Loader_FilesystemLoader(self::$settings['templates_dir']);
        self::$config['partials_loader'] = new Mustache_Loader_FilesystemLoader(self::$settings['templates_partials_dir']);
        self::$singleton = new Mustache_Engine(self::$config);

        return $stringLoader ? self::$singletonString : self::$singleton;
    }

    public static function ecco($template, $data = null, $dump = false)
    {
        echo self::render($template, $data, $dump);
    }

    public static function renderPage($page, $template = null, $data = null, $dump = false)
    {
        if (!$page || !is_a($page, 'Page')) {
            return false;
        }
        if (!$template) {
            $template = (string) $page->intendedTemplate();
        }
        if (is_string($data)) {
            if ($file = self::readFile($data)) {
                $template = !$template ? a::get($file, 'template', null) : $template;
                $data = a::get($file, 'data', []);
            }
        }
        if ($data == null) {
            $data = [];
        }

        if (c::get('plugin.mustache.helpers.page', false)) {
            $data = array_merge($page->toArray(), $data);
        }
        return self::render($template, $data, $dump);
    }

    public static function render($template, $data = null, $dump = false)
    {
        $out = false;
        $ext = '';
        $stringLoader = false;

        if (!is_string($template) && is_callable($template)) {
            $template = trim($template());
            $stringLoader = true;
        }

        if (is_string($data)) {
            if ($file = self::readFile($data)) {
                $template = !$template ? a::get($file, 'template', null) : $template;
                $data = a::get($file, 'data', []);
            }
        }
        if ($data == null) {
            $data = [];
        }

        if ($mustache = self::instance($stringLoader)) {
            if (!$stringLoader) {
                $ext = '.' . self::$settings['extension'];
                if (!str::endsWith($template, $ext)) {
                    $template .= $ext;
                }
            }
            $out = $mustache->render(
                $template,
                $data
            );
        }

        if ($dump) {
            $t = self::$settings['templates_dir'] . DS . $template;
            $p = self::$settings['templates_partials_dir'] . DS . $template;
            $f = null;
            $s = self::$settings['templates_dir'] . DS . str_replace($ext, '', $template).'.css';
            if (!f::exists($s)) {
                $s = self::$settings['templates_dir'] . DS . '_'.str_replace($ext, '', $template).'.scss';
            }

            if (f::exists($t)) {
                $f = f::read($t);
            }
            if (f::exists($p)) {
                $f = f::read($p);
            }

            $tabs = [
        'output' => $out,
        'template' => '<pre><code data-language="mustache">'.htmlspecialchars($f).'</code></pre>',
        'data' => str_replace(
            ['<pre>', '</pre>'],
            ['<pre><code data-language="kirby-array">', '</code></pre>'],
            a::show($data, false)
        ),
      ];

            if (f::exists($s)) {
                $s = f::read($s);
                $tabs['style'] = '<pre><code data-language="'.c::get('plugin.mustache.style.language', 'css').'">'.$s.'</code></pre>';
            }

            $s = '';
            $d = '';
            $class = c::get('plugin.mustache.css.class', 'kirby-php-mustache');
            foreach ($tabs as $key => $tab) {
                $s .= brick('div', c::get('plugin.mustache.tab.'.$key, $key))
          ->addClass($class . '__tab')
          ->attr('data-tab', $class . '__view--' . $key);
                $d .= brick('div', $tab)
          ->addClass($class . '__view ' . $class . '__view--' . $key);
            }
            $s = brick('div', $s)->addClass($class . '__tabs');
            $d = brick('div', $d)->addClass($class . '__views');
            $out = brick('div', $s.$d)
        ->addClass($class . ' hidden');
        }

        return $out;
    }

    public static function readFile($path)
    {
        if (!self::instance()) {
            return null;
        } // force load settings

        if ($path == basename($path)) {
            $path = self::$settings['templates_dir'] . DS . $path;
        }
        if (f::exists($path)) {
            // as array not object (for $data)
      return json_decode(f::read($path), true); // null if failed
        }
        return null;
    }

    public static function listTemplates()
    {
        if (!self::instance()) {
            return null;
        } // force load settings

        $templates = [];
        $folders = [
      self::$settings['templates_dir'],
      self::$settings['templates_partials_dir']
    ];
        foreach ($folders as $folder) {
            foreach (dir::read($folder) as $file) {
                if (f::extension($file) != self::$settings['extension']) {
                    continue;
                }
                $templates[f::name($file)] = str::ucwords(str_replace('-', ' ', f::name($file)));
            }
        }

        return $templates;
    }
}

/****************************************
  Global Helper
 ***************************************/

if (!function_exists('mustache')) {
    function mustache($template, $data = null, $dump = false, $return = false)
    {
        $out = KirbyPHPMustache::render($template, $data, $dump);
        if ($return == false) {
            echo $out;
        } else {
            return $out;
        }
    }
}

/****************************************
  SNIPPETS
 ***************************************/

$snippets = new Folder(__DIR__ . '/snippets');
foreach ($snippets->files() as $file) {
    if ($file->extension() == 'php') {
        $kirby->set('snippet', $file->name(), $file->root());
    }
}

/****************************************
  TAGS
 ***************************************/

$tags = new Folder(__DIR__ . '/tags');
foreach ($tags->files() as $file) {
    if ($file->extension() == 'php') {
        // $kirby->set('tag', $file->name(), $file->root());
        // TODO: ? maybe just include file?
        include_once $file->root();
    }
}

/****************************************
  PAGE METHODS
 ***************************************/

$kirby->set(
    'page::method',
    'mustache',
    function ($page, $template = null, $data = null, $dump = false) {
        return KirbyPHPMustache::renderPage($page, $template, $data, $dump);
    }
);

/****************************************
 ROUTE
 ***************************************/

$kirby->set('route', array(
  'pattern' => 'kirby-php-mustache/templates',
  'action'  => function () {
      return die(response::json(KirbyPHPMustache::listTemplates()));
  }
));
