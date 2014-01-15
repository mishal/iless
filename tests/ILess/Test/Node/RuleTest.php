<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Rule node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Rule
 */
class ILess_Test_Node_RuleTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        // FIXME: this rule does not make sense!
        $r = new ILess_Node_Rule('foobar', new ILess_Node_Anonymous('foobar'));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $r = new ILess_Node_Rule('foobar', new ILess_Node_Anonymous('foobar'));
        $this->assertEquals('Rule', $r->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new ILess_Environment();
        $output = new ILess_Output();

        $r = new ILess_Node_Rule('foobar', new ILess_Node_Anonymous('yellow'));
        $r->generateCss($env, $output);
        $this->assertEquals('foobar: yellow;', $output->toString());
    }

}
