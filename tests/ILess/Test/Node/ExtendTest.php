<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\AnonymousNode;
use ILess\Node\ExtendNode;
use ILess\Node\SelectorNode;

/**
 * Extend node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Extend
 * @group node
 */
class Test_Node_ExtendTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ExtendNode(new SelectorNode([new AnonymousNode('foobar')]), 'all');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ExtendNode(new SelectorNode([new AnonymousNode('foobar')]), 'all');
        $this->assertEquals('Extend', $d->getType());
    }

    /**
     * @covers compile
     */
    public function testCompile()
    {
        $env = new Context();
        $d = new ExtendNode(new SelectorNode([new AnonymousNode('foobar')]), 'all');
        $result = $d->compile($env);
        $this->assertInstanceOf('ILess\Node\ExtendNode', $result);
    }

}
