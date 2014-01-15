<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Element node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Element
 */
class ILess_Test_Node_ElementTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ILess_Node_Element('>', new ILess_Node_Anonymous('bar'));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ILess_Node_Element('>', new ILess_Node_Anonymous('bar'));
        $this->assertEquals('Element', $d->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new ILess_Environment();
        $output = new ILess_Output();
        $d = new ILess_Node_Element('>', new ILess_Node_Anonymous('bar'));
        $d->generateCss($env, $output);
        $this->assertEquals(' > bar', $output->toString());
    }

}
