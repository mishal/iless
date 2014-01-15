<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Color node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Color_Call
 */
class ILess_Test_Node_ColorTest extends ILess_Test_TestCase
{

    public function setUp()
    {
        // we need the precision setup
        ILess_Math::setup(16);
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $a = new ILess_Node_Color('#ffffff');
        $this->assertEquals('Color', $a->getType());
    }

    /**
     * @covers       __constructor
     * @dataProvider getDataForConstructorTest
     */
    public function testConstructor($clr, $expected, $alpha)
    {
        $color = new ILess_Node_Color($clr);
        $this->assertEquals($color->getRgb(), $expected);
        $this->assertInstanceOf('ILess_Node_Dimension', $color->getAlpha());
    }

    public function getDataForConstructorTest()
    {
        return array(
            // color, array of rgb channels, alpha
            array('#ffffff', array(255, 255, 255), 1),
            array('#000000', array(0, 0, 0), 1),
            array('#ddd', array(221, 221, 221), 1),
        );
    }

    /**
     * @covers getRed
     */
    public function testGetRed()
    {
        $color = new ILess_Node_Color('#cc00ff');
        $this->assertInstanceOf('ILess_Node_Dimension', $color->getRed());
        $this->assertEquals('204', (string)$color->getRed());
    }

    /**
     * @covers getGreen
     */
    public function testGetGreen()
    {
        $color = new ILess_Node_Color('#00ddff');
        $this->assertInstanceOf('ILess_Node_Dimension', $color->getGreen());
        $this->assertEquals('221', (string)$color->getGreen());
    }

    /**
     * @covers getBlue
     */
    public function testGetBlue()
    {
        $color = new ILess_Node_Color('#ff00cc');
        $this->assertInstanceOf('ILess_Node_Dimension', $color->getBlue());
        $this->assertEquals('204', (string)$color->getBlue());
    }

    /**
     * @covers getBlue
     */
    public function testGetAlpha()
    {
        $color = new ILess_Node_Color('#ffffff', 0.5);
        $this->assertInstanceOf('ILess_Node_Dimension', $color->getAlpha());
        $this->assertEquals('0.5', (string)$color->getAlpha());
    }

    /**
     * @covers getSaturation
     */
    public function testGetSaturation()
    {
        $color = new ILess_Node_Color('#BE3AF2');
        $saturation = $color->getSaturation();
        $this->assertInstanceOf('ILess_Node_Dimension', $saturation);

        $this->assertEquals('88%', (string)$color->getSaturation());
    }

    /**
     * @covers getHue
     */
    public function testGetLightness()
    {
        $color = new ILess_Node_Color('#BE3AF2', 0.5);
        $this->assertInstanceOf('ILess_Node_Dimension', $color->getLightness());
        $this->assertEquals('59%', (string)$color->getLightness());
    }

    /**
     * @covers getHue
     */
    public function testGetHue()
    {
        $color = new ILess_Node_Color('#BE3AF2', 0.5);
        $this->assertInstanceOf('ILess_Node_Dimension', $color->getHue());
        $this->assertEquals('283', (string)$color->getHue());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCSS()
    {
        $env = new ILess_Environment();
        $color = new ILess_Node_Color('#ffffff');
        $output = new ILess_Output();
        $color->generateCss($env, $output);
        $this->assertEquals($output->toString(), '#ffffff');
    }

    /**
     * @covers operate
     */
    public function testOperate()
    {
        $env = new ILess_Environment();

        $color = new ILess_Node_Color('#ffffff');
        $other = new ILess_Node_Color('#ff0000');

        $result = $color->operate($env, '+', $other);
        // new color is returned
        $this->assertInstanceOf('ILess_Node_Color', $result);
        $this->assertEquals($result->getRGB(), array(
            255, 255, 255
        ));
    }

}
