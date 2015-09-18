<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ILess\CLI;

class Test_CLI extends CLI
{
    public function parseArguments($args)
    {
        return parent::parseArguments($args);
    }

    public function isSilent()
    {
        return parent::isSilent();
    }
}

/**
 * ILess\CLI
 *
 * @package ILess
 * @subpackage test
 * @covers CLI
 */
class CLITest extends Test_TestCase
{
    /**
     * @covers isValid
     */
    public function testIsValid()
    {
        $cli = new CLI([]);
        $this->assertEquals(false, $cli->isValid());
    }

    /**
     * @covers getScriptName
     */
    public function testGetScriptName()
    {
        $cli = new CLI([
            'foobar.php', 'arg1', 'arg2', 'arg3'
        ]);
        $this->assertEquals($cli->getScriptName(), 'foobar.php');
    }

    /**
     * @covers       parseArguments
     * @dataProvider getDataForParseArgumentsTest
     */
    public function testParseArguments($arguments, $expected)
    {
        $cli = new Test_CLI([
            'foobar.php', 'arg1', 'arg2', 'arg3'
        ]);
        $this->assertSame($expected, $cli->parseArguments($arguments));
    }

    public function getDataForParseArgumentsTest()
    {
        return [
            [
                // to test:
                ['a.less', 'b.css', '--source-map', '--compress', '-x'],
                // expected:
                ['arguments' => ['a.less', 'b.css'], 'flags' => ['x'], 'options' => ['source-map' => true, 'compress' => true]]
            ],
            [
                // to test:
                ['--source-map=foobar.map', '--compress=false', '-x', 'a.less', 'b.css'],
                // expected:
                ['arguments' => ['a.less', 'b.css'], 'flags' => ['x'], 'options' => ['source-map' => 'foobar.map', 'compress' => false]]
            ],
            [
                // to test:
                ['-', '-x'], // read from stdin
                // expected:
                ['arguments' => ['-'], 'flags' => ['x'], 'options' => []]
            ]
        ];
    }

    /**
     * @covers       isSilent
     * @dataProvider getDataForIsSilentTest
     */
    public function testIsSilent($arguments, $expected)
    {
        $cli = new Test_CLI($arguments);
        $this->assertSame($expected, $cli->isSilent());
    }

    public function getDataForIsSilentTest()
    {
        return [
            [
                // to test:
                ['foobar.php', 'a.less', 'b.css', '--source-map', '--compress', '-x'],
                // expected:
                false
            ],
            [
                // to test:
                // -s flag present
                ['foobar.php', '--source-map=foobar.map', '--compress=false', '-s', 'a.less', 'b.css'],
                // expected:
                true
            ],
            [
                // to test:
                // --compress option present
                ['foobar.php', '--compress=true', '--silent'],
                // expected:
                true
            ]
        ];
    }

    /**
     * @covers getUsage
     */
    public function testGetUsage()
    {
        $cli = new Test_CLI(['foobar.php']);
        $this->assertContains('usage: foobar.php', $cli->getUsage());
    }

}
