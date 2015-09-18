<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Cache\NoCache;
use ILess\Context;
use ILess\Importer;
use ILess\Visitor\ImportVisitor;

/**
 * ILess\ILess\Visitor\Visitor\ImportVisitor tests
 *
 * @package ILess
 * @subpackage test
 * @covers Visitor_Import
 * @group visitor
 */
class Test_Visitor_ImportTest extends Test_TestCase
{

    /**
     * @covers __constructor
     */
    public function testVisit()
    {
        $env = new Context();
        $v = new ImportVisitor($env, new Importer($env, [], new NoCache()));
        $this->assertFalse($v->isReplacing());
    }

}
