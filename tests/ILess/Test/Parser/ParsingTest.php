<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Parsing tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Parser_Core
 */
class ILess_Test_Parser_ParsingTest extends ILess_Test_TestCase
{
    public function setUp()
    {
        $env = new ILess_Environment(array(), new ILess_FunctionRegistry());
        $importer = new ILess_Importer($env, array(
            new ILess_Importer_FileSystem()
        ), new ILess_Cache_None());
        $this->parser = new ILess_Test_Parser_Core($env, $importer);
    }

    public function testSimpleCompilation()
    {
        $less = glob(dirname(__FILE__) . '/_fixtures/simple/less/*.less');
        $css = glob(dirname(__FILE__) . '/_fixtures/simple/css/*.css');

        foreach ($less as $i => $lessFile) {
            $this->setUp();
            $this->parser->parseFile($lessFile);
            $preCompiled = file_get_contents($css[$i]);
            $this->assertEquals($preCompiled, $this->parser->getCSS(), sprintf('Testing compilation for %s', basename($lessFile)));
        }
    }

    public function testCompilation()
    {
        $fixturesDir = dirname(__FILE__) . '/_fixtures';
        $less = glob($fixturesDir . '/less.js/less/*.less');
        $css = glob($fixturesDir . '/less.js/css/*.css');

        // skip
        $skip = array(
            'functions.less'
        );

        foreach ($less as $i => $lessFile) {
            if (in_array(basename($lessFile), $skip)) {
                $this->diag('Skipping test ' . basename($lessFile));
                continue;
            }

            // reset the parser for each test
            $this->setup();

            $this->parser->parseFile($lessFile);
            $compiled = $this->parser->getCss();

            $preCompiled = file_get_contents($css[$i]);

            // known diff, check of the diff is still ok
            if (is_readable($fixturesDir . '/diff/' . basename($lessFile) . '.php')) {
                // FIXME: check the diff
            } else {
                $this->assertEquals($preCompiled, $compiled, sprintf('Compilated CSS matches for "%s"', basename($lessFile)));
            }
        }
    }

    public function testPhpCompilation()
    {
        $less = glob(dirname(__FILE__) . '/_fixtures/php/less/*.less');
        $css = glob(dirname(__FILE__) . '/_fixtures/php/css/*.css');

        foreach ($less as $i => $lessFile) {
            // reset the parser for each test
            $this->setup();

            $this->parser->setVariables(array(
                'a' => 'black',
                'fontdir' => '/fonts',
                'base' => '12px',
                'myurl' => '"http://example.com/image.jpg"'
            ));

            $this->parser->parseFile($lessFile);
            $compiled = $this->parser->getCss();
            $preCompiled = file_get_contents($css[$i]);

            // $this->diag(sprintf('Testing compilation for %s', basename($lessFile)));
            $this->assertSame(addslashes($preCompiled), addslashes($compiled), sprintf('Compilated CSS is ok for "%s".', basename($lessFile)));
        }
    }

}
