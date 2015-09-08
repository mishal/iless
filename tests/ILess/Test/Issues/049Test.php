<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Parser;

/**
 * Issue #49 test
 *
 * @package ILess
 * @subpackage test
 * @group issue
 */
class Test_Issues_049Test extends Test_TestCase
{

    public function testIssue()
    {
        $parser = new Parser();
        $parser->parseString('@property: color;
.widget {
  @{property}: #0ee;
  background-@{property}: #999;
}');

        $css = $parser->getCSS();
        $this->assertEquals('.widget {
  color: #0ee;
  background-color: #999;
}
', $css);
    }

}
