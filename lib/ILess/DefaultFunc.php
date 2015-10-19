<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess;

use ILess\Exception\Exception;
use ILess\Node\KeywordNode;

/**
 * DefaultFunc.
 */
final class DefaultFunc
{
    /**
     * @var Exception|null
     */
    private static $error;

    /**
     * @var mixed
     */
    private static $value;

    /**
     * Set an exception error.
     *
     * @param Exception $error
     */
    public static function error(Exception $error)
    {
        self::$error = $error;
    }

    /**
     * Compiles the default func.
     *
     * @return KeywordNode|null
     *
     * @throws Exception
     */
    public static function compile()
    {
        if (self::$error) {
            throw self::$error;
        }

        if (null !== self::$value) {
            return self::$value ? new KeywordNode('true') : new KeywordNode('false');
        }
    }

    /**
     * Sets the value.
     *
     * @param mixed $value
     */
    public static function value($value)
    {
        self::$value = $value;
    }

    /**
     * Reset.
     */
    public static function reset()
    {
        self::$value = self::$error = null;
    }
}
