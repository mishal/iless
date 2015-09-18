<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Test_TestCase extends PHPUnit_Framework_TestCase
{
    protected function prepareDataForProvider($values, $expected)
    {
        return array_map([$this, 'mapValuesWithExpected'], $values, $expected);
    }

    protected function mapValuesWithExpected($values, $expected)
    {
        return [$values, $expected];
    }

    protected function diag($message)
    {
        echo "\n" . $message . "\n";
    }
}
