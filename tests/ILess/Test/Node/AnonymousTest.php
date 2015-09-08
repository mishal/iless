<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\AnonymousNode;
use ILess\Output\StandardOutput;

/**
 * Anonymous node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Anonymous
 * @group node
 */
class Test_Node_AnonymousTest extends Test_TestCase
{
    /**
     * @covers getType
     */
    public function testGetType()
    {
        $a = new AnonymousNode('foobar');
        $this->assertEquals('Anonymous', $a->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCSS()
    {
        $a = new AnonymousNode('50');
        $output = new StandardOutput();
        $env = new Context();

        $a->generateCss($env, $output);
        $this->assertEquals($output->toString(), '50');
    }

    public function testIsRulesetLike()
    {
        $a = new AnonymousNode('50');
        $this->assertFalse($a->isRulesetLike());

        $a = new AnonymousNode('50', 0, null, false, true);
        $this->assertTrue($a->isRulesetLike());
    }

}
