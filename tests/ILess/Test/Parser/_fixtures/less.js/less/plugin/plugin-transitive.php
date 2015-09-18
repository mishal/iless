<?php

use ILess\Node\AnonymousNode;

$this->addFunctions([
    'test-transitive' => function () {
        return new AnonymousNode('transitive');
    }
]);
