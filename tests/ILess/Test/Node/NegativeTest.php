<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Node\AnonymousNode;
use ILess\Node\NegativeNode;

/**
 * Negative node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Negative
 * @group node
 */
class Test_Node_NegativeTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new NegativeNode(new AnonymousNode('bar'));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new NegativeNode(new AnonymousNode('bar'));
        $this->assertEquals('Negative', $d->getType());
    }
}
