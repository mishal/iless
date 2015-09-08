<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Parser;

/**
 * Issue #26 test
 *
 * @package ILess
 * @subpackage test
 * @group issue
 */
class Test_Issues_026Test extends Test_TestCase
{
    public function testIssue()
    {
        $parser = new Parser();
        $parser->parseString(
'.test{
  background-color: darken("#ffffff",2%);
}');
        $this->setExpectedException('ILess\Exception\FunctionException');
        $parser->getCSS();
    }

}
