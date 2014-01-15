<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Dimension node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Directive
 */
class ILess_Test_Node_DirectiveTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ILess_Node_Directive('15', new ILess_Node_Anonymous('bar'));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ILess_Node_Directive('15', new ILess_Node_Anonymous('bar'));
        $this->assertEquals('Directive', $d->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new ILess_Environment();
        $output = new ILess_Output();

        $d = new ILess_Node_Directive('15', new ILess_Node_Anonymous('bar'));

        $d->generateCss($env, $output);

        $this->assertEquals('15 bar;', $output->toString());
    }

    /**
     * @covers variable
     */
    public function testVariable()
    {
        // FIXME: implement more!
        $d = new ILess_Node_Directive('15', new ILess_Node_Anonymous('bar'));
        $this->assertEquals(null, $d->variable('foo'));
    }

}
