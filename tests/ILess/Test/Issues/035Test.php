<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Issue #35 test
 *
 * @package ILess
 * @subpackage test
 */
class ILess_Test_Issues_035Test extends ILess_Test_TestCase
{
    public function testIssue()
    {
        $parser = new ILess_Parser();

        $parser->setVariables(array('mycolor' => 'transparent'));

        $parser->parseString(
'.test{
  background-color: @mycolor;
}');

        $css = $parser->getCSS();
        $expected =
'.test {
  background-color: transparent;
}
';
        $this->assertEquals($expected, $css);
    }

}
