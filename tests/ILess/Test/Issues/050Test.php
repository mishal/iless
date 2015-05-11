<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Issue #50 test
 *
 * @package ILess
 * @subpackage test
 */
class ILess_Test_Issues_050Test extends ILess_Test_TestCase
{
    public function testIssue()
    {
        $parser = new ILess_Parser(array(
            'compress' => false
        ));

        $parser->parseString('@swatch: foobar;
@import "../../../bootstrap3/less/@{swatch}/variables.less";
');

        $this->setExpectedException('ILess_Exception_Import', '/bootstrap3/less/foobar/variables.less');

        $css = $parser->getCSS();
    }

    public function testIssueWithApiVariables()
    {
        $parser = new ILess_Parser(array(
            'compress' => false
        ));

        $parser->parseString('
@import "../../../bootstrap3/less/@{swatch}/variables.less";
');
        $parser->setVariables(array(
            'swatch' => 'foobar'
        ));

        $this->setExpectedException('ILess_Exception_Import', '/bootstrap3/less/foobar/variables.less');

        $css = $parser->getCSS();
    }

}
