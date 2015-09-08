<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\DimensionNode;
use ILess\Node\UnitNode;
use ILess\Output\StandardOutput;

/**
 * Dimension node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Dimension
 * @group node
 */
class Test_Node_DimensionTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new DimensionNode('15');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new DimensionNode('15');
        $this->assertEquals('Dimension', $d->getType());
    }

    /**
     * @covers toColor
     */
    public function testToColor()
    {
        $c = new DimensionNode('>', new UnitNode());
        $color = $c->toColor();

        $this->assertInstanceOf('ILess\Node\ColorNode', $color);
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCss()
    {
        $env = new Context();
        $env->strictUnits = true;

        $output = new StandardOutput();
        $d = new DimensionNode('15', 'px');

        $d->generateCss($env, $output);

        $this->assertEquals('15px', $output->toString());
    }

}
