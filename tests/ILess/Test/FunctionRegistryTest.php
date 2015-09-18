<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Color;
use ILess\Context;
use ILess\FunctionRegistry;
use ILess\Node\AnonymousNode;
use ILess\Node\ColorNode;
use ILess\Node\DimensionNode;

/**
 * Function tests
 *
 * @package ILess
 * @subpackage test
 */
class Test_FunctionRegistryTest extends Test_TestCase
{
    protected $registry;

    public function setUp()
    {
        $this->registry = new FunctionRegistry([], new Context());
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
        return [
            // hue, saturation, lightness
            [['0.5', '0', '0.5'], [
                127.5, 127.5, 127.5
            ], '#808080'],
            [[
                new DimensionNode('340'),
                new DimensionNode('12', '%'),
                new DimensionNode('95', '%'),
            ], [
                243.78, 240.72, 241.74
            ], '#f4f1f2'],
            [[
                new DimensionNode('340'),
                new DimensionNode('50', '%'),
                new DimensionNode('50', '%'),
            ], [
                191.25, 63.75, 106.24999999999993
            ], '#bf406a']
        ];
    }

    /**
     * @dataProvider getDataForScreenTest
     */
    public function testScreen($color1, $color2, $expected)
    {
        $result = $this->registry->screen($color1, $color2);
        $this->assertInstanceOf('ILess\Node\ColorNode', $result);
        $this->assertEquals($expected, $result->getColor()->toString());
    }

    public function getDataForScreenTest()
    {
        return [
            [new ColorNode(new Color('#f60000')), new ColorNode(new Color('#0000f6')), '#f600f6']
        ];
    }

    /**
     * @dataProvider getDataForSpinTest
     */
    public function testSpin($color, $degrees, $expected)
    {
        $result = $this->registry->spin($color, $degrees);
        $this->assertInstanceOf('ILess\Node\ColorNode', $result);
        $this->assertEquals($expected, $result->getColor()->toString());
    }

    public function getDataForSpinTest()
    {
        return [
            [new ColorNode(new Color('#86797d')), new DimensionNode(40), '#867e79']
        ];
    }

    /**
     * @covers       Function::escape
     * @dataProvider getDataForEscapeTest
     */
    public function testEscape($value, $expected)
    {
        $this->assertEquals($expected, $this->registry->escape($value));
    }

    public function getDataForEscapeTest()
    {
        $values = [new AnonymousNode('a=1'), new AnonymousNode('foobar')];
        $expected = ['a%3D1', 'foobar'];

        return $this->prepareDataForProvider($values, $expected);
    }

    /**
     * @covers addFunction
     */
    public function testCustomFunction()
    {
        $registry = new FunctionRegistry();
        $registry->addFunction('foobar', [$this, 'foobarCallable']);
        $registry->call('foobar', ['a', 'b']);
    }

    public function foobarCallable()
    {
        $registry = func_get_arg(0);
        // first argument is the registry instance
        $this->assertInstanceOf('ILess\FunctionRegistry', $registry);
        $arg1 = func_get_arg(1);
        // other arguments are passed too
        $this->assertEquals($arg1, 'a');
        $arg2 = func_get_arg(2);
        $this->assertEquals($arg2, 'b');
    }

}
