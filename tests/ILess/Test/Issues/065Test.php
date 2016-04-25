<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ILess\Parser;

/**
 * Issue #65 test.
 */
class ILess_Test_Issues_065Test extends Test_TestCase
{
    public function testIssuesWithMathOffAsDefault()
    {
        $parser = new Parser();

        $parser->parseString(
            'body { total-width: (1 * 6em * 12) + (2em * 12); }'
        );

        $css = $parser->getCSS();

        $expected = 'body {
  total-width: 96em;
}
';

        $this->assertEquals($expected, $css);
    }

    public function testIssue()
    {
        $parser = new Parser([
            'strictMath' => true,
        ]);

        $parser->parseString(
            'body { total-width: (1 * 6em * 12) + (2em * 12); }'
        );

        $css = $parser->getCSS();

        $expected = 'body {
  total-width: (1 * 6em * 12) + (2em * 12);
}
';
        $this->assertEquals($expected, $css);
    }
}
