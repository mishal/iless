<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Math;
use ILess\Node\ColorNode;
use ILess\Output\StandardOutput;

/**
 * Color node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Color_Call
 * @group node
 */
class Test_Node_ColorTest extends Test_TestCase
{
    /**
     * @covers getType
     */
    public function testGetType()
    {
        $a = new ColorNode('#ffffff');
        $this->assertEquals('Color', $a->getType());
    }

    /**
     * @covers       __constructor
     * @dataProvider getDataForConstructorTest
     */
    public function testConstructor($clr, $expected, $alpha)
    {
        $color = new ColorNode($clr);
        $this->assertEquals($color->getRgb(), $expected);
        $this->assertInstanceOf('ILess\Node\DimensionNode', $color->getAlpha());
    }

    public function getDataForConstructorTest()
    {
        return [
            // color, array of rgb channels, alpha
            ['#ffffff', [255, 255, 255], 1],
            ['#000000', [0, 0, 0], 1],
            ['#ddd', [221, 221, 221], 1],
        ];
    }

    /**
     * @covers getRed
     */
    public function testGetRed()
    {
        $color = new ColorNode('#cc00ff');
        $this->assertInstanceOf('ILess\Node\DimensionNode', $color->getRed());
        $this->assertEquals('204', (string)$color->getRed());
    }

    /**
     * @covers getGreen
     */
    public function testGetGreen()
    {
        $color = new ColorNode('#00ddff');
        $this->assertInstanceOf('ILess\Node\DimensionNode', $color->getGreen());
        $this->assertEquals('221', (string)$color->getGreen());
    }

    /**
     * @covers getBlue
     */
    public function testGetBlue()
    {
        $color = new ColorNode('#ff00cc');
        $this->assertInstanceOf('ILess\Node\DimensionNode', $color->getBlue());
        $this->assertEquals('204', (string)$color->getBlue());
    }

    /**
     * @covers getBlue
     */
    public function testGetAlpha()
    {
        $color = new ColorNode('#ffffff', '0.5');
        $this->assertInstanceOf('ILess\Node\DimensionNode', $color->getAlpha());
        $this->assertEquals('0.5', (string)$color->getAlpha());
    }

    /**
     * @covers getSaturation
     */
    public function testGetSaturation()
    {
        $color = new ColorNode('#BE3AF2');
        $saturation = $color->getSaturation();
        $this->assertInstanceOf('ILess\Node\DimensionNode', $saturation);

        $this->assertEquals('88%', (string)$color->getSaturation());
    }

    /**
     * @covers getHue
     */
    public function testGetLightness()
    {
        $color = new ColorNode('#BE3AF2', 0.5);
        $this->assertInstanceOf('ILess\Node\DimensionNode', $color->getLightness());
        $this->assertEquals('59%', (string)$color->getLightness());
    }

    /**
     * @covers getHue
     */
    public function testGetHue()
    {
        $color = new ColorNode('#BE3AF2', 0.5);
        $this->assertInstanceOf('ILess\Node\DimensionNode', $color->getHue());
        $this->assertEquals('283', (string)$color->getHue());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCSS()
    {
        $env = new Context();
        $color = new ColorNode('#ffffff');
        $output = new StandardOutput();
        $color->generateCss($env, $output);
        $this->assertEquals($output->toString(), '#ffffff');
    }

    /**
     * @covers operate
     */
    public function testOperate()
    {
        $env = new Context();

        $color = new ColorNode('#ffffff');
        $other = new ColorNode('#ff0000');

        $result = $color->operate($env, '+', $other);
        // new color is returned
        $this->assertInstanceOf('ILess\Node\ColorNode', $result);
        $this->assertEquals($result->getRGB(), [
            255, 255, 255
        ]);
    }

}
