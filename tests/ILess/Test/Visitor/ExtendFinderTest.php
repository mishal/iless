<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Visitor_ExtendFinder tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Visitor_ExtendFinder
 */
class ILess_Test_Visitor_ExtendFinderTest extends ILess_Test_TestCase
{

    /**
     * @covers __constructor
     */
    public function testVisit()
    {
        $v = new ILess_Visitor_ExtendFinder(new ILess_Environment());
        $this->assertFalse($v->isReplacing());
    }

}
