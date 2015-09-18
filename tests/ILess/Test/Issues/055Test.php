<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ILess\Parser;

/**
 * Issue #52 test
 *
 * @package ILess
 * @subpackage test
 */
class ILess_Test_Issues_055Test extends Test_TestCase
{
    public function testIssue()
    {
        $parser = new Parser();

        $parser->parseString('//this is a comment string');

        $css = $parser->getCSS();

        $this->assertEquals('', $css);
    }
}
