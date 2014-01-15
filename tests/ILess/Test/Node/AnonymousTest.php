<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Anonymous node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Anonymous
 */
class ILess_Test_Node_AnonymousTest extends ILess_Test_TestCase
{
    /**
     * @covers getType
     */
    public function testGetType()
    {
        $a = new ILess_Node_Anonymous('foobar');
        $this->assertEquals('Anonymous', $a->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCSS()
    {
        $a = new ILess_Node_Anonymous('50');
        $output = new ILess_Output();
        $env = new ILess_Environment();

        $a->generateCss($env, $output);
        $this->assertEquals($output->toString(), '50');
    }

}
