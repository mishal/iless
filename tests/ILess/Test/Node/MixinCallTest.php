<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * MixinCall node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_MixinCall
 */
class ILess_Test_Node_MixinCallTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $mc = new ILess_Node_MixinCall(array(new ILess_Node_Element('', 'foobar')));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $mc = new ILess_Node_MixinCall(array(new ILess_Node_Element('', 'foobar')));
        $this->assertEquals('MixinCall', $mc->getType());
    }

    /**
     * @covers compile
     */
    public function testCompile()
    {
        $this->setExpectedException('ILess_Exception_Compiler');

        $env = new ILess_Environment();
        $mc = new ILess_Node_MixinCall(array(new ILess_Node_Element('', 'foobar')));

        // throws an exception
        $result = $mc->compile($env);
    }

}
