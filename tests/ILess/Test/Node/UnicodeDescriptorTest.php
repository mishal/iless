<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\UnicodeDescriptorNode;
use ILess\Output\StandardOutput;

/**
 * UnicodeDescriptor node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_UnicodeDescriptor
 * @group node
 */
class Test_Node_UnicodeDescriptorTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new UnicodeDescriptorNode('foobar');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new UnicodeDescriptorNode('foobar');
        $this->assertEquals('UnicodeDescriptor', $d->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new Context();
        $output = new StandardOutput();

        $d = new UnicodeDescriptorNode('foobar');
        $d->generateCss($env, $output);
        $this->assertEquals('foobar', $output->toString());
    }

}
