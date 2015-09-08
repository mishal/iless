<?php

use ILess\Exception\Exception;
use ILess\Parser;

require_once '_bootstrap.php';

try {

    $cacheDir = dirname(__FILE__).'/cache';
    // create the parser
    $parser = new Parser(array(
            'compress' => false,
            'source_map' => true, // enable source map
            'source_map_options' => array(
                'source_contents' => true
            )
        )
    );

    // parse file
    $parser->parseFile(__DIR__.'/less/test.less');

    // parse additional string
    $parser->parseString('
  #header {
    background: black;
  }');

    $cssContent = $parser->getCSS();
    file_put_contents($cacheDir.'/screen.css', $cssContent);
    $css = 'cache/screen.css';
} catch (Exception $e) {
    @header('HTTP/1.0 500 Internal Server Error');
    echo $e;
    exit;
}

$example = 'source map output';

include '_page.php';
