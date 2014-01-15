<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class ILess_Test_CLI extends ILess_CLI
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
 * CLI
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_CLI
 */
class ILess_CLITest extends ILess_Test_TestCase
{
    /**
     * @covers isValid
     */
    public function testIsValid()
    {
        $cli = new ILess_CLI(array());
        $this->assertEquals(false, $cli->isValid());
    }

    /**
     * @covers getScriptName
     */
    public function testGetScriptName()
    {
        $cli = new ILess_CLI(array(
            'foobar.php', 'arg1', 'arg2', 'arg3'
        ));
        $this->assertEquals($cli->getScriptName(), 'foobar.php');
    }

    /**
     * @covers       parseArguments
     * @dataProvider getDataForParseArgumentsTest
     */
    public function testParseArguments($arguments, $expected)
    {
        $cli = new ILess_Test_CLI(array(
            'foobar.php', 'arg1', 'arg2', 'arg3'
        ));
        $this->assertSame($expected, $cli->parseArguments($arguments));
    }

    public function getDataForParseArgumentsTest()
    {
        return array(
            array(
                // to test:
                array('a.less', 'b.css', '--source-map', '--compress', '-x'),
                // expected:
                array('arguments' => array('a.less', 'b.css'), 'flags' => array('x'), 'options' => array('source-map' => true, 'compress' => true))
            ),
            array(
                // to test:
                array('--source-map=foobar.map', '--compress=false', '-x', 'a.less', 'b.css'),
                // expected:
                array('arguments' => array('a.less', 'b.css'), 'flags' => array('x'), 'options' => array('source-map' => 'foobar.map', 'compress' => false))
            ),
            array(
                // to test:
                array('-', '-x'), // read from stdin
                // expected:
                array('arguments' => array('-'), 'flags' => array('x'), 'options' => array())
            )
        );
    }

    /**
     * @covers       isSilent
     * @dataProvider getDataForIsSilentTest
     */
    public function testIsSilent($arguments, $expected)
    {
        $cli = new ILess_Test_CLI($arguments);
        $this->assertSame($expected, $cli->isSilent());
    }

    public function getDataForIsSilentTest()
    {
        return array(
            array(
                // to test:
                array('foobar.php', 'a.less', 'b.css', '--source-map', '--compress', '-x'),
                // expected:
                false
            ),
            array(
                // to test:
                // -s flag present
                array('foobar.php', '--source-map=foobar.map', '--compress=false', '-s', 'a.less', 'b.css'),
                // expected:
                true
            ),
            array(
                // to test:
                // --compress option present
                array('foobar.php', '--compress=true', '--silent'),
                // expected:
                true
            )
        );
    }

    /**
     * @covers getUsage
     */
    public function testGetUsage()
    {
        $cli = new ILess_Test_CLI(array('foobar.php'));
        $this->assertContains('usage: foobar.php', $cli->getUsage());
    }

}
