<?php

require_once '_bootstrap.php';

try {

  $cacheDir = dirname(__FILE__) . '/cache';
  // create the parser
  $parser = new ILess_Parser(array(
      'compress' => false,
      'source_map' => true, // enable source map
      // import dirs are search first
      'import_dirs' => array(
        dirname(__FILE__) . '/less/import'
      )
  ),
  // cache implementation
  new ILess_Cache_FileSystem($cacheDir)
);

  // parse file
  $parser->parseFile('less/test.less');

  // parse additional string
  $parser->parseString('
  #header {
    background: black;
  }');

  $cssContent = $parser->getCSS();
  file_put_contents($cacheDir. '/screen.css', $cssContent);
  $css = 'cache/screen.css';
}
catch(Exception $e)
{
  @header('HTTP/1.0 500 Internal Server Error');
  echo $e;
  exit;
}

$example = 'source map output';

include '_page.php';
