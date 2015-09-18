<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\FileInfo;
use ILess\ImportedFile;
use ILess\Importer\ArrayImporter;
use ILess\Parser;

/**
 * Array importer tests
 *
 * @group importer
 */
class Test_Importer_ArrayTest extends PHPUnit_Framework_TestCase
{
    public function testSetFile()
    {
        $importer = new ArrayImporter([]);
        $this->assertFalse($importer->import('foo.less', new FileInfo()));

        $fluent = $importer->setFile('foo.less', 'text');
        $this->assertSame($importer, $fluent);
        $this->assertEquals(new ImportedFile('foo.less', 'text', -1), $importer->import('foo.less', new FileInfo()));
    }

    public function testGetLastModified()
    {
        $time = time();
        $importer = new ArrayImporter(['foo.less' => 'text'], ['foo.less' => $time]);
        $this->assertEquals($time, $importer->getLastModified('foo.less', new FileInfo()));
    }

    public function testImport()
    {
        $importer = new ArrayImporter([
            'vendor/foo.less' => '@import "bar";',
            'vendor/bar.less' => '@import "foobar"; @import "../parent"; a { color: blue; }',
            'foobar.less' => 'b { color: red; }',
            'parent.less' => '/* comment */',
        ]);

        $parser = new Parser([], null, [$importer]);
        $parser->parseString('@import "vendor/foo";');

        $this->assertEquals("b {\n  color: red;\n}\n/* comment */\na {\n  color: blue;\n}\n", $parser->getCSS());
    }
}
