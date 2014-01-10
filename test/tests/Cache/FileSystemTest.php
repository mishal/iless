<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * File cache tests
 *
 * @package ILess
 * @subpackage test
 */
class ILess_File_Cache_Test extends ILess_TestCase
{
  /**
   * @covers 
   */
  public function testFileCache()
  {
    $cache = new ILess_Cache_FileSystem(array('cache_dir' => dirname(__FILE__) . '/temp'));

    $cache->set('a', 'foobar');    
    $this->assertEquals(true, $cache->has('a'));
    $cache->remove('a');
    $this->assertEquals(false, $cache->has('a'));
  }

}
