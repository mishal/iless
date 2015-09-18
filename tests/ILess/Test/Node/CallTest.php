<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\AnonymousNode;
use ILess\Node\CallNode;
use ILess\Output\StandardOutput;

/**
 * Call node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Call
 * @group node
 */
class Test_Node_CallTest extends Test_TestCase
{
    /**
     * @covers getType
     */
    public function testGetType()
    {
        $a = new CallNode('foo', [], 0);
        $this->assertEquals('Call', $a->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCSS()
    {
        $env = new Context();

        $a = new CallNode('foo', [], 0);
        $output = new StandardOutput();

        $a->generateCss($env, $output);
        $this->assertEquals($output->toString(), 'foo()');

        // a bit complicated
        $a = new CallNode('foo', [
            new AnonymousNode('arg1'),
            new AnonymousNode('arg2'),
        ], 0);

        $output = new StandardOutput();
        $a->generateCss($env, $output);
        $this->assertEquals($output->toString(), 'foo(arg1, arg2)');
    }

}
