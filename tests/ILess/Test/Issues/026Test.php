<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Issue #26 test
 *
 * @package ILess
 * @subpackage test
 */
class ILess_Test_Issues_026Test extends ILess_Test_TestCase
{
    public function testIssue()
    {
        $parser = new ILess_Parser();
        $parser->parseString(
'.test{
  background-color: darken("#ffffff",2%);
}');
        $this->setExpectedException('ILess_Exception_Function');
        $parser->getCSS();
    }

}
