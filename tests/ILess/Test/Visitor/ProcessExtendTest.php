<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Visitor\ProcessExtendsVisitor;

/**
 * ILess\ILess\Visitor\Visitor\ProcessExtendsVisitor tests
 *
 * @package ILess
 * @subpackage test
 * @covers Visitor_ProcessExtend
 * @group visitor
 */
class Test_Visitor_ProcessExtendTest extends Test_TestCase
{

    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $v = new ProcessExtendsVisitor();
        $this->assertFalse($v->isReplacing());
    }

}
