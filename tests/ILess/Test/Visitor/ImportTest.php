<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Visitor_Import tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Visitor_Import
 */
class ILess_Test_Visitor_ImportTest extends ILess_Test_TestCase
{

    /**
     * @covers __constructor
     */
    public function testVisit()
    {
        $env = new ILess_Environment();
        $v = new ILess_Visitor_Import($env, new ILess_Importer($env, array(), new ILess_Cache_None()));
        $this->assertTrue($v->isReplacing());
    }

}
