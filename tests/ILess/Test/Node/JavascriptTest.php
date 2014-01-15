<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Javascript node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Javascript
 */
class ILess_Test_Node_JavascriptTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ILess_Node_Javascript('"hello".toUpperCase() + \'!\'');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ILess_Node_Javascript('"hello".toUpperCase() + \'!\'');
        $this->assertEquals('Javascript', $d->getType());
    }

    public function testCompile()
    {
        $env = new ILess_Environment();
        $d = new ILess_Node_Javascript('"hello".toUpperCase() + \'!\'');
        $result = $d->compile($env);
        $this->assertInstanceOf('ILess_Node_Javascript', $result);
    }

}
