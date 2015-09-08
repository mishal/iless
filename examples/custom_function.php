<?php

use ILess\Exception\Exception;
use ILess\FunctionRegistry;
use ILess\Node;
use ILess\Node\AnonymousNode;
use ILess\Node\ColorNode;
use ILess\Parser;

require_once '_bootstrap.php';

class myLessUtils
{

    public static function foobar(FunctionRegistry $registry, Node $color = null)
    {
        // what can you do here, look to ILess\FunctionRegistry.php
        if ($color instanceof ColorNode) {
            return new AnonymousNode('"Color is here"');
        }

        return new AnonymousNode('"Foobar is here!"');
    }
}

try {

    $cacheDir = dirname(__FILE__).'/cache';
    $parser = new Parser();
    // adds a function with an alias: fb
    $parser->addFunction('foobar', array('myLessUtils', 'foobar'), array(
        'fb',
    ));
    $parser->parseString('
  @color: red;
  #head {
    color: foobar(@color);
    font-size: fb();
  }');
} catch (Exception $e) {
    @header('HTTP/1.0 500 Internal Server Error');
    echo $e;
    exit;
}

$cssContent = $parser->getCSS();
file_put_contents($cacheDir.'/function.css', $cssContent);
$css = 'cache/function.css';

$example = 'custom function';
include '_page.php';
