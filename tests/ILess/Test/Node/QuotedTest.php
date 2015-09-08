<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\QuotedNode;
use ILess\Output\StandardOutput;

/**
 * Quoted node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Quoted
 * @group node
 */
class Test_Node_QuotedTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $q = new QuotedNode('"foobar"', 'foobar');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $q = new QuotedNode('"foobar"', 'foobar');
        $this->assertEquals('Quoted', $q->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new Context();
        $output = new StandardOutput();

        $q = new QuotedNode('"foobar"', 'foobar');
        $q->generateCss($env, $output);
        $this->assertEquals('"foobar"', $output->toString());
    }

}
