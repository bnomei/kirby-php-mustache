<?php kirbytext::$tags['mustache'] = array(
  'attr' => array(
    'dump', 'data'
  ),
  'html' => function($tag) {

    $template = $tag->attr('mustache');
    $data = $tag->attr('data', $template.'.json');
    $dump = $tag->attr('dump', false);
    return $tag->page()->mustache($template, $data, boolval($dump));
  }
);
