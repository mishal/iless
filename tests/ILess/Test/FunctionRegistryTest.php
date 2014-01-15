<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Function tests
 *
 * @package ILess
 * @subpackage test
 */
class ILess_Test_FunctionRegistryTest extends ILess_Test_TestCase
{
    protected $registry;

    public function setUp()
    {
        $this->registry = new ILess_FunctionRegistry(array(), new ILess_Environment());
        ILess_Math::setup();
    }

    /**
     * @covers       hsl
     * @dataProvider getDataForHslTest
     */
    public function testHsl($input, $expected, $expectedHex)
    {
        $color = $this->registry->hsl($input[0], $input[1], $input[2]);

        $rgb = $color->getRGB();
        $this->assertEquals($expected, $rgb);
        $this->assertEquals($expectedHex, $color->getColor()->toString());
    }

    public function getDataForHslTest()
    {
        return array(
            // hue, saturation, lightness
            array(array('0.5', '0', '0.5'), array(
                127.5, 127.5, 127.5
            ), '#808080'),
            array(array(
                new ILess_Node_Dimension('340'),
                new ILess_Node_Dimension('12', '%'),
                new ILess_Node_Dimension('95', '%'),
            ), array(
                243.78, 240.72, 241.74
            ), '#f4f1f2'),
            array(array(
                new ILess_Node_Dimension('340'),
                new ILess_Node_Dimension('50', '%'),
                new ILess_Node_Dimension('50', '%'),
            ), array(
                191.25, 63.75, 106.24999999999993
            ), '#bf406a')
        );
    }

    /**
     * @dataProvider getDataForScreenTest
     */
    public function testScreen($color1, $color2, $expected)
    {
        $result = $this->registry->screen($color1, $color2);
        $this->assertInstanceOf('ILess_Node_Color', $result);
        $this->assertEquals($expected, $result->getColor()->toString());
    }

    public function getDataForScreenTest()
    {
        return array(
            array(new ILess_Node_Color(new ILess_Color('#f60000')), new ILess_Node_Color(new ILess_Color('#0000f6')), '#f600f6')
        );
    }

    /**
     * @dataProvider getDataForSpinTest
     */
    public function testSpin($color, $degrees, $expected)
    {
        $result = $this->registry->spin($color, $degrees);
        $this->assertInstanceOf('ILess_Node_Color', $result);
        $this->assertEquals($expected, $result->getColor()->toString());
    }

    public function getDataForSpinTest()
    {
        return array(
            array(new ILess_Node_Color(new ILess_Color('#86797d')), new ILess_Node_Dimension(40), '#867e79')
        );
    }

    /**
     * @covers       ILess_Function::escape
     * @dataProvider getDataForEscapeTest
     */
    public function testEscape($value, $expected)
    {
        $this->assertEquals($expected, $this->registry->escape($value));
    }

    public function getDataForEscapeTest()
    {
        $values = array(new ILess_Node_Anonymous('a=1'), new ILess_Node_Anonymous('foobar'));
        $expected = array('a%3D1', 'foobar');

        return $this->prepareDataForProvider($values, $expected);
    }

    /**
     * @covers       ILess_Function::e
     * @dataProvider getDataForETest
     */
    public function testE($value, $expected)
    {
        $result = $this->registry->e($value);
        $this->assertInstanceOf('ILess_Node_Anonymous', $result);
        $this->assertEquals($result->value, $expected);
    }

    public function getDataForETest()
    {
        // THIS IS A bit confusing, the string is returned AS IS in the implementation
        $values = array(new ILess_Node_Anonymous('ms:alwaysHasItsOwnSyntax.For.Stuff()'));
        $expected = array('ms:alwaysHasItsOwnSyntax.For.Stuff()');

        return $this->prepareDataForProvider($values, $expected);
    }

    /**
     * @covers       ILess_Function::template
     * @dataProvider getDataForTemplateTest
     */
    public function testTemplate($value, $expected)
    {
        $result = call_user_func_array(array($this->registry, 'template'), $value);
        $this->assertInstanceOf('ILess_Node_Quoted', $result);
        $this->assertEquals($expected, $result->value);
    }

    public function getDataForTemplateTest()
    {
        return array(
            array(
                array(
                    new ILess_Node_Anonymous('repetitions: %a file: %d'),
                    new ILess_Node_Anonymous('3'),
                    new ILess_Node_Quoted('"directory/file.less"', 'directory/file.less')
                ),
                'repetitions: 3 file: "directory/file.less"'
            ));
    }

    /**
     * @covers addFunction
     */
    public function testCustomFunction()
    {
        $registry = new ILess_FunctionRegistry();
        $registry->addFunction('foobar', array($this, 'foobarCallable'));
        $registry->call('foobar', array('a', 'b'));
    }

    public function foobarCallable()
    {
        $registry = func_get_arg(0);
        // first argument is the registry instance
        $this->assertInstanceOf('ILess_FunctionRegistry', $registry);
        $arg1 = func_get_arg(1);
        // other arguments are passed too
        $this->assertEquals($arg1, 'a');
        $arg2 = func_get_arg(2);
        $this->assertEquals($arg2, 'b');
    }

}
