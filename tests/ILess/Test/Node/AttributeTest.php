<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\AttributeNode;
use ILess\Output\StandardOutput;

/**
 * Attribute node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Attribute
 * @group node
 */
class Test_Node_AttributeTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new AttributeNode('foo', '=', 'bar');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new AttributeNode('foo', '=', 'bar');
        $this->assertEquals('Attribute', $d->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new Context();
        $output = new StandardOutput();

        $d = new AttributeNode('foo', '=', 'bar');
        $d->generateCss($env, $output);
        $this->assertEquals('[foo=bar]', $output->toString());
    }

}
