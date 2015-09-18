<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Cache\CacheInterface;
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
    protected $parserDefaultOptions = [
        'strictUnits' => false,
        'relativeUrls' => true
    ];

    public static function setUpBeforeClass()
    {
        echo "\n ----- Testing parsing test with cache disabled ----- \n";
    }

    public static function tearDownAfterClass()
    {
        echo str_repeat('-', 40)."\n";
    }

    /**
     * @param array $options
     * @param CacheInterface $cache
     * @return Parser
     */
    protected function createParser($options = [], CacheInterface $cache = null)
    {
        $parser = new Parser($options, $cache);
        // test functions
        $parser->addFunctions(
            [
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
            ]
        );

        return $parser;
    }

    /**
     * @dataProvider getCompilationData
     */
    public function testCompilation($lessFile, $cssFile, $options = [], $variables = [], $filter = null)
    {
        // default options
        if ($options !== false && !count($options)) {
            $options = $this->parserDefaultOptions;
        }

        $parser = $this->createParser($options);
        $parser->setVariables($variables);

        // only for output info
        $dirBase = basename(dirname($lessFile));
        $baseName = basename($lessFile);
        if ($dirBase !== 'less') {
            $baseName = $dirBase.'/'.$baseName;
        }

        echo "Compilation test for $baseName\n";

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
            $this->assertEquals($actualDiff, []);
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
                $data[] = [
                    $fileToTest,
                    $expectedFile,
                ];
            }
        }

        // utf-8
        $data[] = [
            $fixturesDir.'/utf8/less/utf8.less',
            $fixturesDir.'/utf8/css/utf8.css',
        ];

        // bootstrap3
        $data[] = [
            $fixturesDir.'/bootstrap3/less/bootstrap.less',
            $fixturesDir.'/bootstrap3/css/bootstrap.css',
        ];

        // bootstrap2
        $data[] = [
            $fixturesDir.'/bootstrap2/less/bootstrap.less',
            $fixturesDir.'/bootstrap2/css/bootstrap.css',
            // turn off strict math
            [
                'strictUnits' => false,
                'strictMath' => false,
            ],
        ];

        // variables via the API
        $data[] = [
            $fixturesDir.'/php/less/variables.less',
            $fixturesDir.'/php/css/variables.css',
            [],
            [
                'a' => 'black',
                'fontdir' => '/fonts',
                'base' => '12px',
                'myurl' => '"http://example.com/image.jpg"',
            ],
        ];

        // relative urls
        $data[] = [
            $fixturesDir.'/relative_urls/less/simple.less',
            $fixturesDir.'/relative_urls/css/simple.css',
            [
                'relativeUrls' => true,
            ],
        ];

        $data[] = [
            $fixturesDir.'/less.js/less/compression/compression.less',
            $fixturesDir.'/less.js/css/compression/compression.css',
            ['compress' => true],
        ];

        $data[] = [
            $fixturesDir.'/less.js/less/strict-units/strict-units.less',
            $fixturesDir.'/less.js/css/strict-units/strict-units.css',
            [
                'strictMath' => true,
                'strictUnits' => true,
            ],
        ];

        $data[] = [
            $fixturesDir.'/less.js/less/legacy/legacy.less',
            $fixturesDir.'/less.js/css/legacy/legacy.css',
            [
                'strictMath' => false,
                'strictUnits' => false,
            ],
        ];

        $data[] = [
            $fixturesDir.'/less.js/less/url-args/urls.less',
            $fixturesDir.'/less.js/css/url-args/urls.css',
            [
                'urlArgs' => '424242',
            ],
        ];

        $data[] = [
            $fixturesDir.'/less.js/less/static-urls/urls.less',
            $fixturesDir.'/less.js/css/static-urls/urls.css',
            [
                'strict_math' => true,
                'relative_urls' => false,
                'root_path' => 'folder (1)/',
            ],
        ];

        $data[] = [
            $fixturesDir.'/relative_urls/less/simple.less',
            $fixturesDir.'/relative_urls/css/simple.css',
            [
                'relative_urls' => true,
            ],
        ];

        $data[] = [
            $fixturesDir.'/less.js/less/debug/linenumbers.less',
            $fixturesDir.'/less.js/css/debug/linenumbers-all.css',
            ['dumpLineNumbers' => DebugInfo::FORMAT_ALL],
            [],
            [$this, 'normalizeDebugPaths'],
        ];

        $data[] = [
            $fixturesDir.'/less.js/less/debug/linenumbers.less',
            $fixturesDir.'/less.js/css/debug/linenumbers-comments.css',
            ['dumpLineNumbers' => DebugInfo::FORMAT_COMMENT],
            [],
            [$this, 'normalizeDebugPaths'],
        ];

        $data[] = [
            $fixturesDir.'/less.js/less/debug/linenumbers.less',
            $fixturesDir.'/less.js/css/debug/linenumbers-mediaquery.css',
            ['dumpLineNumbers' => DebugInfo::FORMAT_MEDIA_QUERY],
            [],
            [$this, 'normalizeDebugPaths'],
        ];

        // bootswatch
        $data[] = [
            $fixturesDir.'/bootswatch/less/bootswatch.less',
            $fixturesDir.'/bootswatch/css/united/bootswatch.css',
            [],
            ['swatch' => 'united']
        ];

        return $data;
    }

    public function normalizeDebugPaths($css)
    {
        $importPath = str_replace('\\', '/', dirname(__FILE__).'/_fixtures/less.js/less/debug/import/');
        $lessPath = str_replace('\\', '/', dirname(__FILE__).'/_fixtures/less.js/less/debug/');

        return str_replace(
            [
                $importPath,
                DebugInfo::escapeFilenameForMediaQuery($importPath),
                $lessPath,
                DebugInfo::escapeFilenameForMediaQuery($lessPath),
            ],
            [
                '{pathimport}',
                '{pathimportesc}',
                '{path}',
                '{pathesc}',
            ],
            $css
        );
    }

}
