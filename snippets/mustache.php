<?php

// possibly extract(ed) vars
// $page, $template, $data
if(!isset($dump)) $dump = false;
echo KirbyPHPMustache::renderPage($page, $template, $data, $dump);
