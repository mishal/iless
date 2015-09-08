<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\AnonymousNode;
use ILess\Node\ElementNode;
use ILess\Output\StandardOutput;

/**
 * Element node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Element
 * @group node
 */
class Test_Node_ElementTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ElementNode('>', new AnonymousNode('bar'));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ElementNode('>', new AnonymousNode('bar'));
        $this->assertEquals('Element', $d->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new Context();
        $output = new StandardOutput();
        $d = new ElementNode('>', new AnonymousNode('bar'));
        $d->generateCss($env, $output);
        $this->assertEquals(' > bar', $output->toString());
    }

}
