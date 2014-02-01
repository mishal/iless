<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Math
 *
 * @package ILess
 * @subpackage util
 */
class ILess_Math
{
    /**
     * Default precision. Matches the default precision used by Less.js
     *
     * @var integer
     */
    protected static $defaultPrecision = 16;

    /**
     * Old precision for math operations using
     * classical methods like (sin, cos...).
     *
     * @var integer
     * @see setup(), restore()
     */
    private static $oldPrecision;

    /**
     * Setup flag
     *
     * @var boolean
     */
    protected static $setup = false;

    /**
     * Setups the math
     *
     * @param integer $defaultPrecision The default precision
     * @return void
     */
    public static function setup($defaultPrecision = null)
    {
        if (self::$setup) {
            return;
        }

        if ($defaultPrecision) {
            self::$defaultPrecision = (int)$defaultPrecision;
        }

        bcscale(self::$defaultPrecision);
        self::$oldPrecision = array(ini_get('precision'), ini_get('bcmath.scale'));

        if (function_exists('ini_set')) {
            ini_set('bcmath.scale', self::$defaultPrecision);
            ini_set('precision', self::$defaultPrecision);
        } else {
            trigger_error('Math precision could not be set due to forbidden "ini_set" function.', E_USER_WARNING);
        }

        self::$setup = true;
    }

    /**
     * Restores the state of precision setting
     *
     */
    public static function restore()
    {
        if (self::$oldPrecision !== null
            && function_exists('ini_set')
        ) {
            ini_set('precision', self::$oldPrecision[0]);
            ini_set('bcmath.scale', self::$oldPrecision[1]);
            bcscale(self::$oldPrecision[1]);
        }
        self::$setup = false;
    }

    /**
     * Returns default precision
     *
     * @return integer
     */
    public static function getDefaultPrecision()
    {
        return self::$defaultPrecision;
    }

    /**
     * Add two arbitrary precision numbers
     *
     * @param string $left_operand
     * @param string $right_operand
     * @param integer $precision The scale factor
     * @return string The sum of the two operands
     */
    public static function add($left_operand, $right_operand, $precision = null)
    {
        return is_null($precision) ? bcadd($left_operand, $right_operand) : bcadd($left_operand, $right_operand, $precision);
    }

    /**
     * Compares the left_operand to the right_operand and returns the result as an integer.
     *
     * @param string $left_operand The left operand, as a string.
     * @param string $right_operand The right operand, as a string.
     * @param integer $precision The optional scale parameter is used to set the number of digits after the decimal place which will be used in the comparison.
     * @return integer Returns 0 if the two operands are equal, 1 if the left_operand is larger than the right_operand, -1 otherwise.
     */
    public static function compare($left_operand, $right_operand, $precision = null)
    {
        return is_null($precision) ? bccomp($left_operand, $right_operand) : bccomp($left_operand, $right_operand, $precision);
    }

    /**
     * Is the left operand greater than the right operand?
     *
     * @param string $left_operand The left operand, as a string.
     * @param string $right_operand The right operand, as a string.
     * @param integer $precision The optional scale parameter is used to set the number of digits after the decimal place which will be used in the comparison.
     * @return boolean
     */
    public static function isGreaterThan($left_operand, $right_operand, $precision = null)
    {
        $result = self::compare($left_operand, $right_operand, $precision);

        return $result === 1;
    }

    /**
     * Is the left operand greater than or equal the right operand?
     *
     * @param string $left_operand The left operand, as a string.
     * @param string $right_operand The right operand, as a string.
     * @param integer $precision The optional scale parameter is used to set the number of digits after the decimal place which will be used in the comparison.
     * @return boolean
     */
    public static function isGreaterThanOrEqual($left_operand, $right_operand, $precision = null)
    {
        $result = self::compare($left_operand, $right_operand, $precision);

        return $result === 1 || $result === 0;
    }

    /**
     * Is the left operand lower than the right operand?
     *
     * @param string $left_operand The left operand, as a string.
     * @param string $right_operand The right operand, as a string.
     * @param integer $precision The optional scale parameter is used to set the number of digits after the decimal place which will be used in the comparison.
     * @return boolean
     */
    public static function isLowerThan($left_operand, $right_operand, $precision = null)
    {
        $result = self::compare($left_operand, $right_operand, $precision);

        return $result === -1;
    }

