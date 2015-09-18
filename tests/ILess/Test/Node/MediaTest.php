<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\KeywordNode;
use ILess\Node\MediaNode;

/**
 * Media node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Media
 * @group node
 */
class Test_Node_MediaTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new MediaNode(['black']);
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new MediaNode(['black']);
        $this->assertEquals('Media', $d->getType());
    }

    public function testCompile()
    {
        $env = new Context();
        $d = new KeywordNode('black');
        $result = $d->compile($env);
        $this->assertInstanceOf('ILess\Node\KeywordNode', $result);
    }

}
