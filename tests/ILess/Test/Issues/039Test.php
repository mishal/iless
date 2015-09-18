<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Parser;

/**
 * Issue #39 test
 *
 * @package ILess
 * @subpackage test
 * @group issue
 */
class Test_Issues_039Test extends Test_TestCase
{
    public function testIssue()
    {
        $parser = new Parser([
            'compress' => false
        ]);

        $parser->parseString(
            '
@screen-md-min: 460px;
@media (min-width: @screen-md-min) {
    body { color: red; }
}
');

        $css = $parser->getCSS();
        $expected =
            '@media (min-width: 460px) {
  body {
    color: red;
  }
}
';
        $this->assertEquals($expected, $css);
    }

    public function testIssueWithCompression()
    {
        $parser = new Parser([
            'compress' => true
        ]);

        $parser->parseString(
            '
@screen-md-min: 460px;
@media (min-width: @screen-md-min) {
    body { color: red; }
}');

        $css = $parser->getCSS();
        $expected = '@media (min-width:460px){body{color:red}}';
        $this->assertEquals($expected, $css);
    }

    public function testEmptyMediaDeclaration()
    {
        $parser = new Parser([
            'compress' => false
        ]);

        $parser->parseString('@media (min-width: 640px) {}');

        $css = $parser->getCSS();
        $expected = '';

        $this->assertEquals($expected, $css);
    }

}
