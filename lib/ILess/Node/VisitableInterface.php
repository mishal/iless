<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Node visitable interface
 *
 * @package ILess
 * @subpackage node
 */
interface ILess_Node_VisitableInterface
{
    /**
     * Accepts a visit by a visitor
     *
     * @param ILess_Visitor $visitor
     * @return void
     */
    public function accept(ILess_Visitor $visitor);

}
