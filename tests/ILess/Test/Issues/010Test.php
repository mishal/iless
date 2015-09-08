<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Parser;

/**
 * Issue #10 test
 *
 * @package ILess
 * @subpackage test
 * @group issue
 */
class Test_Issues_010Test extends Test_TestCase
{
    public function testIssue()
    {
        $parser = new Parser();
        $parser->parseString("body {\n  color: fade(#ffcc00, 10%);\n}\n");

        $this->assertSame("body {\n  color: rgba(255, 204, 0, 0.1);\n}\n", $parser->getCSS());
    }

}
