<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Unit conversion
 *
 * @package ILess
 * @subpackage util
 */
class ILess_UnitConversion
{
    /**
     * Setup flag
     *
     * @var boolean
     */
    private static $setup = false;

    /**
     * Unit groups
     *
     * @var array
     */
    public static $groups = array('length', 'duration', 'angle');

    /**
     * Length conversions
     *
     * @var array
     */
    public static $length = array();

    /**
     * Duration conversions
     *
     * @var array
     */
    public static $duration = array(
        's' => '1',
        'ms' => '0.001'
    );

    /**
     * Angle conversions
     *
     * @var array
     */
    public static $angle = array();

    /**
     * Setups conversions with given precision.
     *
     * @param array $precision
     */
    public static function setup()
    {
        if (self::$setup !== false) {
            return;
        }

        self::$angle = array(
            'rad' => ILess_Math::divide(1, ILess_Math::multiply('2', M_PI)), // 1/(2*M_PI)
            'deg' => ILess_Math::divide(1, 360), // 1/360
            'grad' => ILess_Math::divide(1, 400), // 1/400
            'turn' => '1'
        );

        self::$length = array(
            'm' => '1',
            'cm' => '0.01',
            'mm' => '0.001',
            'in' => '0.0254',
            'pt' => ILess_Math::divide('0.0254', '72'), // 0.0254 / 72,
            'pc' => ILess_Math::multiply(ILess_Math::divide('0.0254', '72'), '12'), //0.0254 / 72 * 12
        );

        self::$setup = true;
    }

    /**
     * Retores the state so the setup can be called again
     *
     */
    public static function restore()
    {
        self::$setup = false;
    }

}
