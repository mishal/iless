<?php

use ILess\FunctionRegistry;
use ILess\Node\ColorNode;
use ILess\Node\DimensionNode;

$parser->addVariables([
    'color' => 'white'
]);

$parser->addFunction('superdarken', function (FunctionRegistry $registry, ColorNode $color) {
    return $registry->call('darken', [$color, new DimensionNode(80, '%')]);
});
