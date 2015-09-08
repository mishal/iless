<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Visitor\ToCSSVisitor;

/**
 * ILess\ILess\Visitor\Visitor\ToCSSVisitor tests
 *
 * @package ILess
 * @subpackage test
 * @covers Visitor_ToCSS
 * @group visitor
 */
class Test_Visitor_ToCSSTest extends Test_TestCase
{

    /**
     * @covers __constructor
     */
    public function testVisit()
    {
        $v = new ToCSSVisitor(new Context());
        $this->assertTrue($v->isReplacing());
    }

}
