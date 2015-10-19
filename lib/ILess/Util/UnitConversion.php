<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Util;

/**
 * Unit conversion.
 */
final class UnitConversion
{
    /**
     * Unit groups.
     *
     * @var array
     */
    private static $groups = ['length', 'duration', 'angle'];

    /**
     * Length conversions.
     *
     * @var array
     */
    private static $length = [];

    /**
     * Duration conversions.
     *
     * @var array
     */
    private static $duration = [];

    /**
     * Angle conversions.
     *
     * @var array
     */
    private static $angle = [];

    /**
     * @var bool
     */
    private static $setup = false;

    /**
     * Returns the groups.
     *
     * @return array
     */
    public static function getGroups()
    {
        return self::$groups;
    }

    /**
     * Returns the group by its name.
     *
     * @param string $name
     *
     * @return array|null
     */
    public static function getGroup($name)
    {
        if (!in_array($name, self::$groups)) {
            return;
        }

        self::setup();

        return self::$$name;
    }

    /**
     * Setups the conversions.
     */
    private static function setup()
    {
        if (self::$setup) {
            return;
        }

        // angle
        self::$angle = [
            'rad' => 1 / (2 * M_PI),
            'deg' => 1 / 360,
            'grad' => 1 / 400,
            'turn' => 1,
        ];

        self::$duration = [
            's' => 1,
            'ms' => 0.001,
        ];

        self::$length = [
            'm' => 1,
            'cm' => 0.01,
            'mm' => 0.001,
            'in' => 0.0254,
            'px' => 0.0254 / 96,
            'pt' => 0.0254 / 72,
            'pc' => 0.0254 / 72 * 12,
        ];

        self::$setup = true;
    }
}
