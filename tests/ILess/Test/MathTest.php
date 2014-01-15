<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Math tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Math
 */
class ILess_Test_MathTest extends ILess_Test_TestCase
{
    /**
     * @covers       toFixed
     * @dataProvider getDataForToFixedTest
     */
    public function testToFixed($test, $decimals, $expected)
    {
        $this->assertEquals($expected, ILess_Math::toFixed($test, $decimals));
    }

    public function getDataForToFixedTest()
    {
        return array(
            // test, expected
            array('1', 2, '1'),
            array('0.00001', 20, '0.00001'),
            array('0.000000000000001', 20, '0.000000000000001'),
            array('1.000000000000001', 20, '1.000000000000001'),
        );
    }

    /**
     * @covers       round
     * @dataProvider getDataForRoundTest
     */
    public function testRound($value, $precision, $expected)
    {
        $this->assertEquals($expected, ILess_Math::round($value, $precision), sprintf('Rounding of "%s" with precision "%s" works', $value, $precision));
    }

    public function getDataForRoundTest()
    {
        return array(
            // test, precision, expected
            array('1.499999', 0, '1'),
            array('71.52', 0, '72'),
            array('78.47', 0, '78'),
            array('71.25', 0, '71')
        );
    }

}
