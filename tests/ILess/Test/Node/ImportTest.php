<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Import node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Import
 */
class ILess_Test_Node_ImportTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ILess_Node_Import(new ILess_Node_Url(new ILess_Node_Quoted('"foobar.css"', 'foobar.css')));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ILess_Node_Import(new ILess_Node_Url(new ILess_Node_Quoted('"foobar.css"', 'foobar.css')));
        $this->assertEquals('Import', $d->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new ILess_Environment();
        $output = new ILess_Output();
        $d = new ILess_Node_Import(new ILess_Node_Url(new ILess_Node_Quoted('"foobar.css"', 'foobar.css')));
        $result = $d->generateCss($env, $output);
        $this->assertInstanceOf('ILess_Output', $result);
        $this->assertEquals('@import url("foobar.css");', $output->toString());

        // import less when no extension is specified
        // nothing gets to output
        $output = new ILess_Output();
        $d = new ILess_Node_Import(new ILess_Node_Url(new ILess_Node_Quoted('"foobar"', 'foobar')));
        $d->generateCss($env, $output);
        $this->assertEquals('', $output->toString());
    }

    public function testCompileForImport()
    {
        $env = new ILess_Environment();
        $d = new ILess_Node_Import(new ILess_Node_Url(new ILess_Node_Quoted('"foobar.css"', 'foobar.css')));

        $result = $d->compileForImport($env);
        $this->assertInstanceOf('ILess_Node_Import', $result);
    }

    public function testCompile()
    {
        $env = new ILess_Environment();
        $d = new ILess_Node_Import(new ILess_Node_Url(new ILess_Node_Quoted('"foobar.css"', 'foobar.css')));

        $result = $d->compile($env);

        $this->assertInstanceOf('ILess_Node_Import', $result);
        $this->assertEquals('@import url("foobar.css");', $result->toCSS($env));
    }

}
