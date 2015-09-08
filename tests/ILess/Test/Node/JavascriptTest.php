<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\JavascriptNode;

/**
 * Javascript node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Javascript
 * @group node
 */
class Test_Node_JavascriptTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new JavascriptNode('"hello".toUpperCase() + \'!\'');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new JavascriptNode('"hello".toUpperCase() + \'!\'');
        $this->assertEquals('Javascript', $d->getType());
    }

    public function testCompile()
    {
        $env = new Context();
        $d = new JavascriptNode('"hello".toUpperCase() + \'!\'');
        $result = $d->compile($env);
        $this->assertInstanceOf('ILess\Node\JavascriptNode', $result);
    }

}
