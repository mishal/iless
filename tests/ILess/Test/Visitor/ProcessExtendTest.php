<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Visitor_ProcessExtend tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Visitor_ProcessExtend
 */
class ILess_Test_Visitor_ProcessExtendTest extends ILess_Test_TestCase
{

    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $v = new ILess_Visitor_ProcessExtend();
        $this->assertFalse($v->isReplacing());
    }

}
