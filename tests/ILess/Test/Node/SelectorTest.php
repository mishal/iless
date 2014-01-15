<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Selector node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Selector
 */
class ILess_Test_Node_SelectorTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $s = new ILess_Node_Selector(array(
            new ILess_Node_Element(' ', 'foobar')
        ), array());
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $s = new ILess_Node_Selector(array(
            new ILess_Node_Element(' ', 'foobar')
        ), array());
        $this->assertEquals('Selector', $s->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new ILess_Environment();
        $output = new ILess_Output();

        $s = new ILess_Node_Selector(array(
            new ILess_Node_Element(' ', 'foobar')
        ), array());

        $s->generateCss($env, $output);
        $this->assertEquals(" foobar", $output->toString());
    }

}
