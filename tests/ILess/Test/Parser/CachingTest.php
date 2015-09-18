<?php

namespace ILess\Test;

use ILess\Cache\CacheInterface;
use ILess\Cache\FileSystemCache;
use ILess\Parser;

require_once __DIR__.'/ParsingTest.php';

class CachingTest extends \Test_Parser_ParsingTest
{
    // the same tests as parsing test, but with cache enabled

    public static function setUpBeforeClass()
    {
        echo "\n ----- Testing parsing test with cache enabled ----- \n";
    }

    public static function tearDownAfterClass()
    {
        echo str_repeat('-', 40)."\n";
    }

    /**
     * Test again, when the cache is already there
     *
     * @dataProvider getCompilationData
     */
    public function testCompilationAgain($lessFile, $cssFile, $options = [], $variables = [], $filter = null)
    {
        $this->testCompilation($lessFile, $cssFile, $options, $variables, $filter);
    }

    protected function createParser($options = [], CacheInterface $cache = null)
    {
        return parent::createParser($options, $cache ? $cache : new FileSystemCache(ILESS_TEST_CACHE_DIR));
    }

}
