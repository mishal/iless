<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Ruleset node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Ruleset
 */
class ILess_Test_Node_RulesetTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $r = new ILess_Node_Ruleset(array(
            new ILess_Node_Element('div', 'foobar')
        ), array());
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $r = new ILess_Node_Ruleset(array(
            new ILess_Node_Element(' ', 'foobar')
        ), array());
        $this->assertEquals('Ruleset', $r->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new ILess_Environment();
        $output = new ILess_Output();

        $r = new ILess_Node_Ruleset(array(
            new ILess_Node_Selector(array(new ILess_Node_Element('', 'div'))),
        ), array(
            new ILess_Node_Rule('color', new ILess_Node_Color('#fff')),
            new ILess_Node_Rule('font-weight', new ILess_Node_Keyword('bold')),
        ));

        // $r->debugInfo = new ILess_DebugInfo('foo', 1);

        $args = new ILess_Visitor_Arguments(array(
            'visitDeeper' => true
        ));
        $visitor = new ILess_Visitor_JoinSelector();
        $visitor->visitRuleset($r, $args);

        $n = new ILess_Node_Rule('font-weight', new ILess_Node_Keyword('bold'));
        $n->generateCss($env, $output);

        $this->assertEquals('font-weight: bold;', $output->toString());

        $output = new ILess_Output();
        $r->generateCss($env, $output);
        $this->assertEquals("div {\n  color: #ffffff;\n  font-weight: bold;\n}", $output->toString());
    }

}
