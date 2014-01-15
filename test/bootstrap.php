<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Unit test bootstrap
 *
 * @package ILess
 * @subpackage test
 */

require dirname(__FILE__) . '/../lib/ILess/Autoloader.php';
ILess_Autoloader::register();

class ILess_TestCase extends PHPUnit_Framework_TestCase
{
  protected function prepareDataForProvider($values, $expected)
  {
    return array_map(array($this, 'mapValuesWithExpected'), $values, $expected);
  }

  protected function mapValuesWithExpected($values, $expected)
  {
    return array($values, $expected);
  }

  protected function diag($message)
  {
    echo "\n" . $message . "\n";
  }

}
