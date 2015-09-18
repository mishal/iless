<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\AnonymousNode;
use ILess\Node\ExpressionNode;
use ILess\Output\StandardOutput;

/**
 * Expression node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Expression
 * @group node
 */
class Test_Node_ExpressionTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ExpressionNode([new AnonymousNode('foobar')]);
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ExpressionNode([new AnonymousNode('foobar')]);
        $this->assertEquals('Expression', $d->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new Context();
        $output = new StandardOutput();
        $d = new ExpressionNode([new AnonymousNode('foobar')]);
        $d->generateCss($env, $output);
        $this->assertEquals('foobar', $output->toString());
    }

    /**
     * @covers compile
     */
    public function testCompile()
    {
        $env = new Context();
        $d = new ExpressionNode([new AnonymousNode('foobar')]);
        $result = $d->compile($env);
        $this->assertInstanceOf('ILess\Node\AnonymousNode', $result);
        $this->assertEquals('foobar', $result->toCSS($env));

        // FIXME: more tests!
    }

}
