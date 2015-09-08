<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Cache\FileSystemCache;

/**
 * Issue #48 test
 *
 * @package ILess
 * @subpackage test
 * @group issue
 */
class Test_Issues_048Test extends Test_TestCase
{
    protected $cacheDir;

    public function setUp()
    {
        $this->cacheDir = sys_get_temp_dir() . '/iless_cache';
    }

    public function testIssue()
    {
        $cache = new FileSystemCache($this->cacheDir);
        $cache->set('foo', 'bar');

        $this->assertFileExists($this->cacheDir . '/foo.cache');

        // put something in the cache folder
        file_put_contents($this->cacheDir . '/something.txt', 'dummy contents');

        $cache->clean();

        // the cache file was removed by clean()
        $this->assertFileNotExists($this->cacheDir . '/foo.cache');
        // the file is not deleted by clean()
        $this->assertFileExists($this->cacheDir . '/something.txt');
    }

    public function tearDown()
    {
        // cleanup
        unlink($this->cacheDir . '/something.txt');
        rmdir($this->cacheDir);
    }

}
