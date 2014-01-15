<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Comment node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Comment
 */
class ILess_Test_Node_CommentTest extends ILess_Test_TestCase
{
    /**
     * @covers getType
     */
    public function testGetType()
    {
        $a = new ILess_Node_Comment('my comment');
        $this->assertEquals('Comment', $a->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCSS()
    {
        $env = new ILess_Environment();

        $a = new ILess_Node_Comment('my comment');
        $output = new ILess_Output();

        $a->generateCss($env, $output);
        $this->assertEquals($output->toString(), 'my comment');
    }

}
