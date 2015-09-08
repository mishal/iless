<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\AssignmentNode;
use ILess\Output\StandardOutput;

/**
 * Assignment node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Assignment
 * @group node
 */
class Test_Node_AssignmentTest extends Test_TestCase
{
    /**
     * @covers getType
     */
    public function testGetType()
    {
        $a = new AssignmentNode('foo', 'bar');
        $this->assertEquals('Assignment', $a->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCSS()
    {
        $a = new AssignmentNode('a', '50');
        $output = new StandardOutput();
        $env = new Context();

        $a->generateCss($env, $output);
        $this->assertEquals($output->toString(), 'a=50');
    }

}
