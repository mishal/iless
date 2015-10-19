<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Node;

/**
 * Node comparable interface.
 */
interface ComparableInterface
{
    /**
     * Compares the another node.
     *
     * @param Node $other
     *
     * @return int|null
     */
    public function compare(Node $other);
}
