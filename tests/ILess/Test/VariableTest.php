<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Variable tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Variable
 */
class ILess_Test_VariableTest extends ILess_Test_TestCase
{
    /**
     * @covers       toNode
     * @dataProvider getDataForTestCreateTest
     */
    public function testCreate($name, $value, $expectedObj)
    {
        $variable = ILess_Variable::create($name, $value);
        // convert to node
        $this->assertEquals($variable->toNode(), $expectedObj);
    }

    public function getDataForTestCreateTest()
    {
        return array(
            array('foo', '#ffffff', new ILess_Node_Rule('@foo', new ILess_Node_Color(new ILess_Color('#ffffff')))),
            array('angle', '-20rad', new ILess_Node_Rule('@angle', new ILess_Node_Dimension('-20', new ILess_Node_DimensionUnit('rad')))),
            array('foobar', '12px', new ILess_Node_Rule('@foobar', new ILess_Node_Dimension('12', new ILess_Node_DimensionUnit('px')))),
            array('myurl', '"http://example.com/image.jpg"', new ILess_Node_Rule('@myurl', new ILess_Node_Quoted(
                '"http://example.com/image.jpg"', 'http://example.com/image.jpg'))),
        );
    }

}