    /**
     * Is the left operand lower than or equal the right operand?
     *
     * @param string $left_operand The left operand, as a string.
     * @param string $right_operand The right operand, as a string.
     * @param integer $precision The optional scale parameter is used to set the number of digits after the decimal place which will be used in the comparison.
     * @return boolean
     */
    public static function isLowerThanOrEqual($left_operand, $right_operand, $precision = null)
    {
        $result = self::compare($left_operand, $right_operand, $precision);

        return $result === -1 || $result === 0;
    }

    /**
     * Calculates the square root of the operand
     *
     * @param string $operand The operand, as a string.
     * @param integer $precision The optional scale parameter is used to set the number of digits after the decimal place
     * @return string Return the square root of the operand.
     */
    public static function sqrt($operand, $precision = null)
    {
        return self::clean(is_null($precision) ? bcsqrt($operand) : bcsqrt($operand, $precision));
    }

    /**
     * Divide two arbitrary precision numbers
     *
     * @param string $left_operand The left operand, as a string.
     * @param string $right_operand The right operand, as a string.
     * @param integer $precision This optional parameter is used to set the number of digits after the decimal place in the result.
     * @return string|null Returns the result of the division as a string, or NULL if right_operand is 0.
     */
    public static function divide($left_operand, $right_operand, $precision = null)
    {
        return is_null($precision) ? bcdiv($left_operand, $right_operand) : bcdiv($left_operand, $right_operand, $precision);
    }

    /**
     * Sets the current number to the absolute value of itself
     *
     * @return string
     */
    public static function abs($value)
    {
        $value = self::clean($value);

        // Use substr() to find the negative sign at the beginning of the
        // number, rather than using signum() to determine the sign.
        if (substr($value, 0, 1) === '-') {
            return substr($value, 1);
        }

        return $value;
    }

    /**
     * Get modulus of an arbitrary precision number
     *
     * @param string $left_operand The left operand, as a string.
     * @param string $modulus The modulus, as a string.
     * @return string|null Returns the modulus as a string, or NULL if modulus is 0.
     */
    public static function modulus($left_operand, $modulus)
    {
        return bcmod($left_operand, $modulus);
    }

    /**
     * Multiply two arbitrary precision number
     *
     * @param string $left_operand The left operand, as a string.
     * @param string $right_operand The right operand, as a string.
     * @param integer $precision This optional parameter is used to set the number of digits after the decimal place in the result.
     * @return string Returns the result as a string.
     */
    public static function multiply($left_operand, $right_operand, $precision = null)
    {
        return is_null($precision) ? bcmul($left_operand, $right_operand) : bcmul($left_operand, $right_operand, $precision);
    }

    /**
     * Raise an arbitrary precision number to another
     *
     * @param string $left_operand The left operand, as a string.
     * @param string $right_operand The right operand, as a string.
     * @param integer $precision This optional parameter is used to set the number of digits after the decimal place in the result.
     * @return string Returns the result as a string.
     */
    public static function power($left_operand, $right_operand, $precision = null)
    {
        return is_null($precision) ? bcpow($left_operand, $right_operand) : bcpow($left_operand, $right_operand, $precision);
    }

    /**
     * Substract one arbitrary precision number from another
     *
     * @param string $left_operand The left operand, as a string.
     * @param string $right_operand The right operand, as a string.
     * @param integer $precision This optional parameter is used to set the number of digits after the decimal place in the result.
     * @return string Returns the result as a string.
     */
    public static function substract($left_operand, $right_operand, $precision = null)
    {
        return is_null($precision) ? bcsub($left_operand, $right_operand) : bcsub($left_operand, $right_operand, $precision);
    }

    /**
     * Returns the tangent of the operand
     *
     * @param string $operand
     * @return string
     */
    public static function sin($operand)
    {
        return sin($operand);
    }

    /**
     * Returns the tangent of the operand
     *
     * @param string $operand
     * @return string
     */
    public static function cos($operand)
    {
        return cos($operand);
    }

    /**
     * Returns the tangent of the operand
     *
     * @param string $operand
     * @return string
     */
    public static function tan($operand)
    {
        return tan($operand);
    }

