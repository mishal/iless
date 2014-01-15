<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Issue #10 test
 *
 * @package ILess
 * @subpackage test
 */
class ILess_Test_Issues_010Test extends ILess_Test_TestCase
{
    public function testIssue()
    {
        $parser = new ILess_Parser();
        $parser->parseString("body {\n  color: fade(#ffcc00, 10%);\n}\n");

        $this->assertSame("body {\n  color: rgba(255, 204, 0, 0.1);\n}\n", $parser->getCSS());
    }

}
