<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Negative node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Negative
 */
class ILess_Test_Node_NegativeTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ILess_Node_Negative(new ILess_Node_Anonymous('bar'));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ILess_Node_Negative(new ILess_Node_Anonymous('bar'));
        $this->assertEquals('Negative', $d->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        /*
        $env = new ILess_Environment();
        $output = new ILess_Output();

        $d = new ILess_Node_Directive('15', new ILess_Node_Anonymous('bar'));

        $d->generateCss($env, $output);

        $this->assertEquals('15 bar;', $output->toString());
         *
         */
    }

}
