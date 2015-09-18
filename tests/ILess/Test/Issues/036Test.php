<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Parser;

/**
 * Issue #36 test
 *
 * @package ILess
 * @subpackage test
 * @group issue
 */
class Test_Issues_036Test extends Test_TestCase
{
    public function testIssue()
    {
        $parser = new Parser([
            'compress' => false
        ]);

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
        $parser = new Parser([
            'compress' => true
        ]);

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
