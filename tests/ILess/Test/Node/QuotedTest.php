<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Quoted node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Quoted
 */
class ILess_Test_Node_QuotedTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $q = new ILess_Node_Quoted('"foobar"', 'foobar');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $q = new ILess_Node_Quoted('"foobar"', 'foobar');
        $this->assertEquals('Quoted', $q->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new ILess_Environment();
        $output = new ILess_Output();

        $q = new ILess_Node_Quoted('"foobar"', 'foobar');
        $q->generateCss($env, $output);
        $this->assertEquals('"foobar"', $output->toString());
    }

}
