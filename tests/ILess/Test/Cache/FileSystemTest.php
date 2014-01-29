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
 * @covers ILess_Cache_FileSystem
 */
class ILess_Test_Cache_FileSystemTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testDirectorySetupThrowsException()
    {
        if (DIRECTORY_SEPARATOR == '/') {
            $dir = '/root/foobar';
        } else {
            $dir = '\000YY:\/N0nSeNse/x';
        }
        $this->setExpectedException('ILess_Exception_Cache', sprintf('The cache directory "%s" could not be created.', $dir));
        $cache = new ILess_Cache_FileSystem(array('cache_dir' => $dir));
    }

    /**
     * @covers __constructor
     */
    public function testDirectorySetupCreatesDirectory()
    {
        $dir = sys_get_temp_dir() . '/iless_test';
        $cache = new ILess_Cache_FileSystem(array('cache_dir' => $dir));
        // the directory has been created
        $this->assertEquals(true, is_dir($dir) && is_writable($dir));
        // cleanup
        rmdir($dir);
    }

    /**
     * @covers has
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
