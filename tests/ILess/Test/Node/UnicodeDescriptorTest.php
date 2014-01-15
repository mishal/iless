<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * UnicodeDescriptor node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_UnicodeDescriptor
 */
class ILess_Test_Node_UnicodeDescriptorTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ILess_Node_UnicodeDescriptor('foobar');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ILess_Node_UnicodeDescriptor('foobar');
        $this->assertEquals('UnicodeDescriptor', $d->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new ILess_Environment();
        $output = new ILess_Output();

        $d = new ILess_Node_UnicodeDescriptor('foobar');
        $d->generateCss($env, $output);
        $this->assertEquals('foobar', $output->toString());
    }

}
