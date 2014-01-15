<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Visitor_JoinSelector tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Visitor_JoinSelector
 */
class ILess_Visitor_JoinSelector_Test extends ILess_TestCase
{

    /**
     * @covers __constructor
     */
    public function testVisit()
    {
        $v = new ILess_Visitor_JoinSelector(new ILess_Environment());
        $this->assertFalse($v->isReplacing());
    }

}
