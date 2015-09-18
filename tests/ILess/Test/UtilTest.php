<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Node\AnonymousNode;
use ILess\Util;

/**
 * Utility tests
 *
 * @package ILess
 * @subpackage test
 * @group util
 */
class Test_UtilTest extends Test_TestCase
{
    /**
     * @covers       normalizePath
     * @dataProvider getDataForNormalizePathTest
     */
    public function testNormalizePath($path, $expected)
    {
        $this->assertEquals(Util::normalizePath($path), $expected);
    }

    public function getDataForNormalizePathTest()
    {
        return [
            ['foo', 'foo'],
            ['http://foobar.com', 'http://foobar.com'],
            [__FILE__, str_replace('\\', '/', __FILE__)],
            ['/a/d/tmp/../foo/test.jpg', '/a/d/foo/test.jpg']
        ];
    }

    /**
     * @covers       compareNodes
     * @dataProvider getDataForCompareNodes
     */
    public function testCompareNodes($expected, $a, $b)
    {
        $this->assertSame($expected, Util::compareNodes($a, $b));
    }

    public function getDataForCompareNodes()
    {
        return [
            [
                null, new AnonymousNode('a'), new AnonymousNode('b')
            ]
        ];
    }

}
