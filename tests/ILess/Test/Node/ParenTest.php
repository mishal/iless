<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\AnonymousNode;
use ILess\Node\ParenNode;
use ILess\Output\StandardOutput;

/**
 * Paren node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Paren
 * @group node
 */
class Test_Node_ParenTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $p = new ParenNode(new AnonymousNode('foobar'));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $p = new ParenNode(new AnonymousNode('foobar'));
        $this->assertEquals('Paren', $p->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new Context();
        $output = new StandardOutput();

        $p = new ParenNode(new AnonymousNode('foobar'));
        $p->generateCss($env, $output);
        $this->assertEquals('(foobar)', $output->toString());
    }

}
