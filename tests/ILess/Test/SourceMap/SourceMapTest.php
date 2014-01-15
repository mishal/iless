<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Parser tests with source map generation enabled
 *
 * @package ILess
 * @subpackage test
 */
class ILess_Test_SourceMap_SourceMapTest extends ILess_Test_TestCase
{
    public function setUp()
    {
        $this->sourceMap = sys_get_temp_dir() . '/media.css.map';

        $this->parser = new ILess_Parser(array(
            'sourceMap' => true,
            'sourceMapOptions' => array(
                'base_path' => dirname(__FILE__) . '/_fixtures',
                'filename' => 'simple.css',
                'write_to' => $this->sourceMap
            ),
        ));
        $this->fixturesDir = dirname(__FILE__) . '/_fixtures';
    }

    public function tearDown()
    {
        if (isset($this->sourceMap)) {
            // @unlink($this->sourceMap);
        }
    }

    public function testWriteMap()
    {
        $this->parser->parseFile($this->fixturesDir . '/simple.less');
        // need to call to get the map generated
        $css = $this->parser->getCss();
        // the generated source map
        $this->assertEquals(file_get_contents($this->fixturesDir . '/simple.css.map'), file_get_contents($this->sourceMap));
        // the generated css
        $this->assertEquals(file_get_contents($this->fixturesDir . '/simple.css'), $css);
    }

    public function testInlineMap()
    {
        $this->parser = new ILess_Parser(array(
            'sourceMap' => true,
            'sourceMapOptions' => array(
                'base_path' => dirname(__FILE__) . '/_fixtures',
                'filename' => 'media.css',
            ),
        ));

        $this->parser->parseFile($this->fixturesDir . '/media.less');
        $this->assertEquals(file_get_contents($this->fixturesDir . '/media-map-inline.css'), $this->parser->getCss());
    }

    public function testMapWithContents()
    {
        $this->sourceMap = sys_get_temp_dir() . '/media-content.css.map';

        $this->parser = new ILess_Parser(array(
            'sourceMap' => true,
            'sourceMapOptions' => array(
                'base_path' => dirname(__FILE__) . '/_fixtures',
                'filename' => 'media.css',
                'source_contents' => true,
                'write_to' => $this->sourceMap
            ),
        ));

        $this->parser->parseFile($this->fixturesDir . '/media.less');

        // the generated css
        $this->assertEquals(file_get_contents($this->fixturesDir . '/media-content.css'), $this->parser->getCss());

        // the generated source map
        $this->assertEquals(file_get_contents($this->fixturesDir . '/media-content.css.map'), file_get_contents($this->sourceMap));
    }

    public function testMapFromString()
    {
        $this->sourceMap = sys_get_temp_dir() . '/media-content.css.map';

        $this->parser = new ILess_Parser(array(
            'sourceMap' => true,
            'sourceMapOptions' => array(
                'base_path' => dirname(__FILE__) . '/_fixtures',
                'filename' => 'media.css',
                'source_contents' => true,
                'write_to' => $this->sourceMap
            ),
        ));

        $this->parser->parseString(file_get_contents($this->fixturesDir . '/media.less'), 'media.less');

        // the generated css
        $this->assertEquals(file_get_contents($this->fixturesDir . '/media-content.css'), $this->parser->getCss());

        // the generated source map
        $this->assertEquals(file_get_contents($this->fixturesDir . '/media-content.css.map'), file_get_contents($this->sourceMap));
    }

}
