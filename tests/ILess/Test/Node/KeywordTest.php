<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\KeywordNode;

/**
 * Keyword node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Keyword
 * @group node
 */
class Test_Node_KeywordTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new KeywordNode('black');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new KeywordNode('black');
        $this->assertEquals('Keyword', $d->getType());
    }

    public function testCompile()
    {
        $env = new Context();
        $d = new KeywordNode('black');
        $result = $d->compile($env);
        $this->assertInstanceOf('ILess\Node\KeywordNode', $result);
    }

}
