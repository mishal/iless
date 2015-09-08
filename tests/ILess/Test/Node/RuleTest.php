<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\AnonymousNode;
use ILess\Node\RuleNode;
use ILess\Output\StandardOutput;

/**
 * Rule node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Rule
 * @group node
 */
class Test_Node_RuleTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        // FIXME: this rule does not make sense!
        $r = new RuleNode('foobar', new AnonymousNode('foobar'));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $r = new RuleNode('foobar', new AnonymousNode('foobar'));
        $this->assertEquals('Rule', $r->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new Context();
        $output = new StandardOutput();

        $r = new RuleNode('foobar', new AnonymousNode('yellow'));
        $r->generateCss($env, $output);
        $this->assertEquals('foobar: yellow;', $output->toString());
    }

}
