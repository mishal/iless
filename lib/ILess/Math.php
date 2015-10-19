<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess;

use InvalidArgumentException;

/**
 * Math.
 */
class Math
{
    /**
     * Rounds the value as javascript's Math.round function.
     *
     * @param float $value
     * @param int $precision
     *
     * @return float
     */
    public static function round($value, $precision = 0)
    {
        $precision = pow(10, $precision);
        $value = $value * $precision;

        $ceil = ceil($value);
        $floor = floor($value);
        if (($ceil - $value) <= ($value - $floor)) {
            return $ceil / $precision;
        } else {
            return $floor / $precision;
        }
    }

    /**
     * Is the number negative?
     *
     * @param float $number
     *
     * @return bool
     */
    public static function isNegative($number)
    {
        return $number < 0;
    }

    /**
     * Remove trailing and leading zeros - just to return cleaner number.
     *
     * @param float $number
     *
     * @return string
     */
    public static function clean($number)
    {
        $number = self::fixDecimalPoint((string) $number);

        // don't clean numbers without dot
        if (strpos($number, '.') === false) {
            return $number;
        }

        // remove zeros from end of number ie. 140.00000 becomes 140.
        $clean = rtrim($number, '0');
        // remove zeros from front of number ie. 0.33 becomes .33
        $clean = ltrim($clean, '0');

        // everything has been cleaned
        if ($clean == '.') {
            return '0';
        }

        // remove decimal point if an integer ie. 140. becomes 140
        $clean = rtrim($clean, '.');

        return $clean[0] == '.' ? '0' . $clean : $clean;
    }

    /**
     * Fixes decimal points.
     *
     * @param mixed $float
     *
     * @return string
     */
    public static function fixDecimalPoint($float)
    {
        return str_replace(',', '.', $float);
    }

    /**
     * Makes an operation on $a and $b using the $operator (+, -, *, /).
     *
     * @param string $operator The operator
     * @param float $a
     * @param float $b
     *
     * @return float
     *
     * @throws InvalidArgumentException When the operator is not valid
     */
    public static function operate($operator, $a, $b)
    {
        switch ($operator) {
            case '+':
                return $a + $b;
            case '-':
                return $a - $b;
            case '*':
                return $a * $b;
            case '/':
                return $a / $b;
            default:
                throw new InvalidArgumentException(sprintf('Invalid operator "%s" given', $operator));
        }
    }

    /**
     * Converts a number into a string, keeping a specified number of decimals.
     *
     * @param float $number The number
     * @param int $decimals Number of decimals
     *
     * @return string
     */
    public static function toFixed($number, $decimals)
    {
        return sprintf('%.' . $decimals . 'f', $number);
    }
}
