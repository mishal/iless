<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Expression node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Expression
 */
class ILess_Test_Node_ExpressionTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ILess_Node_Expression(array(new ILess_Node_Anonymous('foobar')));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ILess_Node_Expression(array(new ILess_Node_Anonymous('foobar')));
        $this->assertEquals('Expression', $d->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new ILess_Environment();
        $output = new ILess_Output();
        $d = new ILess_Node_Expression(array(new ILess_Node_Anonymous('foobar')));
        $d->generateCss($env, $output);
        $this->assertEquals('foobar', $output->toString());
    }

    /**
     * @covers compile
     */
    public function testCompile()
    {
        $env = new ILess_Environment();
        $d = new ILess_Node_Expression(array(new ILess_Node_Anonymous('foobar')));
        $result = $d->compile($env);
        $this->assertInstanceOf('ILess_Node_Anonymous', $result);
        $this->assertEquals('foobar', $result->toCSS($env));

        // FIXME: more tests!
    }

}
