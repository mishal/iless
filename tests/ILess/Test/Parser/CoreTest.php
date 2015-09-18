<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Cache\NoCache;
use ILess\Context;
use ILess\FunctionRegistry;
use ILess\Importer;
use ILess\Importer\FileSystemImporter;
use ILess\Parser\Core;

/**
 * Parser tests
 *
 * @package ILess
 * @subpackage test
 * @covers Parser_Core
 */
class Test_Parser_CoreTest extends Test_TestCase
{
    public function setUp()
    {
        $env = new Context([], new FunctionRegistry());

        $importer = new Importer($env, [
            new FileSystemImporter(),
        ], new NoCache());

        $this->parser = new Core($env, $importer);
    }

    public function testAssignVariablesAndReset()
    {
        $this->parser->setVariables([
            'color' => 'red',
        ]);

        $this->parser->parseString('body { color: @color; }');
        $generated = $this->parser->getCSS();

        $this->assertEquals(
            'body {
  color: red;
}
', $generated);

        // reset
        $this->parser->reset();
        $this->assertEquals('', $this->parser->getCSS());

        $this->parser->setVariables([
            'color' => 'blue',
        ]);

        $this->parser->parseString('body { color: @color; }');

        $this->parser->reset(false);

        $this->parser->parseString('h1 { color: @color; }');

        $generated = $this->parser->getCSS();

        $this->assertEquals(
            'h1 {
  color: blue;
}
', $generated);

    }

}
