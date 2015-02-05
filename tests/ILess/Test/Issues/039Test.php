<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Issue #39 test
 *
 * @package ILess
 * @subpackage test
 */
class ILess_Test_Issues_039Test extends ILess_Test_TestCase
{
    public function testIssue()
    {
        $parser = new ILess_Parser(array(
            'compress' => false
        ));

        $parser->parseString(
'
@screen-md-min: 460px;
@media (min-width: @screen-md-min) {
    body { color: red; }
}');

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
        $parser = new ILess_Parser(array(
            'compress' => true
        ));

        $parser->parseString(
            '
@screen-md-min: 460px;
@media (min-width: @screen-md-min) {
    body { color: red; }
}');

        $css = $parser->getCSS();
        $expected = '@media (min-width:460px){body{color:#f00}}';
        $this->assertEquals($expected, $css);
    }

}
