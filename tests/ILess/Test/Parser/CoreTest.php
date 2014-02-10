<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__FILE__) . '/Core.php';

/**
 * Parser tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Parser_Core
 */
class ILess_Test_Parser_CoreTest extends ILess_Test_TestCase
{
    public function setUp()
    {
        $env = new ILess_Environment(array(), new ILess_FunctionRegistry());
        $importer = new ILess_Importer($env, array(
            new ILess_Importer_FileSystem()
        ), new ILess_Cache_None());
        $this->parser = new ILess_Test_Parser_Core($env, $importer);
    }

    /**
     * @covers       parseEntitiesColor
     * @dataProvider getDataForParseColorTest
     */
    public function testParseColor($color, $expected)
    {
        $result = $this->parser->testParseColor($color);
        $this->assertInstanceOf('ILess_Node_Color', $result);

        $this->assertEquals($result->toCSS(new ILess_Environment()), $expected);
    }

    public function getDataForParseColorTest()
    {
        return array(
            array('#fff', '#ffffff'),
            array('#c2c3c4', '#c2c3c4')
        );
    }

    /**
     * @covers ILess_Parser::parseDirective
     */
    public function testParseDirective()
    {
        $result = $this->parser->testParseDirective('@charset "utf-8";');
        $this->assertInstanceOf('ILess_Node_Directive', $result);

        $output = new ILess_Output();
        $env = new ILess_Environment();
        $result->generateCss($env, $output);

        $this->assertEquals('@charset "utf-8";', $output->toString());
    }

    /**
     * @covers       ILess_Parser::parseComment
     * @dataProvider getDataForParseCommentTest
     */
    public function testParseComment($comment, $expected, $silent)
    {
        $result = $this->parser->testParseComment($comment);
        $this->assertInstanceOf('ILess_Node_Comment', $result);

        $env = new ILess_Environment();

        $this->assertEquals($silent, $result->isSilent($env));

        $output = new ILess_Output();

        $result->generateCss($env, $output);

        $this->assertEquals($expected, $output->toString());
    }

    public function getDataForParseCommentTest()
    {
        return array(
            array('/* This is an CSS comment */', '/* This is an CSS comment */', false),
            array('// This is an less comment', '// This is an less comment', true),
        );
    }

    /**
     * @covers ILess_Parser::parseEntitiesQuoted
     */
    public function testParseEntitiesQuoted()
    {
        $result = $this->parser->testParseEntitiesQuoted('"milky way"');
        $this->assertInstanceOf('ILess_Node_Quoted', $result);

        $output = new ILess_Output();
        $env = new ILess_Environment();
        $result->generateCss($env, $output);

        $this->assertEquals('"milky way"', $output->toString());
    }

    /**
     * @covers ILess_Parser::parseEntitiesKeyword
     */
    public function testParseEntitiesKeyword()
    {
        $result = $this->parser->testParseEntitiesKeyword('black');
        $this->assertInstanceOf('ILess_Node_Color', $result);

        $output = new ILess_Output();
        $env = new ILess_Environment();
        $result->generateCss($env, $output);

        $this->assertEquals('#000000', $output->toString());

        $this->setUp();

        $result = $this->parser->testParseEntitiesKeyword('border-collapse');
        $this->assertInstanceOf('ILess_Node_Keyword', $result);

        $output = new ILess_Output();
        $env = new ILess_Environment();
        $result->generateCss($env, $output);

        $this->assertEquals('border-collapse', $output->toString());
    }

    /**
     * @covers ILess_Parser::parseMixinDefinition
     */
    public function testParseMixinDefinition()
    {
        $result = $this->parser->testParseMixinDefinition('.rounded (@radius: 2px, @color){}');
        $this->assertInstanceOf('ILess_Node_MixinDefinition', $result);
    }