    /**
     * Arc sine
     *
     * @param string $operand
     * @return string
     */
    public static function asin($operand)
    {
        return asin($operand);
    }

    /**
     * Arc cosine
     *
     * @param string $operand
     * @return string
     */
    public static function acos($operand)
    {
        return acos($operand);
    }

    /**
     * Returns the tangent of the operand
     *
     * @param string $operand
     * @return string
     */
    public static function atan($operand)
    {
        return atan($operand);
    }

    /**
     * Rounding mode to round towards zero.
     *
     * @param string $value
     * @param integer $precision
     */
    public static function round($value, $precision = 0)
    {
        $value = self::clean($value);

        if (strpos($value, '.') === false) {
            return $value;
        }

        if (!self::isNegative($value)) {
            return self::add($value, '0.' . str_repeat('0', $precision) . '5', $precision);
        }

        return self::substract($value, '0.' . str_repeat('0', $precision) . '5', $precision);
    }

    /**
     * Rounding mode to round away from zero.
     *
     * @param string $value
     * @param integer $precision
     */
    public static function roundUp($value, $precision = 0)
    {
        $value = self::clean($value);

        if (strpos($value, '.') === false) {
            return $value;
        }

        if (!self::isNegative($value)) {
            return self::ceil($value, $precision);
        } else {
            return self::floor($value, $precision);
        }
    }

    /**
     * Rounding mode to round towards zero.
     *
     * @param string $value
     * @param integer $precision
     */
    public static function roundDown($value, $precision = 0)
    {
        $value = self::clean($value);

        // larger than zero
        if (!self::isNegative($value)) {
            return self::floor($value, $precision);
        } else {
            return self::ceil($value, $precision);
        }
    }

    /**
     * Round fractions up
     *
     * @param string $value The value to round
     * @param integer $precision Precision
     * @return string Returns the next highest value by rounding up value if necessary.
     */
    public static function ceil($value, $precision = 0)
    {
        $value = self::clean($value);

        if (strpos($value, '.') === false) {
            return $value;
        }

        $multiplier = self::power(10, $precision);
        $value = self::multiply($value, $multiplier);

        if (!self::isNegative($value)) {
            $value = self::add($value, '1', 0);
        } else {
            $value = self::substract($value, '0', 0);
        }

        return self::clean(self::divide($value, $multiplier, $precision));
    }

    /**
     * Round fractions down
     *
     * @param string $number
     * @param integer $precision Precision
     * @return string
     */
    public static function floor($number, $precision = 0)
    {
        $number = self::clean($number);

        if (strpos($number, '.') === false) {
            return $number;
        }

        $multiplier = self::power(10, $precision);
        $number = self::multiply($number, $multiplier);

        if (!self::isNegative($number)) {
            $number = self::add($number, '0', 0);
        } else {
            $number = self::substract($number, '1', 0);
        }

        return self::clean(self::divide($number, $multiplier, $precision));
    }

    /**
     * Is the number negative?
     *
     * @param string $number
     * @return boolean
     */
    public static function isNegative($number)
    {
        return self::compare($number, 0) == -1;
    }

    /**
     * Remove trailing and leading zeros - just to return cleaner number
     *
     * @param string $number
     * @return string
     */
    public static function clean($number)
    {
        $number = (string)$number;

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
     * Makes an operatin on $a and $b using the $operator (+, -, *, /)
     *
     * @param string $operator The operator
     * @param string $a
     * @param string $b
     * @return string
     * @throws InvalidArgumentException When the operator is not valid
     */
    public static function operate($operator, $a, $b)
    {
        switch ($operator) {
            case '+':
                return self::clean(self::add($a, $b));
            case '-':
                return self::clean(self::substract($a, $b));
            case '*':
                return self::clean(self::multiply($a, $b));
            case '/':
                return self::clean(self::divide($a, $b));
            default:
                throw new InvalidArgumentException(sprintf('Invalid operator "%s" given', $operator));
        }
    }

    /**
     * Converts a number into a string, keeping a specified number of decimals.
     *
     * @param string $number The number
     * @param integer $decimals Number of decimals
     * @return string
     */
    public static function toFixed($number, $decimals)
    {
        $number = self::clean($number);

        if (strpos($number, '.') === false) {
            return $number;
        }

        return sprintf('%.' . $decimals . 'f', $number);
    }

}
