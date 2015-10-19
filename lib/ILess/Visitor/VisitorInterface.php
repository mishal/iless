<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Visitor;

/**
 * Visitor interface.
 */
interface VisitorInterface
{
    /**
     * Pre compilation visitor.
     */
    const TYPE_PRE_COMPILE = 'pre';

    /**
     * Post compilation visitor.
     */
    const TYPE_POST_COMPILE = 'post';

    /**
     * Runs the visitor.
     *
     * @param ILess\Node|array
     */
    public function run($root);

    /**
     * @param mixed $node
     *
     * @return mixed
     */
    public function visit($node);

    /**
     * Visits an array of nodes.
     *
     * @param array $nodes
     * @param bool $nonReplacing
     *
     * @return mixed
     */
    public function visitArray(array $nodes, $nonReplacing = false);

    /**
     * Is the visitor replacing?
     *
     * @return bool
     */
    public function isReplacing();

    /**
     * Return the visitor type.
     *
     * @return string
     */
    public function getType();
}