    /**
     * @covers       ILess_Parser::parseEntity
     * @dataProvider getDataForEntityTest
     */
    public function testParseEntity($string, $expectedNode, $expectedOutput)
    {
        $result = $this->parser->testParseEntity($string);
        $this->assertInstanceOf($expectedNode, $result);
        $output = new ILess_Output();
        $env = new ILess_Environment();
        $result->generateCss($env, $output);
        $this->assertEquals($expectedOutput, $output->toString());
    }

    public function getDataForEntityTest()
    {
        return array(
            // function calls
            array('foobar()', 'ILess_Node_Call', 'foobar()'),
            array('foobar("1")', 'ILess_Node_Call', 'foobar("1")'), // quoted
            array('foobar(true)', 'ILess_Node_Call', 'foobar(true)'),
            array('foobar(#fff, 1px)', 'ILess_Node_Call', 'foobar(#ffffff, 1px)'),
            // alpha
            array('alpha(opacity=100)', 'ILess_Node_Alpha', 'alpha(opacity=100)'),

            // dimensions
            array('1px', 'ILess_Node_Dimension', '1px'),
            array('10%', 'ILess_Node_Dimension', '10%'),

            // url
            array('url("http://foo.com")', 'ILess_Node_Url', 'url("http://foo.com")'),

            // keyword
            array('foobar', 'ILess_Node_Keyword', 'foobar'),

            // variables
            array('@color', 'ILess_Node_Variable', ''),
        );

    }

    /**
     * @covers       parseSelector
     * @dataProvider getDataForSelectorTest
     */
    public function testParseSelector($string, $expectedOutput)
    {
        $result = $this->parser->testParseSelector($string);
        $this->assertInstanceOf('ILess_Node_Selector', $result);
        $output = new ILess_Output();
        $env = new ILess_Environment();
        $result->generateCss($env, $output);
        $this->assertEquals($expectedOutput, $output->toString());
    }

    public function getDataForSelectorTest()
    {
        return array(
            array('h1 {}', ' h1'),
            array('div > h1 {}', ' div > h1'),
            array('div > h1:last {}', ' div > h1:last')
        );
    }

    /**
     * @covers       parseElement
     * @dataProvider getDataForElementTest
     */
    public function testParseElement($string, $expectedOutput)
    {
        $result = $this->parser->testParseElement($string);
        $this->assertInstanceOf('ILess_Node_Element', $result);
        $output = new ILess_Output();
        $env = new ILess_Environment();
        $result->generateCss($env, $output);
        $this->assertEquals($expectedOutput, $output->toString());
    }

    public function getDataForElementTest()
    {
        return array(
            array('h1 {}', 'h1'),
            array('div > h1 {}', 'div'),
            array('div > h1:last {}', 'div')
        );
    }

    /**
     * @covers       parseMedia
     * @dataProvider getDataForMediaTest
     */
    public function testParseMedia($string)
    {
        $result = $this->parser->testParseMedia($string);
        $this->assertInstanceOf('ILess_Node_Media', $result);
    }

    public function getDataForMediaTest()
    {
        return array(
            array('@media screen { body { max-width: 60; } }'),
        );
    }

    public function testAssignVariablesAndReset()
    {
        $env = new ILess_Environment(array(), new ILess_FunctionRegistry());
        $importer = new ILess_Importer($env, array(
            new ILess_Importer_FileSystem()
        ), new ILess_Cache_None());
        $parser = new ILess_Test_Parser_Core($env, $importer);

        $parser->setVariables(array(
            'color' => 'red'
        ));

        $parser->parseString('body { color: @color; }');
        $generated = $parser->getCSS();

        $this->assertEquals(
'body {
  color: #ff0000;
}
', $generated);

        // reset
        $parser->reset();
        $this->assertEquals('', $parser->getCSS());

        $parser->setVariables(array(
            'color' => 'blue'
        ));

        $parser->parseString('body { color: @color; }');
        $parser->reset(false);

        $parser->parseString('h1 { color: @color; }');

        $generated = $parser->getCSS();
        $this->assertEquals(
'h1 {
  color: #0000ff;
}
', $generated);

    }

}
