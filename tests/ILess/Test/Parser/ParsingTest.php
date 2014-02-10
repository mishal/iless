<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__FILE__) . '/Core.php';

/**
 * Parsing tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Parser_Core
 */
class ILess_Test_Parser_ParsingTest extends ILess_Test_TestCase
{
    /**
     * Parser default options
     *
     * @var array
     */
    protected $parserDefaultOptions = array();

    protected function createParser($options = array())
    {
        $env = new ILess_Environment($options, new ILess_FunctionRegistry());
        $importer = new ILess_Importer($env, array(
            new ILess_Importer_FileSystem()
        ), new ILess_Cache_None());

        return new ILess_Test_Parser_Core($env, $importer);
    }

    /**
     * @dataProvider getCompilationData
     */
    public function testCompilation($lessFile, $cssFile, $options = array(), $variables = array(), $filter = null)
    {
        // default options
        if($options !== false && !count($options))
        {
            $options = $this->parserDefaultOptions;
        }

        $parser = $this->createParser($options);
        $parser->setVariables($variables);

        $parser->parseFile($lessFile);
        $preCompiled = file_get_contents($cssFile);
        $compiled = $parser->getCSS();
        if (is_callable($filter)) {
            $compiled = call_user_func($filter, $compiled);
        }

        // known diff, check if the diff is still ok
        if (is_readable($diffFile = str_replace('/less/', '/diff/', $lessFile . '.php'))) {
            $diff = include $diffFile;
            $actualDiff = array_diff(explode("\n", $compiled), explode("\n", $preCompiled));
            $this->assertEquals($diff, $actualDiff);
        } else {
            $this->assertEquals($preCompiled, $compiled);
        }
    }

    public function getCompilationData()
    {
        $fixturesDir = dirname(__FILE__) . '/_fixtures';

        $data = array_merge(
            array_map(null, glob($fixturesDir . '/simple/less/*.less'), glob($fixturesDir . '/simple/css/*.css')),
            array_map(null, glob($fixturesDir . '/less.js/less/*.less'), glob($fixturesDir . '/less.js/css/*.css'))
        );

        $variables = array(
            'a' => 'black',
            'fontdir' => '/fonts',
            'base' => '12px',
            'myurl' => '"http://example.com/image.jpg"'
        );
        $data[] = array($fixturesDir.'/php/less/variables.less' , $fixturesDir.'/php/css/variables.css', array(), $variables);

        $data[] = array($fixturesDir.'/bootstrap2/less/bootstrap.less' , $fixturesDir.'/bootstrap2/css/bootstrap.css');        
        $data[] = array($fixturesDir.'/bootstrap3/less/bootstrap.less' , $fixturesDir.'/bootstrap3/css/bootstrap.css', array(
            'strict_math' => true
        ));

        $data[] = array(
            $fixturesDir.'/less.js/less/debug/linenumbers.less',
            $fixturesDir.'/less.js/css/debug/linenumbers-all.css',
            array('dumpLineNumbers' => ILess_DebugInfo::FORMAT_ALL),
            array(),
            array($this, 'normalizeDebugPaths'),
        );
        $data[] = array(
            $fixturesDir.'/less.js/less/debug/linenumbers.less',
            $fixturesDir.'/less.js/css/debug/linenumbers-comments.css',
            array('dumpLineNumbers' => ILess_DebugInfo::FORMAT_COMMENT),
            array(),
            array($this, 'normalizeDebugPaths'),
        );
        $data[] = array(
            $fixturesDir.'/less.js/less/debug/linenumbers.less',
            $fixturesDir.'/less.js/css/debug/linenumbers-mediaquery.css',
            array('dumpLineNumbers' => ILess_DebugInfo::FORMAT_MEDIA_QUERY),
            array(),
            array($this, 'normalizeDebugPaths'),
        );
        $data[] = array(
            $fixturesDir.'/less.js/less/compression/compression.less',
            $fixturesDir.'/less.js/css/compression/compression.css',
            array('compress' => true)
        );
        $data[] = array(
            $fixturesDir.'/less.js/less/legacy/legacy.less',
            $fixturesDir.'/less.js/css/legacy/legacy.css'
        );
        $data[] = array(
            $fixturesDir.'/relative_urls/less/simple.less',
            $fixturesDir.'/relative_urls/css/simple.css',
            array(
                'relative_urls' => true
            )
        );
        $data[] = array(
            $fixturesDir.'/less.js/less/static-urls/urls.less',
            $fixturesDir.'/less.js/css/static-urls/urls.css',
            array(
                'strict_math' => true,
                'relative_urls' => false,
                'root_path' => 'folder (1)/'
            )
        );

        return $data;
    }

    public function normalizeDebugPaths($css)
    {
        $importPath = str_replace('\\', '/', dirname(__FILE__) . '/_fixtures/less.js/less/debug/import/');
        $lessPath = str_replace('\\', '/', dirname(__FILE__) . '/_fixtures/less.js/less/debug/');

        return str_replace(array(
            $importPath,
            ILess_DebugInfo::escapeFilenameForMediaQuery($importPath),
            $lessPath,
            ILess_DebugInfo::escapeFilenameForMediaQuery($lessPath)
        ), array(
            '{pathimport}',
            '{pathimportesc}',
            '{path}',
            '{pathesc}',
        ), $css);
    }

}
