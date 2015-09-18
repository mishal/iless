<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Math;

/**
 * Math tests
 *
 * @package ILess
 * @subpackage test
 * @covers Math
 * @group util
 */
class Test_MathTest extends Test_TestCase
{
    /**
     * @covers       toFixed
     * @dataProvider getDataForToFixedTest
     */
    public function testToFixed($test, $decimals, $expected)
    {
        $this->assertEquals($expected, Math::toFixed($test, $decimals));
    }

    public function getDataForToFixedTest()
    {
        return [
            // test, expected
            ['1', 2, '1'],
            ['0.00001', 20, '0.00001'],
            ['0.000000000000001', 20, '0.000000000000001'],
            ['1.000000000000001', 20, '1.000000000000001'],
        ];
    }

    /**
     * @covers       round
     * @dataProvider getDataForRoundTest
     */
    public function testRound($value, $precision, $expected)
    {
        $this->assertEquals($expected, Math::round($value, $precision), sprintf('Rounding of "%s" with precision "%s" works', $value, $precision));
    }

    public function getDataForRoundTest()
    {
        return [
            // test, precision, expected
            ['1.499999', 0, '1'],
            ['71.52', 0, '72'],
            ['78.47', 0, '78'],
            ['71.25', 0, '71']
        ];
    }

}
