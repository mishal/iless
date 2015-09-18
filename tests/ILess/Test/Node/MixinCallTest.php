<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\ElementNode;
use ILess\Node\MixinCallNode;

/**
 * MixinCall node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_MixinCall
 * @group node
 */
class Test_Node_MixinCallTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $mc = new MixinCallNode([new ElementNode('', 'foobar')]);
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $mc = new MixinCallNode([new ElementNode('', 'foobar')]);
        $this->assertEquals('MixinCall', $mc->getType());
    }

    /**
     * @covers compile
     */
    public function testCompile()
    {
        $this->setExpectedException('ILess\Exception\CompilerException');

        $env = new Context();
        $mc = new MixinCallNode([new ElementNode('', 'foobar')]);

        // throws an exception
        $result = $mc->compile($env);
    }

}
