<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Extend node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Extend
 */
class ILess_Test_Node_ExtendTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ILess_Node_Extend(new ILess_Node_Selector(array(new ILess_Node_Anonymous('foobar'))), 'all');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ILess_Node_Extend(new ILess_Node_Selector(array(new ILess_Node_Anonymous('foobar'))), 'all');
        $this->assertEquals('Extend', $d->getType());
    }

    /**
     * @covers compile
     */
    public function testCompile()
    {
        $env = new ILess_Environment();
        $d = new ILess_Node_Extend(new ILess_Node_Selector(array(new ILess_Node_Anonymous('foobar'))), 'all');
        $result = $d->compile($env);
        $this->assertInstanceOf('ILess_Node_Extend', $result);
    }

}
