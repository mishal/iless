<?php

/*
 * This file is part of the Sift PHP framework.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\Node;

/**
 * Compilable interface
 *
 * @package ILess\Node
 */
interface CompilableInterface
{
    /**
     * Compiles the node
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param boolean|null $important Important flag
     * @return Node
     */
    public function compile(Context $context, $arguments = null, $important = null);
}
