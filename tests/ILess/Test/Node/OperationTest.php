<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\AnonymousNode;
use ILess\Node\OperationNode;
use ILess\Output\StandardOutput;

/**
 * Operation node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Operation
 * @group node
 */
class Test_Node_OperationTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $o = new OperationNode('+', [new AnonymousNode('10'), new AnonymousNode('15')]);
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $o = new OperationNode('+', [new AnonymousNode('10'), new AnonymousNode('15')]);
        $this->assertEquals('Operation', $o->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new Context();
        $output = new StandardOutput();

        $o = new OperationNode('+', [new AnonymousNode('10'), new AnonymousNode('15')]);
        $o->generateCss($env, $output);
        $this->assertEquals('10+15', $output->toString());
    }

}
