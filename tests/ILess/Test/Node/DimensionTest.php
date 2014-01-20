<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Dimension node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Dimension
 */
class ILess_Test_Node_DimensionTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ILess_Node_Dimension('15');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ILess_Node_Dimension('15');
        $this->assertEquals('Dimension', $d->getType());
    }

    /**
     * @covers toColor
     */
    public function testToColor()
    {
        $c = new ILess_Node_Dimension('>', new ILess_Node_DimensionUnit());
        $color = $c->toColor();
        // FIXME: Should the color verify that this is not valid color?!
        $this->assertInstanceOf('ILess_Node_Color', $color);
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new ILess_Environment();
        $env->strictUnits = true;

        $output = new ILess_Output();
        $d = new ILess_Node_Dimension('15', 'px');

        $d->generateCss($env, $output);

        $this->assertEquals('15px', $output->toString());
    }

}
