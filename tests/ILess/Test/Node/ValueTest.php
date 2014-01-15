<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Value node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Value
 */
class ILess_Test_Node_ValueTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $v = new ILess_Node_Value(array(
            new ILess_Node_Anonymous('foobar')
        ));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $v = new ILess_Node_Value(array(
            new ILess_Node_Anonymous('foobar')
        ));
        $this->assertEquals('Value', $v->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new ILess_Environment();
        $output = new ILess_Output();

        $v = new ILess_Node_Value(array(
            new ILess_Node_Anonymous('foobar')
        ));

        $v->generateCss($env, $output);
        $this->assertEquals('foobar', $output->toString());
    }

}
