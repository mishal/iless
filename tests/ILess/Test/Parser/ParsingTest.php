<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\DebugInfo;
use ILess\FunctionRegistry;
use ILess\Node;
use ILess\Node\ColorNode;
use ILess\Node\DimensionNode;
use ILess\Parser;

/**
 * Parsing tests
 *
 * @package ILess
 * @subpackage test
 * @covers Parser_Core
 */
class Test_Parser_ParsingTest extends Test_TestCase
{
    /**
     * Parser default options
     *
     * @var array
     */
    protected $parserDefaultOptions = array(
        'strictUnits' => false,
    );

    /**
     * @param array $options
     * @return Parser
     */
    protected function createParser($options = array())
    {
        $parser = new Parser($options);
        // test functions
        $parser->addFunctions(
            array(
                '_color' => function (FunctionRegistry $registry, Node $a) {
                    if ($a->value === 'evil red') {
                        return new ColorNode('600');
                    }
                },
                'increment' => function (FunctionRegistry $registry, Node $a) {
                    return new DimensionNode($a->value + 1);
                },
                'add' => function (FunctionRegistry $registry, Node $a, Node $b) {
                    return new DimensionNode($a->value + $b->value);
                },
            )
        );

        return $parser;
    }

    /**
     * @dataProvider getCompilationData
     */
    public function testCompilation($lessFile, $cssFile, $options = array(), $variables = array(), $filter = null)
    {
        // default options
        if ($options !== false && !count($options)) {
            $options = $this->parserDefaultOptions;
        }

        $parser = $this->createParser($options);
        $parser->setVariables($variables);

        echo "Compilation test for ".basename($lessFile)."\n";

        $parser->parseFile($lessFile);
        $preCompiled = file_get_contents($cssFile);

        $compiled = $parser->getCSS();

        if (is_callable($filter)) {
            $compiled = call_user_func($filter, $compiled);
        }

        // known diff, check if the diff is still ok
        if (is_readable($diffFile = str_replace('/less/', '/diff/', $lessFile.'.php'))) {
            $diff = include $diffFile;
            $actualDiff = array_diff(explode("\n", $compiled), explode("\n", $preCompiled));
            foreach ($diff as $lineNum => $change) {
                if ($change[0] == '#') {
                    if (preg_match($change, @$actualDiff[$lineNum])) {
                        unset($actualDiff[$lineNum]);
                    }
                } else {
                    if (@$actualDiff[$lineNum] == $change) {
                        unset($actualDiff[$lineNum]);
                    }
                }
            }
            $this->assertEquals($actualDiff, array());
        } else {
            $this->assertEquals($preCompiled, $compiled, "Compilation error for: ".basename($lessFile));
        }
    }

    public function getCompilationData()
    {
        $fixturesDir = dirname(__FILE__).'/_fixtures';

        $filesToTest = glob($fixturesDir.'/less.js/less/*.less');

        // less.js basic tests
        foreach ($filesToTest as $fileToTest) {
            $expectedFile = $fixturesDir.'/less.js/css/'.str_replace('.less', '.css', basename($fileToTest));
            if (file_exists($expectedFile)) {
                $data[] = array(
                    $fileToTest,
                    $expectedFile,
                );
            }
        }

        // utf-8
        $data[] = array(
            $fixturesDir.'/utf8/less/utf8.less',
            $fixturesDir.'/utf8/css/utf8.css',
        );

        // bootstrap3
        $data[] = array(
            $fixturesDir.'/bootstrap3/less/bootstrap.less',
            $fixturesDir.'/bootstrap3/css/bootstrap.css',
        );

        // bootstrap2
        $data[] = array(
            $fixturesDir.'/bootstrap2/less/bootstrap.less',
            $fixturesDir.'/bootstrap2/css/bootstrap.css',
            // turn off strict math
            array(
                'strictUnits' => false,
                'strictMath' => false,
            ),
        );

        // variables via the API
        $data[] = array(
            $fixturesDir.'/php/less/variables.less',
            $fixturesDir.'/php/css/variables.css',
            array(),
            array(
                'a' => 'black',
                'fontdir' => '/fonts',
                'base' => '12px',
                'myurl' => '"http://example.com/image.jpg"',
            ),
        );

        // relative urls
        $data[] = array(
            $fixturesDir.'/relative_urls/less/simple.less',
            $fixturesDir.'/relative_urls/css/simple.css',
            array(
                'relativeUrls' => true,
            ),
        );

        $data[] = array(
            $fixturesDir.'/less.js/less/compression/compression.less',
            $fixturesDir.'/less.js/css/compression/compression.css',
            array('compress' => true),
        );

        $data[] = array(
            $fixturesDir.'/less.js/less/strict-units/strict-units.less',
            $fixturesDir.'/less.js/css/strict-units/strict-units.css',
            array(
                'strictMath' => true,
                'strictUnits' => true,
            ),
        );

        $data[] = array(
            $fixturesDir.'/less.js/less/legacy/legacy.less',
            $fixturesDir.'/less.js/css/legacy/legacy.css',
            array(
                'strictMath' => false,
                'strictUnits' => false,
            ),
        );

        $data[] = array(
            $fixturesDir.'/less.js/less/url-args/urls.less',
            $fixturesDir.'/less.js/css/url-args/urls.css',
            array(
                'urlArgs' => '424242',
            ),
        );

        $data[] = array(
            $fixturesDir.'/less.js/less/static-urls/urls.less',
            $fixturesDir.'/less.js/css/static-urls/urls.css',
            array(
                'strict_math' => true,
                'relative_urls' => false,
                'root_path' => 'folder (1)/',
            ),
        );

        $data[] = array(
            $fixturesDir.'/relative_urls/less/simple.less',
            $fixturesDir.'/relative_urls/css/simple.css',
            array(
                'relative_urls' => true,
            ),
        );

        $data[] = array(
            $fixturesDir.'/less.js/less/debug/linenumbers.less',
            $fixturesDir.'/less.js/css/debug/linenumbers-all.css',
            array('dumpLineNumbers' => DebugInfo::FORMAT_ALL),
            array(),
            array($this, 'normalizeDebugPaths'),
        );

        $data[] = array(
            $fixturesDir.'/less.js/less/debug/linenumbers.less',
            $fixturesDir.'/less.js/css/debug/linenumbers-comments.css',
            array('dumpLineNumbers' => DebugInfo::FORMAT_COMMENT),
            array(),
            array($this, 'normalizeDebugPaths'),
        );

        $data[] = array(
            $fixturesDir.'/less.js/less/debug/linenumbers.less',
            $fixturesDir.'/less.js/css/debug/linenumbers-mediaquery.css',
            array('dumpLineNumbers' => DebugInfo::FORMAT_MEDIA_QUERY),
            array(),
            array($this, 'normalizeDebugPaths'),
        );

        return $data;
    }

    public function normalizeDebugPaths($css)
    {
        $importPath = str_replace('\\', '/', dirname(__FILE__).'/_fixtures/less.js/less/debug/import/');
        $lessPath = str_replace('\\', '/', dirname(__FILE__).'/_fixtures/less.js/less/debug/');

        return str_replace(
            array(
                $importPath,
                DebugInfo::escapeFilenameForMediaQuery($importPath),
                $lessPath,
                DebugInfo::escapeFilenameForMediaQuery($lessPath),
            ),
            array(
                '{pathimport}',
                '{pathimportesc}',
                '{path}',
                '{pathesc}',
            ),
            $css
        );
    }

}
