<?php

use ILess\Node\AnonymousNode;

$this->addFunctions(array(
    'test-transitive' => function () {
        return new AnonymousNode('transitive');
    }
));
