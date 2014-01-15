<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Attribute node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Attribute
 */
class ILess_Test_Node_AttributeTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ILess_Node_Attribute('foo', '=', 'bar');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ILess_Node_Attribute('foo', '=', 'bar');
        $this->assertEquals('Attribute', $d->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new ILess_Environment();
        $output = new ILess_Output();

        $d = new ILess_Node_Attribute('foo', '=', 'bar');
        $d->generateCss($env, $output);
        $this->assertEquals('[foo=bar]', $output->toString());
    }

}
