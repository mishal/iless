<?php

use ILess\Node\AnonymousNode;

$this->addFunctions(array(
    'test-shadow' => function () {
        return new AnonymousNode('local');
    },
    'test-local' => function () {
        return new AnonymousNode('local');
    }
));
