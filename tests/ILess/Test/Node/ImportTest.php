<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\ImportNode;
use ILess\Node\UrlNode;
use ILess\Node\QuotedNode;
use ILess\Output\StandardOutput;

/**
 * Import node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Import
 * @group node
 */
class Test_Node_ImportTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ImportNode(new UrlNode(new QuotedNode('"foobar.css"', 'foobar.css')));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ImportNode(new UrlNode(new QuotedNode('"foobar.css"', 'foobar.css')));
        $this->assertEquals('Import', $d->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new Context();
        $output = new StandardOutput();
        $d = new ImportNode(new UrlNode(new QuotedNode('"foobar.css"', 'foobar.css')));
        $result = $d->generateCss($env, $output);
        $this->assertInstanceOf('ILess\Output\StandardOutput', $result);
        $this->assertEquals('@import url("foobar.css");', $output->toString());

        // import less when no extension is specified
        // nothing gets to output
        $output = new StandardOutput();
        $d = new ImportNode(new UrlNode(new QuotedNode('"foobar"', 'foobar')));
        $d->generateCss($env, $output);
        $this->assertEquals('', $output->toString());
    }

    public function testCompileForImport()
    {
        $env = new Context();
        $d = new ImportNode(new UrlNode(new QuotedNode('"foobar.css"', 'foobar.css')));

        $result = $d->compileForImport($env);
        $this->assertInstanceOf('ILess\Node\ImportNode', $result);
    }

    public function testCompile()
    {
        $env = new Context();
        $d = new ImportNode(new UrlNode(new QuotedNode('"foobar.css"', 'foobar.css')));

        $result = $d->compile($env);

        $this->assertInstanceOf('ILess\Node\ImportNode', $result);
        $this->assertEquals('@import url("foobar.css");', $result->toCSS($env));
    }

}
