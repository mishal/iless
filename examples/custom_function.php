<?php

require_once '_bootstrap.php';

class myLessUtils {

  public static function foobar(ILess_FunctionRegistry $registry, ILess_Node $color = null)
  {
    // what can you do here, look to FunctionRegistry.php
    if($color instanceof ILess_Node_Color)
    {
      return new ILess_Node_Anonymous('"Color is here"');
    }
    return new ILess_Node_Anonymous('"Foobar is here!"');
  }
}

try {

  $cacheDir = dirname(__FILE__) . '/cache';
  $parser = new ILess_Parser();
  // adds a function with an alias: fb
  $parser->addFunction('foobar', array('myLessUtils', 'foobar'), array(
    'fb'
  ));
  $parser->parseString('
  @color: red;
  #head {
    color: foobar(@color);
    font-size: fb();
  }');
}
catch(Exception $e)
{
  @header('HTTP/1.0 500 Internal Server Error');
  echo $e;
  exit;
}

$cssContent = $parser->getCSS();
file_put_contents($cacheDir. '/function.css', $cssContent);
$css = 'cache/function.css';

$example = 'custom function';
include '_page.php';