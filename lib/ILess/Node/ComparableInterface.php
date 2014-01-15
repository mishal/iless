<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Node comparable interface
 *
 * @package ILess
 * @subpackage node
 */
interface ILess_Node_ComparableInterface
{
    /**
     * Compares with another node
     *
     * @param ILess_Node $other The other node
     * @return integer
     */
    public function compare(ILess_Node $other);
}
