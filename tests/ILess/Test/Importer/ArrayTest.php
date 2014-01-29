<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class ILess_Test_Importer_ArrayTest extends PHPUnit_Framework_TestCase
{
    public function testSetFile()
    {
        $importer = new ILess_Importer_Array(array());
        $this->assertFalse($importer->import('foo.less', new ILess_FileInfo()));

        $fluent = $importer->setFile('foo.less', 'text');
        $this->assertSame($importer, $fluent);
        $this->assertEquals(new ILess_ImportedFile('foo.less', 'text', -1), $importer->import('foo.less', new ILess_FileInfo()));
    }

    public function testGetLastModified()
    {
        $time = time();
        $importer = new ILess_Importer_Array(array('foo.less' => 'text'), array('foo.less' => $time));
        $this->assertEquals($time, $importer->getLastModified('foo.less', new ILess_FileInfo()));
    }

    public function testImport()
    {
        $importer = new ILess_Importer_Array(array(
            'vendor/foo.less' => '@import "bar";',
            'vendor/bar.less' => '@import "foobar"; @import "../parent"; a { color: blue; }',
            'foobar.less' => 'b { color: red; }',
            'parent.less' => '/* comment */',
        ));

        $parser = new ILess_Parser(array(), null, array($importer));
        $parser->parseString('@import "vendor/foo";');
        $this->assertEquals("b {\n  color: red;\n}\n/* comment */\na {\n  color: blue;\n}\n", $parser->getCSS());
    }
}
