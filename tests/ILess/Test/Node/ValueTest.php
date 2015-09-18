<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\AnonymousNode;
use ILess\Node\ValueNode;
use ILess\Output\StandardOutput;

/**
 * Value node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Value
 * @group node
 */
class Test_Node_ValueTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $v = new ValueNode([
            new AnonymousNode('foobar')
        ]);
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $v = new ValueNode([
            new AnonymousNode('foobar')
        ]);
        $this->assertEquals('Value', $v->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new Context();
        $output = new StandardOutput();

        $v = new ValueNode([
            new AnonymousNode('foobar')
        ]);

        $v->generateCss($env, $output);
        $this->assertEquals('foobar', $output->toString());
    }

}
