<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\AlphaNode;
use ILess\Node\AnonymousNode;
use ILess\Output\StandardOutput;

/**
 * Alpha node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Alpha
 * @group node
 */
class Test_Node_AlphaTest extends Test_TestCase
{
    /**
     * @covers getType
     */
    public function testGetType()
    {
        $a = new AlphaNode(10);
        $this->assertEquals('Alpha', $a->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCSS()
    {
        $env = new Context();

        // with string
        $a = new AlphaNode('50');
        $output = new StandardOutput();
        $a->generateCss($env, $output);
        $this->assertEquals($output->toString(), 'alpha(opacity=50)');

        // now with another node
        $output = new StandardOutput();
        $a = new AlphaNode(new AnonymousNode('10'));
        $a->generateCss($env, $output);
        $this->assertEquals($output->toString(), 'alpha(opacity=10)');
    }

}
