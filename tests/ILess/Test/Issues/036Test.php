<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Issue #36 test
 *
 * @package ILess
 * @subpackage test
 */
class ILess_Test_Issues_036Test extends ILess_Test_TestCase
{
    public function testIssue()
    {
        $parser = new ILess_Parser(array(
            'compress' => false
        ));

        $parser->parseString(
'
@grid-gutter-width: 10px;
.elem {
  width: calc(~\'100% + @{grid-gutter-width}\');
}');

        $css = $parser->getCSS();
        $expected =
'.elem {
  width: calc(100% + 10px);
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
@grid-gutter-width: 10px;
.elem {
  width: calc(~\'100% + @{grid-gutter-width}\');
}');

        $css = $parser->getCSS();
        $expected = '.elem{width:calc(100% + 10px)}';
        $this->assertEquals($expected, $css);
    }

}
