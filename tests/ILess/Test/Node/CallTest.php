<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Call node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Call
 */
class ILess_Test_Node_CallTest extends ILess_Test_TestCase
{
    /**
     * @covers getType
     */
    public function testGetType()
    {
        $a = new ILess_Node_Call('foo', array(), 0);
        $this->assertEquals('Call', $a->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCSS()
    {
        $env = new ILess_Environment();

        $a = new ILess_Node_Call('foo', array(), 0);
        $output = new ILess_Output();

        $a->generateCss($env, $output);
        $this->assertEquals($output->toString(), 'foo()');

        // a bit complicated
        $a = new ILess_Node_Call('foo', array(
            new ILess_Node_Anonymous('arg1'),
            new ILess_Node_Anonymous('arg2'),
        ), 0);

        $output = new ILess_Output();
        $a->generateCss($env, $output);
        $this->assertEquals($output->toString(), 'foo(arg1, arg2)');
    }

}
