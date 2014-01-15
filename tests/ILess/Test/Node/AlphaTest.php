<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Alpha node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Alpha
 */
class ILess_Test_Node_AlphaTest extends ILess_Test_TestCase
{
    /**
     * @covers getType
     */
    public function testGetType()
    {
        $a = new ILess_Node_Alpha(10);
        $this->assertEquals('Alpha', $a->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCSS()
    {
        $env = new ILess_Environment();

        // with string
        $a = new ILess_Node_Alpha('50');
        $output = new ILess_Output();
        $a->generateCss($env, $output);
        $this->assertEquals($output->toString(), 'alpha(opacity=50)');

        // now with another node
        $output = new ILess_Output();
        $a = new ILess_Node_Alpha(new ILess_Node_Anonymous('10'));
        $a->generateCss($env, $output);
        $this->assertEquals($output->toString(), 'alpha(opacity=10)');
    }

}
