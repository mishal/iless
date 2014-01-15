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
class ILess_Test_Cache_FileSystemTest extends ILess_Test_TestCase
{
    /**
     * @covers
     */
    public function testFileCache()
    {
        $cache = new ILess_Cache_FileSystem(array('cache_dir' => sys_get_temp_dir()));
        $cache->set('a', 'foobar');
        $this->assertEquals(true, $cache->has('a'));
        $cache->remove('a');
        $this->assertEquals(false, $cache->has('a'));
    }

}
