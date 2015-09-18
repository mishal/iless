<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Cache\FileSystemCache;
use ILess\Parser;

/**
 * Parser API tests
 *
 * @package ILess
 * @subpackage test
 * @covers Parser
 * @group parser
 */
class Test_ParserAPITest extends Test_TestCase
{

    /**
     * @covers getCache
     */
    public function testGetCache()
    {
        $parser = new Parser();
        $this->assertInstanceOf('ILess\Cache\CacheInterface', $parser->getCache());

        $parser = new Parser([], new FileSystemCache([
            'cache_dir' => sys_get_temp_dir()
        ]));
        $this->assertInstanceOf('ILess\Cache\CacheInterface', $parser->getCache());
    }

}
