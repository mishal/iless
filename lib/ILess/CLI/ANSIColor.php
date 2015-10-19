<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\CLI;

/**
 * ANSIColor.
 */
class ANSIColor
{
    /**
     * Array of ANSI codes.
     *
     * @var array
     */
    protected static $codes = [
        'reset' => [0, 0],
        'bold' => [1, 22],
        'inverse' => [7, 27],
        'underline' => [4, 24],
        'yellow' => [33, 39],
        'green' => [32, 39],
        'red' => [31, 39],
        'grey' => [90, 39],
    ];

    /**
     * Colorizes the string by given style.
     *
     * @param string $string The string to colorize
     * @param string $style The style
     *
     * @return string
     */
    public static function colorize($string, $style)
    {
        $styles = explode('+', $style);
        $colorized = '';
        foreach ($styles as $s) {
            $colorized .= "\033[" . self::$codes[$s][0] . 'm';
        }
        $colorized .= $string . "\033[" . self::$codes[$s][1] . 'm';

        return $colorized;
    }
}
