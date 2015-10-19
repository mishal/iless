<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

/**
 * ToColorConvertibleInterface.
 */
interface ToColorConvertibleInterface
{
    /**
     * Converts the node to a color node.
     *
     * @return ColorNode
     */
    public function toColor();
}
