<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Visitor\ExtendFinderVisitor;

/**
 * ILess\ILess\Visitor\Visitor\ExtendFinderVisitor tests
 *
 * @package ILess
 * @subpackage test
 * @covers Visitor_ExtendFinder
 * @group visitor
 */
class Test_Visitor_ExtendFinderTest extends Test_TestCase
{

    /**
     * @covers __constructor
     */
    public function testVisit()
    {
        $v = new ExtendFinderVisitor();
        $this->assertFalse($v->isReplacing());
    }

}
