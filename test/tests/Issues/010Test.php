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
class ILess_Issue_10_Test extends ILess_TestCase {

  public function testIssue()
  {
    $parser = new ILess_Parser();
    $parser->parseString(
'body {
    color: fade(#ffcc00, 10%);
}
');

    $this->assertSame(
'body {
  color: rgba(255, 204, 0, 0.1);
}
', $parser->getCSS());
  }

}