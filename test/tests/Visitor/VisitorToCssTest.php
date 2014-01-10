<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Visitor_toCSS tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Visitor_toCSS
 */
class ILess_Visitor_toCSS_Test extends ILess_TestCase
{

  /**
   * @covers __constructor
   */
  public function testVisit()
  {
    $v = new ILess_Visitor_toCSS(new ILess_Environment());
    $this->assertTrue($v->isReplacing());
  }

}