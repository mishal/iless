<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Issue #52 test
 *
 * @package ILess
 * @subpackage test
 */
class ILess_Test_Issues_052Test extends ILess_Test_TestCase
{
    public function testIssue()
    {
        $parser = new ILess_Parser(array(
            'compress' => false
        ));

        $parser->parseString('
#mxtest {
  color2: @b;
  alpha: alpha(@a);
  color: darken(@a, 20);
  background: -moz-linear-gradient(top, @a 0%, darken(@a, 20) 100%);
}');

        $parser->setVariables(array('a' => 'rgb(46, 120, 176)', 'b' => 'rgba(0,1,2,0.3)'));

        $css = $parser->getCSS();

        $this->assertContains('alpha: 1;', $css);
        $this->assertContains('color2: rgba(0, 1, 2, 0.3);', $css);
    }

}
