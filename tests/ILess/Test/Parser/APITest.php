<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Parser API tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Parser
 */
class ILess_Test_ParserAPITest extends ILess_Test_TestCase
{

    /**
     * @covers getCache
     */
    public function testGetCache()
    {
        $parser = new ILess_Parser();
        $this->assertInstanceOf('ILess_CacheInterface', $parser->getCache());

        $parser = new ILess_Parser(array(), new ILess_Cache_FileSystem(array(
            'cache_dir' => sys_get_temp_dir()
        )));
        $this->assertInstanceOf('ILess_CacheInterface', $parser->getCache());
    }

}