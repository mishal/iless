<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Visitor\VisitorInterface;

/**
 * Visitable interface.
 */
interface VisitableInterface
{
    /**
     * Accepts a visit by a visitor.
     *
     * @param VisitorInterface $visitor
     */
    public function accept(VisitorInterface $visitor);
}
