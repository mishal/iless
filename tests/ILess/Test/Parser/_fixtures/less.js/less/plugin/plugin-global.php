<?php

/* @var $this FunctionRegistry */
// executed in the context of function registry

use ILess\FunctionRegistry;
use ILess\Node\AnonymousNode;

$this->addFunctions([
    'test-shadow' => function () {
        return new AnonymousNode("global");
    },
    'test-global' => function () {
        return new AnonymousNode("global");
    }
]);
