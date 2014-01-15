<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Paren node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Paren
 */
class ILess_Test_Node_ParenTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $p = new ILess_Node_Paren(new ILess_Node_Anonymous('foobar'));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $p = new ILess_Node_Paren(new ILess_Node_Anonymous('foobar'));
        $this->assertEquals('Paren', $p->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new ILess_Environment();
        $output = new ILess_Output();

        $p = new ILess_Node_Paren(new ILess_Node_Anonymous('foobar'));
        $p->generateCss($env, $output);
        $this->assertEquals('(foobar)', $output->toString());
    }

}
