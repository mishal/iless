<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Operation node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Operation
 */
class ILess_Test_Node_OperationTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $o = new ILess_Node_Operation('+', array(new ILess_Node_Anonymous('10'), new ILess_Node_Anonymous('15')));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $o = new ILess_Node_Operation('+', array(new ILess_Node_Anonymous('10'), new ILess_Node_Anonymous('15')));
        $this->assertEquals('Operation', $o->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new ILess_Environment();
        $output = new ILess_Output();

        $o = new ILess_Node_Operation('+', array(new ILess_Node_Anonymous('10'), new ILess_Node_Anonymous('15')));
        $o->generateCss($env, $output);
        $this->assertEquals('10+15', $output->toString());
    }

}
